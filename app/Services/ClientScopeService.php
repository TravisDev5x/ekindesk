<?php

namespace App\Services;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientScopeService
{
    /** Administración global: sin restricción por cliente. */
    public function bypassesClientScope(User $user): bool
    {
        return $user->can('tickets.manage_all');
    }

    /**
     * Asigna sede y cliente del usuario solicitante al crear un ticket.
     *
     * @return \Illuminate\Http\JsonResponse|null Error 422 o null si OK
     */
    public function stampTicketSiteFromUser(User $user, array &$data): ?\Illuminate\Http\JsonResponse
    {
        $user->loadMissing('sede:id,name,client_id');

        if (! $user->sede_id) {
            return response()->json([
                'message' => 'Debes tener una sede asignada para crear tickets. Contacta a tu administrador.',
            ], 422);
        }

        $data['sede_id'] = (int) $user->sede_id;
        $data['client_id'] = $user->sede->client_id ? (int) $user->sede->client_id : null;

        return null;
    }

    public function syncTicketClientFromSede(int $sedeId): ?int
    {
        $clientId = Sede::where('id', $sedeId)->value('client_id');

        return $clientId ? (int) $clientId : null;
    }

    public function resolveUserClientId(User $user): ?int
    {
        $user->loadMissing('sede:id,client_id');

        return $user->sede?->client_id ? (int) $user->sede->client_id : null;
    }

    /** Restringe tickets al cliente de la sede del usuario (salvo manage_all). */
    public function applyTicketScope(Builder $query, User $user): Builder
    {
        if ($this->bypassesClientScope($user)) {
            return $query;
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return $query->where('requester_id', $user->id);
        }

        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhere(function ($sub) use ($clientId) {
                    $this->whereTicketSedeInClient($sub, $clientId);
                });
        });
    }

    /** Restringe listado de usuarios al mismo cliente (salvo manage_all). */
    public function applyUserScope(Builder $query, User $user): Builder
    {
        if ($this->bypassesClientScope($user)) {
            return $query;
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return $query->where('users.id', $user->id);
        }

        return $query->whereIn('users.sede_id', $this->sedeIdsSubquery($clientId));
    }

    public function ticketVisibleToUser(User $user, \App\Models\Ticket $ticket): bool
    {
        if ($this->bypassesClientScope($user)) {
            return true;
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return (int) $ticket->requester_id === (int) $user->id;
        }

        if ($ticket->client_id) {
            return (int) $ticket->client_id === $clientId;
        }

        $ticket->loadMissing('sede:id,client_id');

        return $ticket->sede && (int) $ticket->sede->client_id === $clientId;
    }

    public function assertSedeAccessible(User $user, int $sedeId): bool
    {
        if ($this->bypassesClientScope($user)) {
            return Sede::where('id', $sedeId)->exists();
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return (int) $user->sede_id === $sedeId;
        }

        return Sede::where('id', $sedeId)->where('client_id', $clientId)->exists();
    }

    public function assertUserAccessible(User $user, int $targetUserId): bool
    {
        if ($this->bypassesClientScope($user)) {
            return true;
        }

        if ((int) $user->id === $targetUserId) {
            return true;
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return false;
        }

        return DB::table('users')
            ->where('id', $targetUserId)
            ->whereIn('sede_id', $this->sedeIdsSubquery($clientId))
            ->exists();
    }

    /** Filtro opcional client_id (validado contra el alcance del usuario). */
    public function applyClientFilter(Request $request, User $user, Builder $query): void
    {
        if (! $request->filled('client_id')) {
            return;
        }

        $clientId = (int) $request->input('client_id');
        if ($clientId < 1) {
            return;
        }

        if (! $this->bypassesClientScope($user)) {
            $own = $this->resolveUserClientId($user);
            if ($own !== $clientId) {
                return;
            }
        }

        $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhere(function ($sub) use ($clientId) {
                    $this->whereTicketSedeInClient($sub, $clientId);
                });
        });
    }

    /** Valida sede_id en filtros de listados. */
    public function applySedeFilter(Request $request, User $user, Builder $query, string $column = 'sede_id'): void
    {
        if (! $request->filled('sede_id')) {
            return;
        }

        $sedeId = (int) $request->input('sede_id');
        if ($sedeId < 1 || ! $this->assertSedeAccessible($user, $sedeId)) {
            return;
        }

        $query->where($column, $sedeId);
    }

    public function clientsForCatalog(?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($this->bypassesClientScope($user)) {
            return DB::table('clients')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->all();
        }

        $clientId = $this->resolveUserClientId($user);
        if (! $clientId) {
            return [];
        }

        return DB::table('clients')
            ->where('id', $clientId)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->all();
    }

    public function sedesQueryForUser(?User $user): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('sites')->where('is_active', true);
        if (! $user || $this->bypassesClientScope($user)) {
            return $q;
        }
        $clientId = $this->resolveUserClientId($user);
        if ($clientId) {
            $q->where('client_id', $clientId);
        } else {
            $q->whereRaw('0 = 1');
        }

        return $q;
    }

    public function usersQueryForCatalog(?User $user): \Illuminate\Database\Query\Builder
    {
        if (! $user) {
            return DB::table('users')->whereRaw('0 = 1');
        }

        $q = DB::table('users')->whereNull('deleted_at');
        if ($user->can('tickets.manage_all') || $user->can('incidents.manage_all')) {
            return $q;
        }
        if ($user->can('tickets.view_area') || $user->can('incidents.view_area')) {
            if ($user->area_id) {
                $q->where('area_id', $user->area_id);
            }
            $clientId = $this->resolveUserClientId($user);
            if ($clientId) {
                $q->whereIn('sede_id', $this->sedeIdsSubquery($clientId));
            }

            return $q;
        }

        return $q->whereRaw('0 = 1');
    }

    private function whereTicketSedeInClient(Builder $query, int $clientId): Builder
    {
        return $query->whereIn('sede_id', $this->sedeIdsSubquery($clientId));
    }

    private function sedeIdsSubquery(int $clientId): \Closure
    {
        return function ($sub) use ($clientId) {
            $sub->select('id')->from('sites')->where('client_id', $clientId);
        };
    }
}
