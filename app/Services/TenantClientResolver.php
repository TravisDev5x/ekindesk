<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Fuente única del tenant operativo (client_id) de un usuario final.
 *
 * Prioridad:
 * 1. sites.client_id vía users.site_id (asignación operativa diaria)
 * 2. users.client_id (vínculo directo MSP / invitación sin sede)
 */
class TenantClientResolver
{
    private array $validClientCache = [];

    public function resolve(User $user): ?int
    {
        $user->loadMissing('site:id,client_id');

        $fromSite = $user->site_id && $user->site?->client_id
            ? (int) $user->site->client_id
            : null;

        $fromUser = $user->client_id ? (int) $user->client_id : null;

        if ($fromSite && $fromUser && $fromSite !== $fromUser) {
            Log::debug('tenant.client_id mismatch: site vs users.client_id', [
                'user_id' => $user->id,
                'site_client_id' => $fromSite,
                'user_client_id' => $fromUser,
            ]);
        }

        if ($fromSite) {
            return $this->validClientId($fromSite);
        }

        if ($fromUser) {
            return $this->validClientId($fromUser);
        }

        return null;
    }

    public function hasOperationalSite(User $user): bool
    {
        return (bool) $user->site_id;
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
        if (! array_key_exists($clientId, $this->validClientCache)) {
            $this->validClientCache[$clientId] = Client::where('id', $clientId)
                ->where('is_active', true)
                ->exists()
                ? $clientId
                : null;
        }

        return $this->validClientCache[$clientId];
    }
}
