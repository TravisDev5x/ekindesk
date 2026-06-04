<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fuente única del tenant operativo (client_id) de un usuario final.
 *
 * Prioridad:
 * 1. sites.client_id vía users.sede_id (asignación operativa diaria)
 * 2. users.client_id (vínculo directo MSP / invitación sin sede)
 */
class TenantClientResolver
{
    public function resolve(User $user): ?int
    {
        $user->loadMissing('sede:id,client_id');

        $fromSede = $user->sede_id && $user->sede?->client_id
            ? (int) $user->sede->client_id
            : null;

        $fromUser = $user->client_id ? (int) $user->client_id : null;

        if ($fromSede && $fromUser && $fromSede !== $fromUser) {
            Log::debug('tenant.client_id mismatch: sede vs users.client_id', [
                'user_id' => $user->id,
                'sede_client_id' => $fromSede,
                'user_client_id' => $fromUser,
            ]);
        }

        if ($fromSede) {
            return $this->validClientId($fromSede);
        }

        if ($fromUser) {
            return $this->validClientId($fromUser);
        }

        return null;
    }

    public function hasOperationalSite(User $user): bool
    {
        return (bool) $user->sede_id;
    }

    /**
     * Permiso view_*_area sin area_id asignada (config incompleta).
     */
    public function hasAreaPermissionWithoutArea(User $user, string $module): bool
    {
        $perm = $module === 'incidents' ? 'incidents.view_area' : 'tickets.view_area';

        return $user->can($perm) && ! $user->area_id;
    }

    /**
     * Staff de área sin sede ni client_id: solo filtro por área (en policy).
     */
    public function isAreaScopedWithoutTenant(User $user, string $module): bool
    {
        $perm = $module === 'incidents' ? 'incidents.view_area' : 'tickets.view_area';

        return $user->can($perm)
            && $user->area_id
            && $this->resolve($user) === null;
    }

    private function validClientId(int $clientId): ?int
    {
        return Cliente::where('id', $clientId)->where('is_active', true)->exists()
            ? $clientId
            : null;
    }
}
