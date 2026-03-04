<?php

namespace App\Http\Controllers\Sigua;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sigua\CuentaGenerica;
use App\Models\Sigua\FormatoCA01;
use App\Models\Sigua\Incidente;
use App\Models\Sigua\Sistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    private const MODELO_MAP = [
        'cuenta' => CuentaGenerica::class,
        'ca01' => FormatoCA01::class,
        'sistema' => Sistema::class,
        'incidente' => Incidente::class,
    ];

    private const MODELO_PERMISSION = [
        'cuenta' => 'sigua.cuentas.view',
        'ca01' => 'sigua.ca01.view',
        'sistema' => 'sigua.cuentas.view',
        'incidente' => 'sigua.incidentes.view',
    ];

    /**
     * Historial de auditoría para una entidad SIGUA (cuenta, ca01, sistema, incidente).
     * GET /api/sigua/auditoria/{modelo}/{id}
     */
    public function historial(Request $request, string $modelo, int $id): JsonResponse
    {
        $clase = self::MODELO_MAP[$modelo] ?? null;
        if ($clase === null) {
            return response()->json(['message' => 'Modelo de auditoría no válido'], 404);
        }

        $permission = self::MODELO_PERMISSION[$modelo] ?? 'sigua.dashboard';
        if (! $request->user()?->can($permission)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->where('auditable_type', $clase)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $logs]);
    }
}
