<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\Database\SqlDialect;
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
 * Hub principal: métricas agregadas de RESOLBEB (tickets).
 */
class MainDashboardController extends Controller
{
    public function getHubSummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $resolbeb = $this->resolbebMetrics($user);
        $atencionInmediata = $this->atencionInmediata($user, $resolbeb);

        return response()->json([
            'resolbeb' => $resolbeb,
            'atencion_inmediata' => $atencionInmediata,
            'agentes_disponibles' => $this->agentesDisponibles(),
        ]);
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
                        ->whereRaw(SqlDialect::createdAtPlusHoursLte('created_at', Ticket::SLA_LIMIT_HOURS), [now()]);
                });
            })
            ->count();

        $hoyInicio = Carbon::today()->startOfDay();
        $hoyFin = Carbon::today()->endOfDay();
        $mttrHoy = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$hoyInicio, $hoyFin])
            ->selectRaw(SqlDialect::avgHoursBetween('created_at', 'resolved_at').' as avg_hours')
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
     * Top 5 riesgos para "Atención Inmediata" (tickets).
     */
    private function atencionInmediata($user, array $resolbeb): array
    {
        $items = [];

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
                ->orderBy(SqlDialect::coalesceDueOrSlaDeadline('due_at', 'created_at', Ticket::SLA_LIMIT_HOURS))
                ->limit(5)
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
