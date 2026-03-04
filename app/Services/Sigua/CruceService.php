<?php

namespace App\Services\Sigua;

use App\Exceptions\Sigua\SiguaException;
use App\Models\AuditLog;
use App\Models\Sigua\Cruce;
use App\Models\Sigua\CruceResultado;
use App\Models\Sigua\CuentaGenerica;
use App\Models\Sigua\EmpleadoRh;
use App\Models\Sigua\Sistema;
use App\Models\User;
use App\Notifications\Sigua\GhostUserAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de cruces dinámicos contra N sistemas (SIGUA v2).
 * Cruza empleados RH con cuentas por sistema, categoriza y detecta anomalías.
 */
class CruceService
{
    /**
     * Ejecuta cruce completo: por cada empleado RH activo (y bajas con cuentas), y cuentas huérfanas/genéricas.
     *
     * @param  array<int>|null  $sistemaIds  Si null, usa todos los sistemas activos
     * @throws SiguaException
     */
    public function ejecutarCruceCompleto(?array $sistemaIds, int $ejecutadoPorUserId): Cruce
    {
        $sistemas = $sistemaIds !== null
            ? Sistema::whereIn('id', $sistemaIds)->where('activo', true)->orderBy('orden')->get()
            : Sistema::activos()->orderBy('orden')->get();

        if ($sistemas->isEmpty()) {
            throw new SiguaException('No hay sistemas activos para el cruce.');
        }

        $sistemaIdsList = $sistemas->pluck('id')->all();
        $sistemasIncluidos = $sistemas->map(fn (Sistema $s) => ['id' => $s->id, 'slug' => $s->slug])->all();
        $nombreCruce = 'Cruce completo ' . now()->format('Y-m-d H:i');
        $aplicaPorSistema = $this->obtenerSistemasAplicanPorCampana($sistemaIdsList);

        return DB::transaction(function () use (
            $sistemas,
            $sistemasIncluidos,
            $nombreCruce,
            $aplicaPorSistema,
            $ejecutadoPorUserId
        ) {
            $cruce = Cruce::create([
                'import_id' => null,
                'tipo_cruce' => 'completo',
                'nombre' => $nombreCruce,
                'sistemas_incluidos' => $sistemasIncluidos,
                'fecha_ejecucion' => now(),
                'total_analizados' => 0,
                'coincidencias' => 0,
                'sin_match' => 0,
                'resultado_json' => null,
                'ejecutado_por' => $ejecutadoPorUserId,
            ]);

            $stats = ['ok_completo' => 0, 'sin_cuenta_sistema' => 0, 'generico_con_responsable' => 0, 'generico_sin_responsable' => 0, 'generica_sin_justificacion' => 0, 'cuenta_baja_pendiente' => 0, 'cuenta_sin_rh' => 0, 'cuenta_servicio' => 0, 'anomalia' => 0, 'externo_sin_justificacion' => 0, 'externo_con_justificacion' => 0, 'por_clasificar' => 0];

            // Empleados activos (cargar cuentas y CA-01 vigente para clasificación)
            $empleadosActivos = EmpleadoRh::activos()->with([
                'cuentas' => fn ($q) => $q->whereIn('system_id', $sistemas->pluck('id'))->with('formatosCA01'),
            ])->get();
            foreach ($empleadosActivos as $empleado) {
                $res = $this->evaluarEmpleado($empleado, $sistemas, $aplicaPorSistema);
                $this->crearResultado($cruce, $res);
                $stats[$res['categoria']] = ($stats[$res['categoria']] ?? 0) + 1;
            }

            // Empleados baja/baja probable con cuentas activas
            $empleadosBaja = EmpleadoRh::whereIn('estatus', ['Baja', 'Baja probable'])->with(['cuentas' => fn ($q) => $q->whereIn('system_id', $sistemas->pluck('id'))->where('estado', 'activa')])->get();
            foreach ($empleadosBaja as $empleado) {
                if ($empleado->cuentas->isEmpty()) {
                    continue;
                }
                $res = $this->evaluarEmpleadoBaja($empleado, $sistemas);
                $this->crearResultado($cruce, $res);
                $stats['cuenta_baja_pendiente'] = ($stats['cuenta_baja_pendiente'] ?? 0) + 1;
            }

            // Regla 2: Externos (tipo externo, no se cruza con RH; solo validar CA-01 vigente)
            $externas = CuentaGenerica::whereIn('system_id', $sistemas->pluck('id'))
                ->where('tipo', 'externo')
                ->where('estado', 'activa')
                ->with(['sistema', 'sede', 'campaign', 'ca01Vigente'])
                ->get();
            foreach ($externas as $cuenta) {
                if ($cuenta->ca01Vigente->isNotEmpty()) {
                    $res = $this->resultadoExternoConJustificacion($cuenta, $sistemas);
                    $this->crearResultado($cruce, $res);
                    $stats['externo_con_justificacion'] = ($stats['externo_con_justificacion'] ?? 0) + 1;
                } else {
                    $res = $this->resultadoExternoSinJustificacion($cuenta, $sistemas);
                    $this->crearResultado($cruce, $res);
                    $stats['externo_sin_justificacion'] = ($stats['externo_sin_justificacion'] ?? 0) + 1;
                }
            }

            // Regla 3: Por clasificar (activa, sin RH, no generica/servicio/externo -> desconocida u otro)
            $porClasificar = CuentaGenerica::whereIn('system_id', $sistemas->pluck('id'))
                ->where('estado', 'activa')
                ->whereNull('empleado_rh_id')
                ->whereNotIn('tipo', ['generica', 'servicio', 'prueba', 'externo'])
                ->with(['sistema', 'sede', 'campaign'])
                ->get();
            foreach ($porClasificar as $cuenta) {
                $res = $this->resultadoPorClasificar($cuenta, $sistemas);
                $this->crearResultado($cruce, $res);
                $stats['por_clasificar'] = ($stats['por_clasificar'] ?? 0) + 1;
            }

            // Genéricas activas sin empleado: validación estricta por CA-01 vigente (ISO 27001 / operación controlada)
            $genericasSinEmpleado = CuentaGenerica::whereIn('system_id', $sistemas->pluck('id'))
                ->where('tipo', 'generica')
                ->where('estado', 'activa')
                ->whereNull('empleado_rh_id')
                ->with(['sistema', 'sede', 'campaign', 'ca01Vigente'])
                ->get();
            foreach ($genericasSinEmpleado as $cuenta) {
                if ($cuenta->ca01Vigente->isNotEmpty()) {
                    $res = $this->resultadoGenericoConResponsable($cuenta, $sistemas);
                    $this->crearResultado($cruce, $res);
                    $stats['generico_con_responsable'] = ($stats['generico_con_responsable'] ?? 0) + 1;
                } else {
                    $res = $this->resultadoGenericaSinJustificacion($cuenta, $sistemas);
                    $this->crearResultado($cruce, $res);
                    $stats['generica_sin_justificacion'] = ($stats['generica_sin_justificacion'] ?? 0) + 1;
                }
            }

            $total = $cruce->resultados()->count();
            $coincidencias = $stats['ok_completo'] ?? 0;
            $cruce->update([
                'total_analizados' => $total,
                'coincidencias' => $coincidencias,
                'sin_match' => $total - $coincidencias,
                'resultado_json' => ['stats' => $stats],
            ]);

            $cruce = $cruce->fresh(['resultados']);

            $this->notificarUsuariosFantasmaSiAplica($cruce);

            return $cruce;
        });
    }

