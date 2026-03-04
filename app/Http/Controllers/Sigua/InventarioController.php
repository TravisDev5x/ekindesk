<?php

namespace App\Http\Controllers\Sigua;

use App\Http\Controllers\Controller;
use App\Models\Sigua\CuentaGenerica;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Explorador Maestro: inventario global de cuentas con estado de auditoría derivado.
 * Paginación eficiente para 1,000+ registros.
 */
class InventarioController extends Controller
{
    private const ESTADOS_AUDITORIA = ['match', 'fantasma', 'por_clasificar', 'externo_ok', 'externo'];

    /**
     * GET /api/sigua/inventario
     * Parámetros: search, sistema_id, estado_auditoria, per_page (default 50), page.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->can('sigua.cuentas.view')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $perPage = min((int) $request->input('per_page', 50), 100);
        $search = $request->input('search');
        $sistemaId = $request->filled('sistema_id') ? (int) $request->input('sistema_id') : null;
        $estadoAuditoria = $request->input('estado_auditoria');

        $query = CuentaGenerica::query()
            ->with([
                'sistema:id,name,slug',
                'empleadoRh:id,num_empleado,nombre_completo,estatus',
                'formatosCA01' => fn ($q) => $q->where('sigua_ca01.estado', 'vigente')
                    ->whereDate('sigua_ca01.fecha_vencimiento', '>=', Carbon::today()),
            ])
            ->select([
                'id',
                'system_id',
                'usuario_cuenta',
                'nombre_cuenta',
                'estado',
                'tipo',
                'empleado_rh_id',
            ]);

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('usuario_cuenta', 'like', $term)
                    ->orWhere('nombre_cuenta', 'like', $term);
            });
        }

        if ($sistemaId !== null) {
            $query->where('system_id', $sistemaId);
        }

        $paginator = $query->orderBy('system_id')->orderBy('usuario_cuenta')->paginate($perPage);
        $items = $paginator->getCollection()->map(fn (CuentaGenerica $c) => $this->mapToInventarioRow($c))->values()->all();

        if ($estadoAuditoria !== null && $estadoAuditoria !== '' && in_array($estadoAuditoria, self::ESTADOS_AUDITORIA, true)) {
            $items = array_values(array_filter($items, fn ($row) => $row['estado_auditoria'] === $estadoAuditoria));
        }

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'OK',
        ]);
    }

    private function mapToInventarioRow(CuentaGenerica $c): array
    {
        $empleado = $c->empleadoRh;
        $sistema = $c->sistema;
        $tieneCa01Vigente = $c->formatosCA01->isNotEmpty();

        $estadoAuditoria = $this->derivarEstadoAuditoria($c, $empleado, $tieneCa01Vigente);
        $ca01Status = $this->derivarCa01Status($c, $tieneCa01Vigente);

        return [
            'id' => $c->id,
            'sistema' => $sistema ? ['id' => $sistema->id, 'name' => $sistema->name, 'slug' => $sistema->slug] : null,
            'cuenta_usuario' => $c->usuario_cuenta,
            'nombre_en_sistema' => $c->nombre_cuenta,
            'nombre_rh' => $empleado ? $empleado->nombre_completo : null,
            'tipo_cuenta' => $c->tipo ?? 'desconocida',
            'estado_auditoria' => $estadoAuditoria,
            'ca01_status' => $ca01Status,
            'estado' => $c->estado,
        ];
    }

    private function derivarEstadoAuditoria(CuentaGenerica $c, $empleado, bool $tieneCa01Vigente): string
    {
        if ($c->estado === 'activa' && $c->empleado_rh_id && $empleado && in_array($empleado->estatus, ['Baja', 'Baja probable'], true)) {
            return 'fantasma';
        }
        if ($c->tipo === 'externo') {
            return $tieneCa01Vigente ? 'externo_ok' : 'externo';
        }
        if ($c->tipo === 'desconocida') {
            return 'por_clasificar';
        }
        if ($c->estado === 'activa' && ! $c->empleado_rh_id && ! in_array($c->tipo, ['generica', 'servicio', 'externo'], true)) {
            return 'por_clasificar';
        }
        return 'match';
    }

    private function derivarCa01Status(CuentaGenerica $c, bool $tieneCa01Vigente): string
    {
        if ($tieneCa01Vigente) {
            return 'vigente';
        }
        $tieneAlgunCa01 = DB::table('sigua_ca01_accounts')->where('account_id', $c->id)->exists();
        return $tieneAlgunCa01 ? 'vencido' : 'faltante';
    }

    /**
     * GET /api/sigua/inventario/exportar
     * Mismos filtros que index (search, sistema_id, estado_auditoria). Descarga CSV.
     */
    public function exportar(Request $request): StreamedResponse|JsonResponse
    {
        if (! $request->user()?->can('sigua.cuentas.view')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $search = $request->input('search');
        $sistemaId = $request->filled('sistema_id') ? (int) $request->input('sistema_id') : null;
        $estadoAuditoria = $request->input('estado_auditoria');

        $query = CuentaGenerica::query()
            ->with([
                'sistema:id,name,slug',
                'empleadoRh:id,num_empleado,nombre_completo,estatus',
                'formatosCA01' => fn ($q) => $q->where('sigua_ca01.estado', 'vigente')
                    ->whereDate('sigua_ca01.fecha_vencimiento', '>=', Carbon::today()),
            ])
            ->select(['id', 'system_id', 'usuario_cuenta', 'nombre_cuenta', 'estado', 'tipo', 'empleado_rh_id']);

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('usuario_cuenta', 'like', $term)->orWhere('nombre_cuenta', 'like', $term);
            });
        }
        if ($sistemaId !== null) {
            $query->where('system_id', $sistemaId);
        }

        $cuentas = $query->orderBy('system_id')->orderBy('usuario_cuenta')->get();
        $rows = $cuentas->map(fn (CuentaGenerica $c) => $this->mapToInventarioRow($c));
        if ($estadoAuditoria !== null && $estadoAuditoria !== '' && in_array($estadoAuditoria, self::ESTADOS_AUDITORIA, true)) {
            $rows = $rows->filter(fn ($row) => $row['estado_auditoria'] === $estadoAuditoria);
        }

        $filename = 'sigua_inventario_' . date('Y-m-d_His') . '.csv';
        $headers = ['Sistema', 'Usuario', 'Nombre en sistema', 'Nombre RH', 'Tipo', 'Estado auditoría', 'CA-01', 'Estado'];

        if (ob_get_length() > 0) {
            ob_end_clean();
        }
        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['sistema']['name'] ?? '',
                    $r['cuenta_usuario'],
                    $r['nombre_en_sistema'],
                    $r['nombre_rh'] ?? '',
                    $r['tipo_cuenta'],
                    $r['estado_auditoria'],
                    $r['ca01_status'],
                    $r['estado'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
