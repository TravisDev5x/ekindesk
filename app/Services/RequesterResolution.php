<?php

namespace App\Services;

use App\Models\User;

/**
 * Resultado de "¿este email puede generar/interactuar con un ticket de este
 * tenant?" — ver TicketCreationService::resolveActiveTenantUser(). Único
 * punto de decisión, usado por email (ProcessInboundTicket/ProcessInboundReply)
 * y portal (MyTicketsController), para que no vuelvan a divergir.
 */
final class RequesterResolution
{
    private function __construct(
        public readonly bool $allowed,
        public readonly ?User $user,
        public readonly string $reason,
        public readonly ?int $actualClientId = null,
    ) {}

    public static function ok(User $user): self
    {
        return new self(true, $user, 'ok');
    }

    public static function unregistered(): self
    {
        return new self(false, null, 'unregistered');
    }

    public static function inactive(User $user): self
    {
        return new self(false, $user, 'inactive');
    }

    public static function wrongTenant(User $user, ?int $actualClientId): self
    {
        return new self(false, $user, 'wrong_tenant', $actualClientId);
    }
}
