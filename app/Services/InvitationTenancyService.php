<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Validation\ValidationException;

class InvitationTenancyService
{
    public function __construct(
        protected TenantContextService $tenantContext,
        protected OperatorScopeService $operatorScope
    ) {}

    /**
     * Resuelve y valida client_id al crear una invitación.
     */
    public function resolveClientIdForCreate(User $actor, ?int $requestedClientId): ?int
    {
        $enforced = $this->tenantContext->enforcedClientId();

        if ($enforced) {
            if ($requestedClientId && (int) $requestedClientId !== $enforced) {
                throw ValidationException::withMessages([
                    'client_id' => ['En este portal solo puedes invitar personal de tu organización.'],
                ]);
            }

            return $enforced;
        }

        if (! $requestedClientId) {
            return null;
        }

        $client = Client::find($requestedClientId);
        if (! $client) {
            throw ValidationException::withMessages([
                'client_id' => ['Cliente no válido.'],
            ]);
        }

        $this->operatorScope->authorizeClient($actor, $client);

        return (int) $client->id;
    }

    /**
     * En portal estricto, la invitación debe pertenecer al client_id del subdominio.
     */
    public function assertInvitationMatchesPortal(UserInvitation $invitation): void
    {
        $enforced = $this->tenantContext->enforcedClientId();
        if (! $enforced) {
            return;
        }

        if ((int) $invitation->client_id !== $enforced) {
            throw ValidationException::withMessages([
                'token' => ['Esta invitación no corresponde a este portal. Usa el enlace del correo en la URL de tu organización.'],
            ]);
        }
    }
}
