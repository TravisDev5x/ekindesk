<?php

namespace App\Http\Controllers\Sigua;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Sigua\Alerta;
use App\Models\Sigua\Bitacora;
use App\Models\Sigua\Cruce;
use App\Models\Sigua\CuentaGenerica;
use App\Models\Sigua\FormatoCA01;
use App\Models\Sigua\Incidente;
use App\Models\Sigua\Sistema;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiguaDashboardController extends Controller
{
    private const CA01_DIAS_POR_VENCER = 15;

    /**
     * Dashboard centralizado: métricas de cruce, CA-01, riesgo por campaña/isla, fuga de accesos e histórico.
     * Permiso: sigua.dashboard
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->can('sigua.dashboard')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $sedeId = $request->input('sede_id');
            $sistemaId = $request->input('sistema_id');
            $campaignId = $request->input('campaign_id');
            $fechaDesde = $request->input('fecha_desde') ? Carbon::parse($request->input('fecha_desde')) : null;
            $fechaHasta = $request->input('fecha_hasta') ? Carbon::parse($request->input('fecha_hasta')) : null;

            $cuentasQuery = CuentaGenerica::query()->where('estado', 'activa');
            $ca01Query = FormatoCA01::query();
            $bitacoraQuery = Bitacora::query();
            $incidentesQuery = Incidente::query();

            if ($sedeId) {
                $cuentasQuery->where('sede_id', $sedeId);
                $ca01Query->where('sede_id', $sedeId);
                $bitacoraQuery->where('sede_id', $sedeId);
                $incidentesQuery->whereHas('account', fn ($q) => $q->where('sede_id', $sedeId));
            }
            if ($sistemaId) {
                $cuentasQuery->where('system_id', $sistemaId);
                $ca01Query->where('system_id', $sistemaId);
                $bitacoraQuery->where('system_id', $sistemaId);
                $incidentesQuery->where('system_id', $sistemaId);
            }
            if ($campaignId) {
                $cuentasQuery->where('campaign_id', $campaignId);
                $ca01Query->where('campaign_id', $campaignId);
            }
            if ($fechaDesde) {
                $bitacoraQuery->whereDate('fecha', '>=', $fechaDesde);
                $incidentesQuery->where('fecha_incidente', '>=', $fechaDesde);
            }
            if ($fechaHasta) {
                $bitacoraQuery->whereDate('fecha', '<=', $fechaHasta);
                $incidentesQuery->where('fecha_incidente', '<=', $fechaHasta->endOfDay());
            }

            $sistemasActivos = Sistema::activos()->orderBy('orden')->orderBy('name')->get(['id', 'name', 'slug']);
            $indicadoresPorSistema = $sistemasActivos->map(function ($sistema) use ($cuentasQuery, $bitacoraQuery, $incidentesQuery) {
                $qCuentas = (clone $cuentasQuery)->where('system_id', $sistema->id);
                $qBitacora = (clone $bitacoraQuery)->where('system_id', $sistema->id)->hoy();
                $qIncidentes = (clone $incidentesQuery)->where('system_id', $sistema->id)->abiertos();
                return [
                    'sistema_id' => $sistema->id,
                    'sistema' => $sistema->name,
                    'slug' => $sistema->slug,
                    'total_cuentas' => $qCuentas->count(),
                    'bitacoras_hoy' => $qBitacora->count(),
                    'incidentes_abiertos' => $qIncidentes->count(),
                ];
            })->values()->all();

            $totalCuentasPorSistema = $sistemasActivos->map(fn ($s) => [
                'sistema_id' => $s->id,
                'sistema' => $s->name,
                'total' => (clone $cuentasQuery)->where('system_id', $s->id)->count(),
            ])->all();

            // CA-01: Vigentes, Por vencer (próximos 15 días), Vencidos
            $hoy = Carbon::today();
            $limitePorVencer = $hoy->copy()->addDays(self::CA01_DIAS_POR_VENCER);
            $ca01Vigentes = (clone $ca01Query)->vigentes()->whereDate('fecha_vencimiento', '>=', $hoy)->count();
            $ca01PorVencer = (clone $ca01Query)->vigentes()
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $limitePorVencer)
                ->count();
            $ca01Vencidos = (clone $ca01Query)->vencidos()->count();
            $cuentasGenericasSinCa01 = (clone $cuentasQuery)->where('tipo', 'generica')
                ->whereDoesntHave('formatosCA01', fn ($q) => $q->where('sigua_ca01.estado', 'vigente'))
                ->count();

            $bitacorasHoy = (clone $bitacoraQuery)->hoy()->count();
            $incidentesAbiertos = (clone $incidentesQuery)->abiertos()->count();

            $distribucionPorSede = CuentaGenerica::query()
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->when($sistemaId, fn ($q) => $q->where('system_id', $sistemaId))
                ->when($campaignId, fn ($q) => $q->where('campaign_id', $campaignId))
                ->where('estado', 'activa')
                ->selectRaw('sede_id, count(*) as total')
                ->groupBy('sede_id')
                ->with('sede:id,name,code')
                ->get()
                ->map(fn ($r) => ['sede_id' => $r->sede_id, 'sede' => $r->sede?->name ?? null, 'total' => (int) $r->total]);

            // Último cruce: métricas y fuga de accesos (bajas con cuentas activas). El cruce es global.
            $ultimoCruce = Cruce::orderByDesc('fecha_ejecucion')->first();

            $totalAuditadas = $ultimoCruce ? $ultimoCruce->total_analizados : 0;
            $anomaliasTotal = $ultimoCruce ? $ultimoCruce->resultados()->where('requiere_accion', true)->count() : 0;
            $cuentasLimpias = max(0, $totalAuditadas - $anomaliasTotal);
            $alertasCriticasBajasActivas = $ultimoCruce
                ? $ultimoCruce->resultados()->where('categoria', 'cuenta_baja_pendiente')->count()
                : 0;

            // Cumplimiento: cuentas con CA-01 vigente O con empleado RH vinculado (sin duplicar) / total cuentas activas
            $totalCuentasActivas = (clone $cuentasQuery)->count();
            $cuentasCumplen = (clone $cuentasQuery)
                ->where(function ($q) {
                    $q->whereNotNull('empleado_rh_id')
                        ->orWhereHas('formatosCA01', fn ($q2) => $q2->where('sigua_ca01.estado', 'vigente'));
                })
                ->count();
            $porcentajeCumplimiento = $totalCuentasActivas > 0
                ? round(min(100, $cuentasCumplen / $totalCuentasActivas * 100), 1)
                : 100;

            // Distribución por tipo de cuenta (Genéricas, Nominales, Servicio)
            $distribucionPorTipo = (clone $cuentasQuery)
                ->selectRaw('tipo, count(*) as total')
                ->groupBy('tipo')
                ->get()
                ->map(fn ($r) => ['tipo' => $r->tipo ?: 'desconocida', 'total' => (int) $r->total])
                ->keyBy('tipo');

            $cuentasNominales = $distribucionPorTipo->get('nominal')?->total ?? 0;
            $cuentasGenericas = $distribucionPorTipo->get('generica')?->total ?? 0;
            $cuentasServicio = $distribucionPorTipo->get('servicio')?->total ?? 0;

            // Top 5 campañas/islas en riesgo (más anomalías en último cruce)
            $campanasEnRiesgo = [];
            if ($ultimoCruce) {
                $agrupado = $ultimoCruce->resultados()
                    ->where('requiere_accion', true)
                    ->whereNotNull('campana')
                    ->where('campana', '!=', '')
                    ->selectRaw('campana, count(*) as anomalias')
                    ->groupBy('campana')
                    ->orderByDesc('anomalias')
                    ->limit(5)
                    ->get();
                $campaignIdsByName = Campaign::whereIn('name', $agrupado->pluck('campana'))->get()->keyBy('name');
                foreach ($agrupado as $row) {
                    $campaign = $campaignIdsByName->get($row->campana);
                    $gerenteNombre = null;
                    if ($campaign) {
                        $ca01 = FormatoCA01::where('campaign_id', $campaign->id)
                            ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                            ->with('gerente:id,name')
                            ->orderByDesc('fecha_vencimiento')
                            ->first();
                        $gerenteNombre = $ca01?->gerente?->name;
                    }
                    $campanasEnRiesgo[] = [
                        'campana' => $row->campana,
                        'isla' => $row->campana,
                        'anomalias' => (int) $row->anomalias,
                        'responsable' => $gerenteNombre,
                    ];
                }
            }

            // Histórico anomalías: últimos 6 meses (tendencia)
            $historicoAnomalias = $this->historicoAnomalias(6, $sedeId, $campaignId);

            // Alertas no resueltas para el panel
            $alertasQuery = Alerta::noResueltas()->with(['sede:id,name', 'dirigidaA:id,name']);
            if ($sedeId) {
                $alertasQuery->where('sede_id', $sedeId);
            }
            $alertas = $alertasQuery->orderByDesc('created_at')->limit(20)->get()->map(fn ($a) => [
                'tipo' => $a->tipo,
                'mensaje' => $a->titulo ?: $a->descripcion,
                'severidad' => $a->severidad,
                'datos' => ['id' => $a->id],
            ])->values()->all();

            $data = [
                'indicadores_por_sistema' => $indicadoresPorSistema,
                'total_cuentas_por_sistema' => $totalCuentasPorSistema,
                'ca01_vigentes' => $ca01Vigentes,
                'ca01_por_vencer' => $ca01PorVencer,
                'ca01_vencidos' => $ca01Vencidos,
                'ca01_sin_formato_cuentas' => $cuentasGenericasSinCa01,
                'bitacoras_hoy' => $bitacorasHoy,
                'incidentes_abiertos' => $incidentesAbiertos,
                'distribucion_por_sede' => $distribucionPorSede,
                'alertas_bajas' => Cache::get('sigua_alertas_bajas'),
                'alertas_criticas_bajas_activas' => $alertasCriticasBajasActivas,
                'alertas' => $alertas,
                'total_auditadas' => $totalAuditadas,
                'anomalias_total' => $anomaliasTotal,
                'cuentas_limpias' => $cuentasLimpias,
                'total_cuentas_activas' => $totalCuentasActivas,
                'porcentaje_cumplimiento' => $porcentajeCumplimiento,
                'distribucion_por_tipo' => [
                    'nominal' => $cuentasNominales,
                    'generica' => $cuentasGenericas,
                    'servicio' => $cuentasServicio,
                ],
                'campanas_en_riesgo' => $campanasEnRiesgo,
                'historico_anomalias' => $historicoAnomalias,
            ];

            return response()->json(['data' => $data, 'message' => 'OK']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al obtener el dashboard: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve array de { mes, ano, etiqueta, anomalias } para los últimos N meses.
     *
     * @return array<int, array{mes: int, ano: int, etiqueta: string, anomalias: int}>
     */
    private function historicoAnomalias(int $meses, ?int $sedeId, ?int $campaignId): array
    {
        $out = [];
        $fecha = Carbon::today()->startOfMonth();
        $nombresMes = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        for ($i = 0; $i < $meses; $i++) {
            $inicioMes = $fecha->copy()->subMonths($meses - 1 - $i)->startOfMonth();
            $finMes = $inicioMes->copy()->endOfMonth();
            $cruceDelMes = Cruce::whereBetween('fecha_ejecucion', [$inicioMes, $finMes])
                ->orderByDesc('fecha_ejecucion')
                ->first();
            $anomalias = $cruceDelMes
                ? $cruceDelMes->resultados()->where('requiere_accion', true)->count()
                : 0;
            $out[] = [
                'mes' => (int) $inicioMes->month,
                'ano' => (int) $inicioMes->year,
                'etiqueta' => $nombresMes[$inicioMes->month] . ' ' . $inicioMes->year,
                'anomalias' => $anomalias,
            ];
        }
        return $out;
    }
}
