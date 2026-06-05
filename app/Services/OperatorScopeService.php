<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class OperatorScopeService
{
    public function __construct(
        protected TenantClientResolver $tenantResolver,
        protected TenantContextService $tenantContext
    ) {}

    /** Plataforma: ve todos los operadores MSP y clientes. */
    public function bypassesOperatorScope(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Acceso transversal dentro del MSP (todos los clientes del operador).
     * Ya no implica ver toda la plataforma (eso es solo super_admin).
     */
    public function hasMspWideAccess(User $user): bool
    {
        if ($this->bypassesOperatorScope($user)) {
            return true;
        }

        return $user->is_operator
            || $user->can('clients.view_all')
            || $user->can('tickets.manage_all')
            || $user->can('incidents.manage_all');
    }

    /**
     * Acceso transversal a todos los clientes del operador MSP (no solo el tenant vinculado por sede/client_id).
     */
    public function usesOperatorMspWideScope(User $user): bool
    {
        if ($this->bypassesOperatorScope($user) || ! $this->hasMspWideAccess($user)) {
            return false;
        }

        if ($user->is_operator) {
            return true;
        }

        return $this->tenantResolver->resolve($user) === null;
    }

    /**
     * Usuario dueño del MSP cuyos clientes (clients.operator_user_id) aplican.
     */
    /**
     * Instalación legacy (dominio raíz): manage_all sin operador MSP vinculado.
     * No aplica en portal estricto por subdominio.
     */
    public function usesLegacyMspWideAccess(User $user): bool
    {
        if (! config('tenancy.legacy_msp_wide_access', false)) {
            return false;
        }

        if ($this->tenantContext->isStrictClientPortal()) {
            return false;
        }

        if ($this->bypassesOperatorScope($user) || ! $this->hasMspWideAccess($user)) {
            return false;
        }

        return $this->resolveOperatorUserId($user) === null;
    }

    /**
     * Usuarios MSP-wide sin operador resuelto (candidatos a is_operator antes de desactivar legacy).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function legacyOperatorCandidates(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('is_operator', false)
            ->where('status', 'active')
            ->get()
            ->filter(function (User $user) {
                if ($this->bypassesOperatorScope($user) || ! $this->hasMspWideAccess($user)) {
                    return false;
                }

                return $this->resolveOperatorUserId($user) === null;
            })
            ->values();
    }

    public function resolveOperatorUserId(User $user): ?int
    {
        if ($user->is_operator) {
            return (int) $user->id;
        }

        if ($user->client_id) {
            $operatorId = Cliente::where('id', $user->client_id)->value('operator_user_id');
            if ($operatorId) {
                return (int) $operatorId;
            }
        }

        $user->loadMissing('sede:id,client_id');
        if ($user->sede?->client_id) {
            $operatorId = Cliente::where('id', $user->sede->client_id)->value('operator_user_id');
            if ($operatorId) {
                return (int) $operatorId;
            }
        }

        return null;
    }

    public function applyOnClients(Builder $query, User $user): Builder
    {
        if ($enforced = $this->tenantContext->enforcedClientId()) {
            return $query->where('id', $enforced);
        }

        if ($this->bypassesOperatorScope($user)) {
            return $query;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            $operatorId = $this->resolveOperatorUserId($user);
            if (! $operatorId) {
                return $this->usesLegacyMspWideAccess($user) ? $query : $query->whereRaw('0 = 1');
            }

            return $query->where('operator_user_id', $operatorId);
        }

        $clientId = $this->tenantResolver->resolve($user);
        if (! $clientId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('id', $clientId);
    }

    public function applyOnSites(Builder $query, User $user): Builder
    {
        if ($enforced = $this->tenantContext->enforcedClientId()) {
            return $query->where('client_id', $enforced);
        }

        if ($this->bypassesOperatorScope($user)) {
            return $query;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            $operatorId = $this->resolveOperatorUserId($user);
            if (! $operatorId) {
                return $this->usesLegacyMspWideAccess($user) ? $query : $query->whereRaw('0 = 1');
            }

            return $query->whereIn('client_id', $this->clientIdsSubquery($operatorId));
        }

        $clientId = $this->tenantResolver->resolve($user);
        if (! $clientId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('client_id', $clientId);
    }

    public function applyOnTickets(Builder $query, User $user): Builder
    {
        if ($enforced = $this->tenantContext->enforcedClientId()) {
            return $query->where(function ($q) use ($enforced) {
                $q->where('client_id', $enforced)
                    ->orWhereIn('sede_id', function ($sub) use ($enforced) {
                        $sub->select('id')->from('sites')->where('client_id', $enforced);
                    });
            });
        }

        if ($this->bypassesOperatorScope($user)) {
            return $query;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            $operatorId = $this->resolveOperatorUserId($user);
            if (! $operatorId) {
                return $this->usesLegacyMspWideAccess($user) ? $query : $query->whereRaw('0 = 1');
            }

            return $query->where(function ($q) use ($operatorId) {
                $q->whereIn('client_id', $this->clientIdsSubquery($operatorId))
                    ->orWhereIn('sede_id', $this->siteIdsSubquery($operatorId));
            });
        }

        $clientId = $this->tenantResolver->resolve($user);
        if (! $clientId) {
            return $this->applyTicketsWithoutTenant($query, $user);
        }

        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhereIn('sede_id', function ($sub) use ($clientId) {
                    $sub->select('id')->from('sites')->where('client_id', $clientId);
                });
        });
    }

    public function applyOnIncidents(Builder $query, User $user): Builder
    {
        if ($enforced = $this->tenantContext->enforcedClientId()) {
            return $query->where(function ($q) use ($enforced) {
                $q->where('client_id', $enforced)
                    ->orWhereIn('sede_id', function ($sub) use ($enforced) {
                        $sub->select('id')->from('sites')->where('client_id', $enforced);
                    });
            });
        }

        if ($this->bypassesOperatorScope($user)) {
            return $query;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            $operatorId = $this->resolveOperatorUserId($user);
            if (! $operatorId) {
                return $this->usesLegacyMspWideAccess($user) ? $query : $query->whereRaw('0 = 1');
            }

            return $query->where(function ($q) use ($operatorId) {
                $q->whereIn('client_id', $this->clientIdsSubquery($operatorId))
                    ->orWhereIn('sede_id', $this->siteIdsSubquery($operatorId));
            });
        }

        $clientId = $this->tenantResolver->resolve($user);
        if (! $clientId) {
            return $this->applyIncidentsWithoutTenant($query, $user);
        }

        return $query->where(function ($q) use ($clientId) {
            $q->where('client_id', $clientId)
                ->orWhereIn('sede_id', function ($sub) use ($clientId) {
                    $sub->select('id')->from('sites')->where('client_id', $clientId);
                });
        });
    }

    public function assertClientAccessible(User $user, Cliente $client): bool
    {
        if ($this->bypassesOperatorScope($user)) {
            return true;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            if ($this->usesLegacyMspWideAccess($user)) {
                return true;
            }

            $operatorId = $this->resolveOperatorUserId($user);

            return $operatorId && (int) $client->operator_user_id === $operatorId;
        }

        $clientId = $this->tenantResolver->resolve($user);

        return $clientId && (int) $client->id === $clientId;
    }

    public function authorizeClient(User $user, Cliente $client): void
    {
        if (! $this->assertClientAccessible($user, $client)) {
            abort(403, 'No tienes acceso a este cliente.');
        }
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public function nameRules(User $user, ?int $ignoreClientId = null): array
    {
        $rules = ['required', 'string', 'min:2', 'max:255'];

        if ($this->bypassesOperatorScope($user)) {
            $rules[] = Rule::unique('clients', 'name')->ignore($ignoreClientId);

            return $rules;
        }

        $operatorId = $this->resolveOperatorUserId($user) ?? ($user->is_operator ? $user->id : null);

        $unique = Rule::unique('clients', 'name')->ignore($ignoreClientId);
        if ($operatorId !== null) {
            $unique = $unique->where(fn ($q) => $q->where('operator_user_id', $operatorId));
        } else {
            $unique = $unique->where(fn ($q) => $q->whereNull('operator_user_id'));
        }

        $rules[] = $unique;

        return $rules;
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public function codeRules(User $user, ?int $ignoreClientId = null): array
    {
        $rules = ['nullable', 'string', 'max:20'];

        if ($this->bypassesOperatorScope($user)) {
            $rules[] = Rule::unique('clients', 'code')->ignore($ignoreClientId);

            return $rules;
        }

        $operatorId = $this->resolveOperatorUserId($user) ?? ($user->is_operator ? $user->id : null);

        $unique = Rule::unique('clients', 'code')->ignore($ignoreClientId);
        if ($operatorId !== null) {
            $unique = $unique->where(fn ($q) => $q->where('operator_user_id', $operatorId));
        } else {
            $unique = $unique->where(fn ($q) => $q->whereNull('operator_user_id'));
        }

        $rules[] = $unique;

        return $rules;
    }

    public function operatorUserIdForNewClient(User $user): ?int
    {
        if ($this->bypassesOperatorScope($user) && ! $user->is_operator) {
            return null;
        }

        return $this->resolveOperatorUserId($user) ?? ($user->is_operator ? (int) $user->id : null);
    }

    /**
     * @return list<object{id: int, name: string}>
     */
    public function clientsForCatalog(User $user, bool $activeOnly = true): array
    {
        $query = Cliente::query()->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $this->applyOnClients($query, $user)
            ->get(['id', 'name'])
            ->all();
    }

    public function assertClientIdInScope(User $user, int $clientId): bool
    {
        if ($clientId < 1) {
            return false;
        }

        return $this->applyOnClients(Cliente::query()->where('id', $clientId), $user)->exists();
    }

    public function authorizeSite(User $user, \App\Models\Sede $sede): void
    {
        if (! $this->applyOnSites(\App\Models\Sede::query()->where('id', $sede->id), $user)->exists()) {
            abort(403, 'No tienes acceso a esta sede.');
        }
    }

    /**
     * Usuario con manage_all pertenece al mismo operador MSP que el ticket.
     */
    public function userInTicketOperatorScope(User $user, \App\Models\Ticket $ticket): bool
    {
        if ($this->bypassesOperatorScope($user)) {
            return true;
        }

        if ($this->usesLegacyMspWideAccess($user)) {
            return true;
        }

        $operatorId = $this->resolveOperatorIdForTicket($ticket);
        if (! $operatorId) {
            return false;
        }

        $userOperatorId = $this->resolveOperatorUserId($user);

        return $userOperatorId && (int) $userOperatorId === (int) $operatorId;
    }

    public function resolveOperatorIdForTicket(\App\Models\Ticket $ticket): ?int
    {
        $ticket->loadMissing('sede:id,client_id', 'cliente:id,operator_user_id');

        if ($ticket->client_id && $ticket->cliente?->operator_user_id) {
            return (int) $ticket->cliente->operator_user_id;
        }

        if ($ticket->client_id) {
            $op = Cliente::where('id', $ticket->client_id)->value('operator_user_id');

            return $op ? (int) $op : null;
        }

        if ($ticket->sede?->client_id) {
            $op = Cliente::where('id', $ticket->sede->client_id)->value('operator_user_id');

            return $op ? (int) $op : null;
        }

        return null;
    }

    /** Restringe audit_logs al operador MSP del usuario. */
    public function applyOnAuditLogs(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        if ($enforced = $this->tenantContext->enforcedClientId()) {
            return $query->where('client_id', $enforced);
        }

        if ($this->bypassesOperatorScope($user)) {
            return $query;
        }

        if ($this->usesOperatorMspWideScope($user)) {
            $operatorId = $this->resolveOperatorUserId($user);
            if (! $operatorId) {
                return $this->usesLegacyMspWideAccess($user) ? $query : $query->whereRaw('0 = 1');
            }

            return $query->whereIn('client_id', function ($sub) use ($operatorId) {
                $sub->select('id')->from('clients')->where('operator_user_id', $operatorId);
            });
        }

        $clientId = $this->tenantResolver->resolve($user);
        if ($clientId) {
            return $query->where('client_id', $clientId);
        }

        return $query->whereRaw('0 = 1');
    }

    private function applyTicketsWithoutTenant(Builder $query, User $user): Builder
    {
        if ($this->tenantResolver->isAreaScopedWithoutTenant($user, 'tickets')) {
            return $query;
        }

        if ($user->can('tickets.view_own')) {
            return $query->where('requester_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }

    private function applyIncidentsWithoutTenant(Builder $query, User $user): Builder
    {
        if ($this->tenantResolver->isAreaScopedWithoutTenant($user, 'incidents')) {
            return $query;
        }

        if ($user->can('incidents.view_own')) {
            return $query->where('reporter_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }

    /** @return \Closure(\Illuminate\Database\Query\Builder): void */
    private function clientIdsSubquery(int $operatorUserId): \Closure
    {
        return function ($sub) use ($operatorUserId) {
            $sub->select('id')->from('clients')->where('operator_user_id', $operatorUserId);
        };
    }

    /** @return \Closure(\Illuminate\Database\Query\Builder): void */
    private function siteIdsSubquery(int $operatorUserId): \Closure
    {
        return function ($sub) use ($operatorUserId) {
            $sub->select('sites.id')
                ->from('sites')
                ->join('clients', 'clients.id', '=', 'sites.client_id')
                ->where('clients.operator_user_id', $operatorUserId);
        };
    }
}
