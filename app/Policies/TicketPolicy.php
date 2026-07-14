<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TicketPolicy
{
    /**
     * Admin de plataforma (super_admin) sin platform.view_internals:
     * bloqueado de toda operación sobre tickets individuales.
     * Devuelve null para todos los demás → el método correspondiente resuelve.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (app(\App\Services\OperatorScopeService::class)->isPlatformAdminBlockedFromInternals($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->siteScopeType($user) !== null;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create') || $user->can('tickets.manage_all');
    }

    /**
     * Vista (Fase 4 del sprint maestro -- reemplaza el alcance legacy por
     * área): admin ve todo; supervisor ve cualquier ticket de sus sites
     * (site_user); agente ve, de sus sites, solo los asignados a él o sin
     * asignar (un ticket asignado a OTRO agente desaparece de su vista de
     * inmediato); solicitante ve solo los suyos (requester_id).
     *
     * Un ticket sin site_id ("sin site asignado", Fase 2) solo lo ve admin
     * -- ni supervisor ni agente, hasta que alguien le asigne site.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (! app(\App\Services\ClientScopeService::class)->ticketVisibleToUser($user, $ticket)) {
            return false;
        }

        if ($this->siteScopeType($user) === 'solicitante') {
            return (int) $ticket->requester_id === (int) $user->id;
        }

        return $this->withinStaffSiteScope($user, $ticket);
    }

    /**
     * Solo agentes/supervisores con acceso al site pueden modificar (Fase 4
     * -- antes usaba isCurrentArea(), ver withinStaffSiteScope()). El
     * solicitante no actualiza ni comenta; usa alertas como observaciones
     * (alert()), decisión de producto que este sprint no cambia.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.manage_all')) {
            return true;
        }
        if (! $this->withinStaffSiteScope($user, $ticket)) {
            return false;
        }

        return $this->hasAnyManagePermission($user);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $this->canManageAction($user, $ticket, 'tickets.change_status');
    }

    public function changeArea(User $user, Ticket $ticket): bool
    {
        return $this->canManageAction($user, $ticket, 'tickets.escalate');
    }

    /** Solo agentes con permiso comentan. El solicitante añade observaciones mediante alertas. */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->canManageAction($user, $ticket, 'tickets.comment');
    }

    /**
     * Supervisor: cualquier ticket de sus sites (asignado o no). Agente:
     * mismo conjunto que puede ver -- sin asignar, o asignado a él (Fase 4,
     * ver withinStaffSiteScope()).
     *
     * DECISIÓN: sigue gateado por el permiso legacy tickets.assign, no por
     * tickets.reassign (creado en Fase 3, sin usar todavía). tickets.reassign
     * queda reservado para Fase 5, cuando exista la acción real de
     * "reasignar" con notificación correo+in-app -- conectarlo aquí
     * anticiparía esa fase sin sus notificaciones. Hoy agente y supervisor
     * tienen ambos permisos (tickets.assign vía TenantRoleSeeder), así que
     * el comportamiento no cambia por esta decisión.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.manage_all')) {
            return true;
        }
        if (! $user->can('tickets.assign')) {
            return false;
        }

        return $this->withinStaffSiteScope($user, $ticket);
    }

    /**
     * Supervisor: puede liberar cualquier ticket asignado de sus sites,
     * aunque no sea el responsable actual. Agente: solo el suyo (Fase 4 --
     * antes solo el responsable actual, sin distinción de rol).
     */
    public function release(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.manage_all')) {
            return true;
        }
        if (! $ticket->assigned_user_id) {
            return false;
        }

        return $this->withinStaffSiteScope($user, $ticket);
    }

    /**
     * Mismo criterio de site que el resto de acciones de mutación (Fase 4).
     * Antes bloqueaba a cualquiera que no fuera el responsable actual con un
     * pre-check aparte (isAssignee) -- eso colapsaba al supervisor al mismo
     * trato que un agente cualquiera. canManageAction() ya resuelve ese caso
     * (supervisor: todo su site; agente: sin asignar o suyo), así que el
     * pre-check se quita en vez de traducirlo a site, sería redundante.
     */
    public function escalate(User $user, Ticket $ticket): bool
    {
        return $this->canManageAction($user, $ticket, 'tickets.escalate');
    }

    /** Solo el solicitante puede enviar alertas (ticket no atendido / ignorado). */
    public function alert(User $user, Ticket $ticket): bool
    {
        return (int) $ticket->requester_id === (int) $user->id;
    }

    /** Solo el solicitante puede cancelar sus tickets que no estén resueltos. */
    public function cancel(User $user, Ticket $ticket): bool
    {
        if ((int) $ticket->requester_id !== (int) $user->id) {
            return false;
        }
        $ticket->loadMissing('state');

        return ! ($ticket->state && $ticket->state->is_final);
    }

    /**
     * Alcance por site (Fase 4 -- reemplaza el alcance legacy por área).
     * Mismas reglas que view(): admin todo, supervisor sus sites completos,
     * agente sus sites solo asignados-a-él-o-sin-asignar, solicitante solo
     * los suyos.
     */
    public function scopeFor(User $user, Builder $query): Builder
    {
        $scope = $this->siteScopeType($user);

        if ($scope === 'all') {
            return app(\App\Services\ClientScopeService::class)->applyTicketScope($query, $user);
        }

        if ($scope === 'solicitante') {
            $query->where('requester_id', $user->id);

            return app(\App\Services\ClientScopeService::class)->applyTicketScope($query, $user);
        }

        if ($scope === null) {
            $query->whereRaw('0 = 1');

            return app(\App\Services\ClientScopeService::class)->applyTicketScope($query, $user);
        }

        // supervisor / agente
        $siteIds = $this->userSiteIds($user);
        $query->whereIn('site_id', $siteIds);

        if ($scope === 'agente') {
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_user_id')->orWhere('assigned_user_id', $user->id);
            });
        }

        return app(\App\Services\ClientScopeService::class)->applyTicketScope($query, $user);
    }

    /**
     * Devuelve el tipo de alcance de visibilidad (Fase 4, por rol/site):
     * - all: is_operator o tickets.manage_all (admin)
     * - supervisor: rol supervisor
     * - agente: rol agente
     * - solicitante: rol solicitante
     * - null: ninguno de los roles nuevos -- sin acceso.
     *
     * OJO: usuarios que SOLO tienen un rol legacy (gerente, soporte*,
     * usuario, consultor) sin su equivalente nuevo asignado (ver
     * App\Console\Commands\MigrateLegacyRoles) devuelven null aquí, es
     * decir, pierden toda visibilidad de tickets hasta que se les asigne el
     * rol nuevo. Deliberado: Fase 4 reemplaza el área por site como
     * mecanismo de alcance, no lo hace convivir con el legacy.
     */
    protected function siteScopeType(User $user): ?string
    {
        if ($user->is_operator || $user->can('tickets.manage_all')) {
            return 'all';
        }
        if ($user->hasRole('supervisor')) {
            return 'supervisor';
        }
        if ($user->hasRole('agente')) {
            return 'agente';
        }
        if ($user->hasRole('solicitante')) {
            return 'solicitante';
        }

        return null;
    }

    protected function userSiteIds(User $user): \Illuminate\Support\Collection
    {
        return $user->sites()->pluck('sites.id');
    }

    /**
     * Alcance de site compartido por view() y por todas las acciones de
     * mutación (update/assign/release/escalate/canManageAction) -- Fase 4,
     * reemplaza al legacy isCurrentArea()/area_current_id como fuente de
     * autorización. No cubre 'solicitante': ese rol nunca actúa por site,
     * solo por ser el requester (alert()/cancel()), cada método que lo
     * permite lo resuelve aparte.
     *
     * - all: true sin restricción de site (admin).
     * - supervisor: true si el ticket está en uno de sus sites, sin
     *   importar a quién esté asignado.
     * - agente: true si el ticket está en uno de sus sites Y (sin asignar O
     *   asignado a él) -- el mismo conjunto que puede ver, no un subconjunto.
     * - solicitante / null: false.
     */
    protected function withinStaffSiteScope(User $user, Ticket $ticket): bool
    {
        $scope = $this->siteScopeType($user);

        if ($scope === 'all') {
            return true;
        }
        if ($scope !== 'supervisor' && $scope !== 'agente') {
            return false;
        }
        if (! $ticket->site_id || ! $this->userSiteIds($user)->contains((int) $ticket->site_id)) {
            return false;
        }
        if ($scope === 'supervisor') {
            return true;
        }

        // agente: asignado a él, o sin asignar (cola abierta del site).
        return ! $ticket->assigned_user_id || $this->isAssignee($user, $ticket);
    }

    protected function isAssignee(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_user_id && (int) $ticket->assigned_user_id === (int) $user->id;
    }

    protected function hasAnyManagePermission(User $user): bool
    {
        return $user->can('tickets.assign')
            || $user->can('tickets.change_status')
            || $user->can('tickets.comment')
            || $user->can('tickets.escalate');
    }

    /** changeStatus/changeArea/comment (vía canManageAction) usan site scope, no area (Fase 4). */
    protected function canManageAction(User $user, Ticket $ticket, string $permission): bool
    {
        if ($user->can('tickets.manage_all')) {
            return true;
        }
        if (! $user->can($permission)) {
            return false;
        }

        return $this->withinStaffSiteScope($user, $ticket);
    }
}
