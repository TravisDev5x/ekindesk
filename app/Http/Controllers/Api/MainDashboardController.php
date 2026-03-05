<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketState;
use App\Models\User;
use App\Policies\TicketPolicy;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Hub principal: métricas agregadas de SIGUA (Accesos) y RESOLBEB (Tickets).
 * Integra datos de ambos módulos para la página de inicio.
 */
class MainDashboardController extends Controller
{
    public function getHubSummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $sigua = $this->siguaMetrics($user);
        $resolbeb = $this->resolbebMetrics($user);
        $atencionInmediata = $this->atencionInmediata($user, $sigua, $resolbeb);

        return response()->json([
            'sigua' => $sigua,
            'resolbeb' => $resolbeb,
            'atencion_inmediata' => $atencionInmediata,
            'agentes_disponibles' => $this->agentesDisponibles(),
        ]);
    }

    /**
     * Métricas SIGUA: total cuentas, % CA-01 firmados, alertas cuentas sin dueño.
     */
    private function siguaMetrics($user): array
    {
        if (! $user->can('sigua.dashboard') && ! $user->can('sigua.cuentas.view')) {
            return [
                'total_cuentas' => 0,
                'porcentaje_ca01_firmados' => 0,
                'alertas_cuentas_sin_dueño' => 0,
                'ca01_vigentes' => 0,
                'ca01_vencidos' => 0,
            ];
        }

        $cuentaModel = \App\Models\Sigua\CuentaGenerica::class;
        $ca01Model = \App\Models\Sigua\FormatoCA01::class;

        $totalCuentas = $cuentaModel::where('estado', 'activa')->count();
        $cuentasCumplen = $cuentaModel::where('estado', 'activa')
            ->where(function ($q) {
                $q->whereNotNull('empleado_rh_id')
                    ->orWhereHas('formatosCA01', fn ($q2) => $q2->where('sigua_ca01.estado', 'vigente'));
            })
            ->count();
        $porcentajeCa01 = $totalCuentas > 0
            ? round(min(100, $cuentasCumplen / $totalCuentas * 100), 1)
            : 100;

        $cuentasSinDueño = $cuentaModel::where('estado', 'activa')
            ->whereNull('empleado_rh_id')
            ->where('tipo', '!=', 'generica')
            ->count();

        $ca01Vigentes = $ca01Model::vigentes()->whereDate('fecha_vencimiento', '>=', Carbon::today())->count();
        $ca01Vencidos = $ca01Model::vencidos()->count();

        return [
            'total_cuentas' => $totalCuentas,
            'porcentaje_ca01_firmados' => $porcentajeCa01,
            'alertas_cuentas_sin_dueño' => $cuentasSinDueño,
            'ca01_vigentes' => $ca01Vigentes,
            'ca01_vencidos' => $ca01Vencidos,
        ];
    }

    /**
     * Métricas RESOLBEB: tickets abiertos, SLA vencido, MTTR hoy.
     */
    private function resolbebMetrics($user): array
    {
        if (! Gate::allows('viewAny', Ticket::class)) {
            return [
                'tickets_abiertos' => 0,
                'tickets_sla_vencido' => 0,
                'mttr_hoy' => null,
                'tickets_por_prioridad' => [],
            ];
        }

        $policy = app(TicketPolicy::class);
        $base = $policy->scopeFor($user, Ticket::query());

        $finalStateIds = TicketState::where('is_final', true)->pluck('id');
        $ticketsAbiertos = (clone $base)->whereNotIn('ticket_state_id', $finalStateIds)->count();

        $ticketsSlaVencido = (clone $base)
            ->whereNotIn('ticket_state_id', $finalStateIds)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('due_at')->where('due_at', '<=', now());
                })->orWhere(function ($q2) {
                    $q2->whereNull('due_at')
                        ->whereRaw('DATE_ADD(created_at, INTERVAL ? HOUR) <= ?', [Ticket::SLA_LIMIT_HOURS, now()]);
                });
            })
            ->count();

        $hoyInicio = Carbon::today()->startOfDay();
        $hoyFin = Carbon::today()->endOfDay();
        $mttrHoy = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$hoyInicio, $hoyFin])
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) / 3600 as avg_hours')
            ->value('avg_hours');
        $mttrHoy = $mttrHoy !== null ? round((float) $mttrHoy, 1) : null;

        $ticketsPorPrioridad = (clone $base)
            ->whereNotIn('ticket_state_id', $finalStateIds)
            ->select('priority_id', DB::raw('count(*) as total'))
            ->groupBy('priority_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $priorityIds = $ticketsPorPrioridad->pluck('priority_id')->filter()->unique()->values()->all();
        $priorityNames = $priorityIds ? DB::table('priorities')->whereIn('id', $priorityIds)->pluck('name', 'id')->all() : [];
        $ticketsPorPrioridad = $ticketsPorPrioridad->map(function ($row) use ($priorityNames) {
            return [
                'prioridad' => $priorityNames[$row->priority_id] ?? 'Sin prioridad',
                'total' => (int) $row->total,
            ];
        })->values()->all();

        return [
            'tickets_abiertos' => $ticketsAbiertos,
            'tickets_sla_vencido' => $ticketsSlaVencido,
            'mttr_hoy' => $mttrHoy,
            'tickets_por_prioridad' => $ticketsPorPrioridad,
        ];
    }

    /**
     * Top 5 riesgos para "Atención Inmediata" (mezcla accesos y tickets).
     */
    private function atencionInmediata($user, array $sigua, array $resolbeb): array
    {
        $items = [];

        if (($sigua['alertas_cuentas_sin_dueño'] ?? 0) > 0) {
            $items[] = [
                'tipo' => 'acceso',
                'titulo' => 'Cuentas sin vincular',
                'detalle' => $sigua['alertas_cuentas_sin_dueño'].' cuentas activas sin responsable asignado.',
                'severidad' => 'alta',
                'enlace' => '/sigua',
            ];
        }

        if (($resolbeb['tickets_sla_vencido'] ?? 0) > 0) {
            $items[] = [
                'tipo' => 'ticket',
                'titulo' => 'Tickets con SLA vencido',
                'detalle' => $resolbeb['tickets_sla_vencido'].' tickets superaron el tiempo de resolución.',
                'severidad' => 'critica',
                'enlace' => '/resolbeb',
            ];
        }

        $policy = app(TicketPolicy::class);
        if (Gate::allows('viewAny', Ticket::class)) {
            $base = $policy->scopeFor($user, Ticket::query());
            $finalStateIds = TicketState::where('is_final', true)->pluck('id');
            $criticos = (clone $base)
                ->whereNotIn('ticket_state_id', $finalStateIds)
                ->with(['priority:id,name', 'state:id,name'])
                ->orderByRaw('COALESCE(due_at, DATE_ADD(created_at, INTERVAL '.Ticket::SLA_LIMIT_HOURS.' HOUR)) ASC')
                ->limit(3)
                ->get();
            foreach ($criticos as $t) {
                $items[] = [
                    'tipo' => 'ticket',
                    'titulo' => '#'.str_pad((string) $t->id, 5, '0', STR_PAD_LEFT).' '.mb_substr($t->subject ?? '', 0, 40),
                    'detalle' => ($t->priority?->name ?? 'Sin prioridad').' · '.($t->state?->name ?? '—'),
                    'severidad' => $t->is_overdue ? 'critica' : 'media',
                    'enlace' => '/resolbeb/tickets/'.$t->id,
                ];
            }
        }

        if (($sigua['ca01_vencidos'] ?? 0) > 0) {
            $items[] = [
                'tipo' => 'acceso',
                'titulo' => 'Formatos CA-01 vencidos',
                'detalle' => $sigua['ca01_vencidos'].' formatos requieren renovación.',
                'severidad' => 'alta',
                'enlace' => '/sigua',
            ];
        }

        return array_slice(array_values($items), 0, 5);
    }

    private function agentesDisponibles(): int
    {
        return User::whereNotNull('area_id')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'inactive');
            })
            ->count();
    }
}