    /**
     * Si el cruce tiene resultados cuenta_baja_pendiente (usuarios fantasma), notifica a
     * Administrador de Accesos / Seguridad Informática y registra en audit_logs.
     * No interrumpe el flujo si falla el envío (try-catch).
     */
    public function notificarUsuariosFantasmaSiAplica(Cruce $cruce): void
    {
        $fantasmas = $cruce->resultados()
            ->where('categoria', 'cuenta_baja_pendiente')
            ->get();

        if ($fantasmas->isEmpty()) {
            return;
        }

        $destinatarios = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Administrador de Accesos', 'Seguridad Informática']))
            ->get();

        if ($destinatarios->isEmpty()) {
            $destinatarios = User::permission('sigua.cruces')->get();
        }
        if ($destinatarios->isEmpty()) {
            $destinatarios = User::permission('sigua.dashboard')->limit(10)->get();
        }

        $enviados = 0;
        foreach ($destinatarios as $user) {
            try {
                $user->notify(new GhostUserAlert($cruce, $fantasmas));
                $enviados++;
            } catch (\Throwable $e) {
                Log::error('GhostUserAlert failed', [
                    'user_id' => $user->id,
                    'cruce_id' => $cruce->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        try {
            AuditLog::create([
                'user_id' => null,
                'auditable_type' => Cruce::class,
                'auditable_id' => $cruce->id,
                'action' => 'ghost_alert_sent',
                'old_values' => null,
                'new_values' => [
                    'cantidad_fantasmas' => $fantasmas->count(),
                    'destinatarios_enviados' => $enviados,
                    'destinatarios_total' => $destinatarios->count(),
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLog ghost_alert_sent failed', ['cruce_id' => $cruce->id, 'exception' => $e->getMessage()]);
        }
    }

    /**
     * Ejecuta cruce solo para un sistema.
     *
     * @throws SiguaException
     */
    public function ejecutarCruceIndividual(int $sistemaId, int $ejecutadoPorUserId): Cruce
    {
        $sistema = Sistema::where('id', $sistemaId)->where('activo', true)->first();
        if (! $sistema) {
            throw new SiguaException('Sistema no encontrado o inactivo.');
        }
        $cruce = $this->ejecutarCruceCompleto([$sistemaId], $ejecutadoPorUserId);
        $cruce->update([
            'tipo_cruce' => 'individual',
            'nombre' => 'Cruce individual ' . $sistema->name . ' ' . now()->format('Y-m-d H:i'),
        ]);
        return $cruce->fresh(['resultados']);
    }

    /**
     * Resumen del cruce: stats por categoría, por sistema, por sede.
     *
     * @return array{categorias: array, por_sistema: array, por_sede: array, total: int}
     */
    public function obtenerResumenCruce(int $cruceId): array
    {
        $cruce = Cruce::with('resultados')->findOrFail($cruceId);
        $resultados = $cruce->resultados;

        $categorias = [];
        $porSistema = [];
        $porSede = [];

        foreach ($resultados as $r) {
            $categorias[$r->categoria] = ($categorias[$r->categoria] ?? 0) + 1;
            $sede = $r->sede ?? 'Sin sede';
            $porSede[$sede] = ($porSede[$sede] ?? 0) + 1;
            $porSist = $r->resultados_por_sistema ?? [];
            foreach ($porSist as $ent) {
                $slug = $ent['slug'] ?? $ent['sistema_id'] ?? 'sistema';
                $porSistema[$slug] = ($porSistema[$slug] ?? 0) + 1;
            }
        }

        return [
            'categorias' => $categorias,
            'por_sistema' => $porSistema,
            'por_sede' => $porSede,
            'total' => $resultados->count(),
        ];
    }

    /**
     * Compara este cruce con el anterior: nuevas anomalías, resueltas, sin cambio.
     *
     * @return array{anomalias_nuevas: array, resueltas: array, sin_cambio: array, cruce_anterior_id: int|null}
     */
    public function compararConCruceAnterior(int $cruceId): array
    {
        $cruce = Cruce::with('resultados')->findOrFail($cruceId);
        $anterior = Cruce::where('id', '<', $cruceId)->orderByDesc('id')->first();
        if (! $anterior) {
            return [
                'anomalias_nuevas' => $cruce->resultados->where('requiere_accion', true)->values()->all(),
                'resueltas' => [],
                'sin_cambio' => [],
                'cruce_anterior_id' => null,
            ];
        }

        $anterior->load('resultados');
        $clavesAnterior = $this->clavesResultadoParaComparacion($anterior->resultados);
        $clavesActual = $this->clavesResultadoParaComparacion($cruce->resultados);

        $requierenAccionActual = $cruce->resultados->filter(fn ($r) => $r->requiere_accion);
        $requerianAccionAnterior = $anterior->resultados->filter(fn ($r) => $r->requiere_accion)->keyBy(fn ($r) => $this->claveResultado($r));

        $anomaliasNuevas = [];
        $resueltas = [];
        $sinCambio = [];

        foreach ($requierenAccionActual as $r) {
            $clave = $this->claveResultado($r);
            if (! isset($clavesAnterior[$clave])) {
                $anomaliasNuevas[] = $r;
            } elseif (isset($requerianAccionAnterior[$clave])) {
                $sinCambio[] = $r;
            }
        }
        foreach ($requerianAccionAnterior as $r) {
            $clave = $this->claveResultado($r);
            if (! isset($clavesActual[$clave]) || ! $requierenAccionActual->first(fn ($x) => $this->claveResultado($x) === $clave)) {
                continue;
            }
            $actual = $requierenAccionActual->first(fn ($x) => $this->claveResultado($x) === $clave);
            if (! $actual || ! $actual->requiere_accion) {
                $resueltas[] = $r;
            }
        }

        return [
            'anomalias_nuevas' => $anomaliasNuevas,
            'resueltas' => $resueltas,
            'sin_cambio' => $sinCambio,
            'cruce_anterior_id' => $anterior->id,
        ];
    }

    // --------------- Lógica por campaña y evaluación ---------------

    /**
     * Para cada sistema, indica si aplica a "todos" (null) o solo a campaign_ids listados.
     *
     * @return array<int, array{all: bool, campaign_ids: array<int>}>
     */
    protected function obtenerSistemasAplicanPorCampana(array $sistemaIds): array
    {
        $out = [];
        foreach ($sistemaIds as $sid) {
            $conNull = CuentaGenerica::where('system_id', $sid)->whereNull('campaign_id')->exists();
            if ($conNull) {
                $out[$sid] = ['all' => true, 'campaign_ids' => []];
                continue;
            }
            $campaignIds = CuentaGenerica::where('system_id', $sid)->whereNotNull('campaign_id')->distinct()->pluck('campaign_id')->all();
            $out[$sid] = ['all' => count($campaignIds) > 1, 'campaign_ids' => array_values($campaignIds)];
        }
        return $out;
    }

    protected function sistemaAplicaAEmpleado(int $sistemaId, ?int $empleadoCampaignId, array $aplicaPorSistema): bool
    {
        $cfg = $aplicaPorSistema[$sistemaId] ?? ['all' => true, 'campaign_ids' => []];
        if ($cfg['all']) {
            return true;
        }
        if ($empleadoCampaignId === null) {
            return true;
        }
        return in_array($empleadoCampaignId, $cfg['campaign_ids'], true);
    }

    /**
     * Evalúa un empleado activo y devuelve array para CruceResultado.
     *
     * @return array{categoria: string, requiere_accion: bool, accion_sugerida: string, resultados_por_sistema: array, ...}
     */
    protected function evaluarEmpleado(EmpleadoRh $empleado, Collection $sistemas, array $aplicaPorSistema): array
    {
        $cuentasPorSistema = $empleado->cuentas->groupBy('system_id');
        $resultadosPorSistema = [];
        $sedeEmpleado = $empleado->sede;
        $sedeEmpleadoNombre = $sedeEmpleado?->name ?? $sedeEmpleado?->code ?? null;
        $campanaEmpleado = $empleado->campaign?->name ?? null;

        foreach ($sistemas as $sistema) {
            $cuentas = $cuentasPorSistema->get($sistema->id, collect());
            $deberiaTener = $this->sistemaAplicaAEmpleado($sistema->id, $empleado->campaign_id, $aplicaPorSistema);
            $entrada = [
                'sistema_id' => $sistema->id,
                'slug' => $sistema->slug,
                'tiene_cuenta' => $cuentas->isNotEmpty(),
                'identificador' => $cuentas->first()?->usuario_cuenta,
                'tipo' => $cuentas->first()?->tipo ?? null,
                'estado' => $cuentas->first()?->estado ?? null,
                'datos_extra_relevantes' => $cuentas->first()?->datos_extra ?? null,
            ];
            if ($cuentas->count() > 1 && $cuentas->where('tipo', 'nominal')->count() > 1) {
                $entrada['duplicados'] = $cuentas->pluck('usuario_cuenta')->all();
            }
            $primera = $cuentas->first();
            if ($primera && $sedeEmpleadoNombre && $primera->sede_id !== $empleado->sede_id) {
                $entrada['sede_cuenta'] = $primera->sede?->name ?? $primera->sede_id;
                $entrada['sede_empleado'] = $sedeEmpleadoNombre;
                $entrada['anomalia_sede'] = true;
            }
            $resultadosPorSistema[] = $entrada;
        }

        $tieneAnomaliaSede = collect($resultadosPorSistema)->contains(fn ($e) => ($e['anomalia_sede'] ?? false));
        $tieneDuplicados = collect($resultadosPorSistema)->contains(fn ($e) => ! empty($e['duplicados']));

        if ($tieneDuplicados || $tieneAnomaliaSede) {
            $accion = $tieneDuplicados ? 'Revisar cuentas duplicadas en el mismo sistema.' : 'Verificar asignación de sede entre empleado y cuenta(s).';
            return [
                'empleado_rh_id' => $empleado->id,
                'num_empleado' => $empleado->num_empleado,
                'nombre_empleado' => $empleado->nombre_completo,
                'sede' => $sedeEmpleadoNombre,
                'campana' => $campanaEmpleado,
                'resultados_por_sistema' => $resultadosPorSistema,
                'categoria' => 'anomalia',
                'requiere_accion' => true,
                'accion_sugerida' => $accion,
            ];
        }

        $sistemasDondeDebe = $sistemas->filter(fn ($s) => $this->sistemaAplicaAEmpleado($s->id, $empleado->campaign_id, $aplicaPorSistema));
        $faltaEnAlguno = false;
        $tieneNominalActivaEnAlguno = false;
        $tieneGenericaConCa01 = false;
        $tieneGenericaSinCa01 = false;

        foreach ($sistemasDondeDebe as $s) {
            $cuentas = $cuentasPorSistema->get($s->id, collect());
            if ($cuentas->isEmpty()) {
                $faltaEnAlguno = true;
                break;
            }
            $c = $cuentas->first();
            if ($c->tipo === 'nominal' && $c->estado === 'activa') {
                $tieneNominalActivaEnAlguno = true;
            }
            if ($c->tipo === 'generica') {
                $tieneCa01 = $c->formatosCA01->where('estado', 'vigente')->isNotEmpty();
                if ($tieneCa01) {
                    $tieneGenericaConCa01 = true;
                } else {
                    $tieneGenericaSinCa01 = true;
                }
            }
        }

        if ($faltaEnAlguno) {
            return [
                'empleado_rh_id' => $empleado->id,
                'num_empleado' => $empleado->num_empleado,
                'nombre_empleado' => $empleado->nombre_completo,
                'sede' => $sedeEmpleadoNombre,
                'campana' => $campanaEmpleado,
                'resultados_por_sistema' => $resultadosPorSistema,
                'categoria' => 'sin_cuenta_sistema',
                'requiere_accion' => true,
                'accion_sugerida' => 'Asignar o crear cuenta en el(los) sistema(s) donde aplica su campaña.',
            ];
        }

            if ($tieneGenericaSinCa01) {
                return [
                'empleado_rh_id' => $empleado->id,
                'num_empleado' => $empleado->num_empleado,
                'nombre_empleado' => $empleado->nombre_completo,
                'sede' => $sedeEmpleadoNombre,
                'campana' => $campanaEmpleado,
                'resultados_por_sistema' => $resultadosPorSistema,
                'categoria' => 'generico_sin_responsable',
                'requiere_accion' => true,
                'accion_sugerida' => 'Registrar CA-01 vigente para la cuenta genérica o asignar cuenta nominal.',
            ];
        }

        // Tiene cuenta en todos los sistemas que aplican; solo genéricas con CA-01 (ninguna nominal activa ni genérica sin CA-01)
        if (! $faltaEnAlguno && $tieneGenericaConCa01 && ! $tieneNominalActivaEnAlguno && ! $tieneGenericaSinCa01) {
            return [
                'empleado_rh_id' => $empleado->id,
                'num_empleado' => $empleado->num_empleado,
                'nombre_empleado' => $empleado->nombre_completo,
                'sede' => $sedeEmpleadoNombre,
                'campana' => $campanaEmpleado,
                'resultados_por_sistema' => $resultadosPorSistema,
                'categoria' => 'generico_con_responsable',
                'requiere_accion' => false,
                'accion_sugerida' => null,
            ];
        }

        // Tiene cuenta nominal activa (o mix nominal + genérica con CA-01) en todos los sistemas que aplican
        return [
            'empleado_rh_id' => $empleado->id,
            'num_empleado' => $empleado->num_empleado,
            'nombre_empleado' => $empleado->nombre_completo,
            'sede' => $sedeEmpleadoNombre,
            'campana' => $campanaEmpleado,
            'resultados_por_sistema' => $resultadosPorSistema,
            'categoria' => 'ok_completo',
            'requiere_accion' => false,
            'accion_sugerida' => null,
        ];
    }

    protected function evaluarEmpleadoBaja(EmpleadoRh $empleado, Collection $sistemas): array
    {
        $cuentasPorSistema = $empleado->cuentas->groupBy('system_id');
        $resultadosPorSistema = [];
        $sedeNombre = $empleado->sede?->name ?? $empleado->sede?->code ?? null;
        $campanaNombre = $empleado->campaign?->name ?? null;

        foreach ($sistemas as $sistema) {
            $cuentas = $cuentasPorSistema->get($sistema->id, collect());
            $primera = $cuentas->first();
            $resultadosPorSistema[] = [
                'sistema_id' => $sistema->id,
                'slug' => $sistema->slug,
                'tiene_cuenta' => $cuentas->isNotEmpty(),
                'identificador' => $primera?->usuario_cuenta,
                'tipo' => $primera?->tipo ?? null,
                'estado' => $primera?->estado ?? null,
                'datos_extra_relevantes' => $primera?->datos_extra ?? null,
            ];
        }

        return [
            'empleado_rh_id' => $empleado->id,
            'num_empleado' => $empleado->num_empleado,
            'nombre_empleado' => $empleado->nombre_completo,
            'sede' => $sedeNombre,
            'campana' => $campanaNombre,
            'resultados_por_sistema' => $resultadosPorSistema,
            'categoria' => 'cuenta_baja_pendiente',
            'prioridad' => 'CRITICA',
            'requiere_accion' => true,
            'requiere_notificacion_inmediata' => true,
            'accion_sugerida' => 'Revocación inmediata por baja de RH.',
        ];
    }

    protected function resultadoCuentaSinRh(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => $cuenta->tipo,
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => null,
            'num_empleado' => null,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'cuenta_sin_rh',
            'requiere_accion' => true,
            'accion_sugerida' => 'Verificar con RH y vincular empleado o marcar como genérica/servicio.',
        ];
    }

    /**
     * Regla 2: Externo con CA-01 vigente (operación controlada).
     */
    protected function resultadoExternoConJustificacion(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => 'externo',
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => null,
            'num_empleado' => null,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? $cuenta->empresa_cliente ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'externo_con_justificacion',
            'prioridad' => null,
            'requiere_accion' => false,
            'accion_sugerida' => null,
        ];
    }

    /**
     * Regla 2: Externo sin CA-01 vigente (prioridad MEDIA).
     */
    protected function resultadoExternoSinJustificacion(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => 'externo',
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => null,
            'num_empleado' => null,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? $cuenta->empresa_cliente ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'externo_sin_justificacion',
            'prioridad' => 'MEDIA',
            'requiere_accion' => true,
            'accion_sugerida' => 'Vincular CA-01 y responsable interno.',
        ];
    }

    /**
     * Regla 3: Cuenta activa sin cruce con RH, no genérica ni externa (por clasificar).
     */
    protected function resultadoPorClasificar(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => $cuenta->tipo,
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => null,
            'num_empleado' => null,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'por_clasificar',
            'prioridad' => 'ALTA',
            'requiere_accion' => true,
            'accion_sugerida' => 'Solicitar identificación y formato CA-01 al administrador del sistema origen.',
        ];
    }

    /**
     * Genérica sin empleado pero con CA-01 vigente: sin anomalía (operación controlada ISO 27001).
     */
    protected function resultadoGenericoConResponsable(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => 'generica',
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => $cuenta->empleado_rh_id,
            'num_empleado' => $cuenta->empleadoRh?->num_empleado,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'generico_con_responsable',
            'requiere_accion' => false,
            'accion_sugerida' => null,
        ];
    }

    /**
     * Genérica sin empleado y sin CA-01 vigente (o vencido): anomalía que exige formato CA-01 y responsable.
     */
    protected function resultadoGenericaSinJustificacion(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => 'generica',
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => $cuenta->empleado_rh_id,
            'num_empleado' => $cuenta->empleadoRh?->num_empleado,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'generica_sin_justificacion',
            'requiere_accion' => true,
            'accion_sugerida' => 'Generar/Renovar formato CA-01 para esta cuenta genérica e identificar al supervisor responsable.',
        ];
    }

    protected function resultadoGenericoSinResponsable(CuentaGenerica $cuenta, Collection $sistemas): array
    {
        $entrada = [
            'sistema_id' => $cuenta->system_id,
            'slug' => $cuenta->sistema?->slug ?? $cuenta->system_id,
            'tiene_cuenta' => true,
            'identificador' => $cuenta->usuario_cuenta,
            'tipo' => 'generica',
            'estado' => $cuenta->estado,
            'datos_extra_relevantes' => $cuenta->datos_extra,
        ];
        return [
            'empleado_rh_id' => $cuenta->empleado_rh_id,
            'num_empleado' => $cuenta->empleadoRh?->num_empleado,
            'nombre_empleado' => $cuenta->nombre_cuenta,
            'sede' => $cuenta->sede?->name ?? $cuenta->sede?->code ?? null,
            'campana' => $cuenta->campaign?->name ?? null,
            'resultados_por_sistema' => [$entrada],
            'categoria' => 'generico_sin_responsable',
            'requiere_accion' => true,
            'accion_sugerida' => 'Registrar CA-01 vigente para la cuenta genérica o asignar cuenta nominal.',
        ];
    }

    protected function crearResultado(Cruce $cruce, array $res): void
    {
        CruceResultado::create([
            'cruce_id' => $cruce->id,
            'empleado_rh_id' => $res['empleado_rh_id'] ?? null,
            'num_empleado' => $res['num_empleado'] ?? null,
            'nombre_empleado' => $res['nombre_empleado'] ?? null,
            'sede' => $res['sede'] ?? null,
            'campana' => $res['campana'] ?? null,
            'resultados_por_sistema' => $res['resultados_por_sistema'] ?? [],
            'categoria' => $res['categoria'],
            'prioridad' => $res['prioridad'] ?? null,
            'requiere_accion' => $res['requiere_accion'] ?? false,
            'requiere_notificacion_inmediata' => $res['requiere_notificacion_inmediata'] ?? false,
            'accion_sugerida' => $res['accion_sugerida'] ?? null,
        ]);
    }

    protected function claveResultado(CruceResultado $r): string
    {
        $emp = $r->empleado_rh_id ?? 'n';
        $num = $r->num_empleado ?? $r->nombre_empleado ?? '';
        return (string) $emp . '|' . $num;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, CruceResultado>  $resultados
     * @return array<string, true>
     */
    protected function clavesResultadoParaComparacion($resultados): array
    {
        $out = [];
        foreach ($resultados as $r) {
            $out[$this->claveResultado($r)] = true;
        }
        return $out;
    }

    // --------------- Retrocompatibilidad (v1) ---------------

    /**
     * @deprecated Use ejecutarCruceCompleto(null, $userId) o ejecutarCruceIndividual
     */
    public function cruceRhVsAd(int $importacionRhId, int $importacionAdId): array
    {
        throw new SiguaException('Use ejecutarCruceCompleto o ejecutarCruceIndividual con el sistema AD.');
    }

    /**
     * @deprecated Use ejecutarCruceCompleto o ejecutarCruceIndividual
     */
    public function cruceRhVsNeotel(int $importacionRhId, int $importacionNeotelId): array
    {
        throw new SiguaException('Use ejecutarCruceCompleto o ejecutarCruceIndividual con el sistema Neotel.');
    }

    /**
     * @deprecated Use ejecutarCruceCompleto(null, $userId)
     */
    public function cruceCompleto(int $ejecutadoPorUserId): array
    {
        $cruce = $this->ejecutarCruceCompleto(null, $ejecutadoPorUserId);
        return ['cruce' => $cruce];
    }

    /**
     * Guarda el resultado de un cruce en sigua_cross_matches (legacy).
     */
    public function guardarResultado(
        string $tipoCruce,
        array $resultadoJson,
        int $ejecutadoPorUserId,
        ?int $importId = null
    ): Cruce {
        $coincidenciasArr = $resultadoJson['coincidencias'] ?? [];
        $coincidenciasCount = $resultadoJson['coincidencias_count'] ?? count($coincidenciasArr);
        $total = $resultadoJson['total_analizados'] ?? 0;
        $sinMatch = $resultadoJson['sin_match_count'] ?? $resultadoJson['sin_match'] ?? max(0, $total - $coincidenciasCount);

        return Cruce::create([
            'import_id' => $importId,
            'tipo_cruce' => $tipoCruce,
            'fecha_ejecucion' => now(),
            'total_analizados' => $total,
            'coincidencias' => $coincidenciasCount,
            'sin_match' => $sinMatch,
            'resultado_json' => $resultadoJson,
            'ejecutado_por' => $ejecutadoPorUserId,
        ]);
    }
}
