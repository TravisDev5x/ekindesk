<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketState;
use App\Models\TicketType;
use App\Models\User;
use App\Policies\TicketPolicy;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard operativo RESOLBEB: KPIs, balance de carga, tendencia y tickets críticos.
 * Filtros: assigned_user_id (agente), sede_id (sede).
 */
class ResolbebController extends Controller
{
    /** Estados considerados "abiertos/en proceso" (no finales). */
    private function openStateIds(): \Illuminate\Support\Collection
    {
        return TicketState::where('is_final', false)->pluck('id');
    }

    /** IDs de estados "Esperando Proveedor" o "Pausado" (por nombre). */
    private function frozenStateIds(): \Illuminate\Support\Collection
    {
        return TicketState::where(function ($q) {
            $q->where('name', 'like', '%Esperando Proveedor%')
                ->orWhere('name', 'like', '%Pausado%')
                ->orWhereIn('code', ['esperando_proveedor', 'pausado', 'en_espera']);
        })->pluck('id');
    }

    /**
     * Dashboard operativo: KPIs, balance de carga, tendencia, top incidentes y top 5 críticos.
     * Filtros opcionales: sede_id, assigned_user_id.
     */
    public function dashboardOperativo(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        if (! $user->can('tickets.manage_all') && $user->can('tickets.view_area') && ! $user->area_id) {
            return response()->json(['message' => 'Asigna tu área para acceder al dashboard'], 403);
        }

        Gate::authorize('viewAny', Ticket::class);

        $policy = app(TicketPolicy::class);
        $base = $policy->scopeFor($user, Ticket::query());
        $this->applyFilters($request, $user, $base);

        $finalStateIds = TicketState::where('is_final', true)->pluck('id');
        $openIds = $this->openStateIds();
        $frozenIds = $this->frozenStateIds();
        $baseNoFinal = (clone $base)->whereNotIn('ticket_state_id', $finalStateIds);

        // --- KPIs ---
        $vencidosHoy = (clone $baseNoFinal)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereNotNull('due_at')->where('due_at', '<=', now());
                })->orWhere(function ($q2) {
                    $q2->whereNull('due_at')
                        ->whereRaw('DATE_ADD(created_at, INTERVAL ? HOUR) <= ?', [Ticket::SLA_LIMIT_HOURS, now()]);
                });
            })
            ->count();

        $sinAsignar = (clone $baseNoFinal)->whereNull('assigned_user_id')->count();

        $congelados = 0;
        if ($frozenIds->isNotEmpty()) {
            $congelados = (clone $baseNoFinal)
                ->whereIn('ticket_state_id', $frozenIds)
                ->where('updated_at', '<=', now()->subHours(48))
                ->count();
        }

        $semanaInicio = Carbon::now()->startOfWeek();
        $semanaFin = Carbon::now()->endOfWeek();
        $mttrSemanal = (clone $base)
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$semanaInicio, $semanaFin])
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) / 3600 as avg_hours')
            ->value('avg_hours');
        $mttrSemanal = $mttrSemanal !== null ? round((float) $mttrSemanal, 1) : null;

        // --- Balance de carga ---
        $balanceCarga = (clone $base)
            ->whereIn('ticket_state_id', $openIds)
            ->select('assigned_user_id', DB::raw('count(*) as total'))
            ->groupBy('assigned_user_id')
            ->orderByDesc('total')
            ->get();
        $userIds = $balanceCarga->pluck('assigned_user_id')->filter()->unique()->values()->all();
        $userNames = $userIds ? User::whereIn('id', $userIds)->pluck('name', 'id')->all() : [];
        $balanceCargaData = $balanceCarga->map(function ($row) use ($userNames) {
            $name = $row->assigned_user_id ? ($userNames[$row->assigned_user_id] ?? 'Usuario #'.$row->assigned_user_id) : 'Sin asignar';
            return ['agente' => $name, 'total' => (int) $row->total];
        })->values()->all();

        // --- Tendencia: últimos 15 días ---
        $desde = Carbon::today()->subDays(14)->startOfDay();
        $tendencia = [];
        for ($i = 0; $i < 15; $i++) {
            $dia = $desde->copy()->addDays($i);
            $diaInicio = $dia->copy()->startOfDay();
            $diaFin = $dia->copy()->endOfDay();
            $creados = (clone $base)->whereBetween('created_at', [$diaInicio, $diaFin])->count();
            $cerrados = (clone $base)->whereNotNull('resolved_at')->whereBetween('resolved_at', [$diaInicio, $diaFin])->count();
            $tendencia[] = [
                'fecha' => $dia->format('Y-m-d'),
                'etiqueta' => $dia->locale('es')->dayName.' '.$dia->format('d/m'),
                'creados' => $creados,
                'cerrados' => $cerrados,
            ];
        }

        // --- Top incidentes por categoría (tipo de ticket) ---
        $topIncidentesPorTipo = (clone $base)
            ->whereIn('ticket_state_id', $openIds)
            ->select('ticket_type_id', DB::raw('count(*) as total'))
            ->groupBy('ticket_type_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $typeIds = $topIncidentesPorTipo->pluck('ticket_type_id')->filter()->unique()->values()->all();
        $typeNames = $typeIds ? TicketType::whereIn('id', $typeIds)->pluck('name', 'id')->all() : [];
        $topIncidentes = $topIncidentesPorTipo->map(function ($row) use ($typeNames) {
            return [
                'categoria' => $row->ticket_type_id ? ($typeNames[$row->ticket_type_id] ?? 'Tipo #'.$row->ticket_type_id) : 'Sin tipo',
                'total' => (int) $row->total,
            ];
        })->values()->all();

        // --- Top 5 críticos: prioridad Alta/Urgente, más antiguos primero ---
        $prioridadAltaIds = DB::table('priorities')
            ->where(function ($q) {
                $q->where('name', 'like', '%Urgente%')->orWhere('name', 'like', '%Alta%');
            })
            ->orWhere('level', '<=', 2)
            ->pluck('id');
        $criticosQuery = (clone $base)
            ->whereNotIn('ticket_state_id', $finalStateIds)
            ->with(['state:id,name,code', 'priority:id,name', 'assignedUser:id,name', 'sede:id,name'])
            ->orderBy('created_at')
            ->limit(10);
        if ($prioridadAltaIds->isNotEmpty()) {
            $criticosQuery->whereIn('priority_id', $prioridadAltaIds);
        }
        $top5Criticos = $criticosQuery->get()->take(5)->map(function ($t) {
            return [
                'id' => $t->id,
                'subject' => $t->subject,
                'state' => $t->state ? ['name' => $t->state->name, 'code' => $t->state->code] : null,
                'priority' => $t->priority ? ['name' => $t->priority->name] : null,
                'assigned_user' => $t->assignedUser ? ['name' => $t->assignedUser->name] : null,
                'sede' => $t->sede ? ['name' => $t->sede->name] : null,
                'sla_status_text' => $t->sla_status_text,
                'is_overdue' => $t->is_overdue,
                'due_at' => $t->due_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ];
        })->values()->all();

        // --- Top 3 resolvers: agentes que más cerraron tickets en los últimos 30 días ---
        $ticketIds = (clone $base)->pluck('id');
        $resueltoCerradoIds = TicketState::whereIn('code', ['resuelto', 'cerrado'])->pluck('id');
        $topResolvers = [];
        if ($ticketIds->isNotEmpty() && $resueltoCerradoIds->isNotEmpty()) {
            $resolversRaw = TicketHistory::query()
                ->whereIn('ticket_id', $ticketIds)
                ->whereIn('ticket_state_id', $resueltoCerradoIds)
                ->where('created_at', '>=', now()->subDays(30))
                ->select('actor_id', DB::raw('count(*) as tickets_cerrados'))
                ->groupBy('actor_id')
                ->orderByDesc('tickets_cerrados')
                ->limit(3)
                ->get();
            $actorIds = $resolversRaw->pluck('actor_id')->filter()->unique()->values()->all();
            $actorNames = $actorIds ? User::whereIn('id', $actorIds)->pluck('name', 'id')->all() : [];
            $topResolvers = $resolversRaw->map(function ($row) use ($actorNames) {
                return [
                    'nombre' => $actorNames[$row->actor_id] ?? 'Usuario #'.$row->actor_id,
                    'tickets_cerrados' => (int) $row->tickets_cerrados,
                ];
            })->values()->all();
        }

        // --- Top sedes: tickets creados este mes por sede ---
        $mesInicio = Carbon::now()->startOfMonth();
        $mesFin = Carbon::now()->endOfMonth();
        $topSedesRaw = (clone $base)
            ->whereBetween('created_at', [$mesInicio, $mesFin])
            ->select('sede_id', DB::raw('count(*) as total'))
            ->groupBy('sede_id')
            ->orderByDesc('total')
            ->get();
        $sedeIds = $topSedesRaw->pluck('sede_id')->filter()->unique()->values()->all();
        $sedeNames = $sedeIds ? Sede::whereIn('id', $sedeIds)->pluck('name', 'id')->all() : [];
        $topSedes = $topSedesRaw->map(function ($row) use ($sedeNames) {
            return [
                'sede' => $row->sede_id ? ($sedeNames[$row->sede_id] ?? 'Sede #'.$row->sede_id) : 'Sin sede',
                'total' => (int) $row->total,
            ];
        })->values()->all();

        // --- Top fallas: agrupación por categoría/tipo (tickets creados este mes) ---
        $topFallasRaw = (clone $base)
            ->whereBetween('created_at', [$mesInicio, $mesFin])
            ->select('ticket_type_id', DB::raw('count(*) as total'))
            ->groupBy('ticket_type_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();
        $fallasTypeIds = $topFallasRaw->pluck('ticket_type_id')->filter()->unique()->values()->all();
        $fallasTypeNames = $fallasTypeIds ? TicketType::whereIn('id', $fallasTypeIds)->pluck('name', 'id')->all() : [];
        $topFallas = $topFallasRaw->map(function ($row) use ($fallasTypeNames) {
            return [
                'categoria' => $row->ticket_type_id ? ($fallasTypeNames[$row->ticket_type_id] ?? 'Tipo #'.$row->ticket_type_id) : 'Sin tipo',
                'total' => (int) $row->total,
            ];
        })->values()->all();

        // --- KPI reaperturas: tickets que pasaron de Resuelto/Cerrado a Abierto ---
        $abiertoId = TicketState::where('code', 'abierto')->value('id');
        $reaperturas = 0;
        $totalResueltos = 0;
        $porcentajeReapertura = 0;
        if ($abiertoId && $ticketIds->isNotEmpty() && $resueltoCerradoIds->isNotEmpty()) {
            $ticketsReabiertosIds = TicketHistory::query()
                ->whereIn('ticket_id', $ticketIds)
                ->where('ticket_state_id', $abiertoId)
                ->whereExists(function ($q) use ($resueltoCerradoIds) {
                    $q->select(DB::raw(1))
                        ->from('ticket_histories as h2')
                        ->whereColumn('h2.ticket_id', 'ticket_histories.ticket_id')
                        ->whereIn('h2.ticket_state_id', $resueltoCerradoIds)
                        ->whereColumn('h2.created_at', '<', 'ticket_histories.created_at');
                })
                ->select('ticket_id')
                ->distinct()
                ->pluck('ticket_id');
            $reaperturas = $ticketsReabiertosIds->count();
            $totalResueltos = TicketHistory::query()
                ->whereIn('ticket_id', $ticketIds)
                ->whereIn('ticket_state_id', $resueltoCerradoIds)
                ->select('ticket_id')
                ->distinct()
                ->count('ticket_id');
            $porcentajeReapertura = $totalResueltos > 0 ? round((float) ($reaperturas / $totalResueltos) * 100, 1) : 0;
        }
        $kpiReaperturas = [
            'cantidad_reaperturas' => $reaperturas,
            'total_resueltos' => $totalResueltos,
            'porcentaje' => $porcentajeReapertura,
        ];

        return response()->json([
            'kpis' => [
                'vencidos_hoy' => $vencidosHoy,
                'sin_asignar' => $sinAsignar,
                'congelados' => $congelados,
                'mttr_semanal' => $mttrSemanal,
            ],
            'kpi_reaperturas' => $kpiReaperturas,
            'balance_carga' => $balanceCargaData,
            'tendencia' => $tendencia,
            'top_incidentes' => $topIncidentes,
            'top_5_criticos' => $top5Criticos,
            'top_resolvers' => $topResolvers,
            'top_sedes' => $topSedes,
            'top_fallas' => $topFallas,
        ]);
    }

    protected function applyFilters(Request $request, $user, $query): void
    {
        $filters = [
            'area_current_id' => 'area_current_id',
            'area_origin_id' => 'area_origin_id',
            'sede_id' => 'sede_id',
            'ticket_type_id' => 'ticket_type_id',
            'priority_id' => 'priority_id',
            'ticket_state_id' => 'ticket_state_id',
        ];
        foreach ($filters as $param => $column) {
            if ($request->filled($param)) {
                if ($param === 'sede_id' && ! $user->can('tickets.filter_by_sede') && ! $user->can('tickets.manage_all')) {
                    continue;
                }
                $query->where($column, $request->input($param));
            }
        }
        if ($request->filled('assigned_user_id')) {
            $assigneeId = (int) $request->input('assigned_user_id');
            $allowed = $user->can('tickets.manage_all')
                || DB::table('users')->where('id', $assigneeId)->where('area_id', $user->area_id)->exists();
            if ($allowed) {
                $query->where('assigned_user_id', $assigneeId);
            }
        }
        if ($request->input('assigned_to') === 'me') {
            $query->where('assigned_user_id', $user->id);
        }
        if ($request->input('assigned_status') === 'unassigned') {
            $query->whereNull('assigned_user_id');
        }
    }
}
