<?php

namespace App\Support\Tenancy;

use App\Models\Cliente;

/**
 * Contexto de tenant resuelto desde subdominio / cabecera.
 *
 * - platform: dominio raíz (sin portal de cliente)
 * - msp_console: consola del operador MSP (futuro: subdominio del operador)
 * - client_portal: portal de una empresa final (clients.portal_slug)
 */
final class TenantContext
{
    public const MODE_PLATFORM = 'platform';
    public const MODE_MSP_CONSOLE = 'msp_console';
    public const MODE_CLIENT_PORTAL = 'client_portal';

    public function __construct(
        public readonly string $mode,
        public readonly ?string $subdomain = null,
        public readonly ?int $clientId = null,
        public readonly ?Cliente $client = null,
    ) {}

    public function isClientPortal(): bool
    {
        return $this->mode === self::MODE_CLIENT_PORTAL && $this->clientId !== null;
    }

    public function enforcesStrictClientIsolation(): bool
    {
        return $this->isClientPortal() && (bool) config('tenancy.strict_client_portal', true);
    }

    /** @return array<string, mixed> */
    public function brandingForFrontend(): array
    {
        if (! $this->client) {
            return ['mode' => self::MODE_PLATFORM];
        }

        return [
            'mode' => $this->mode,
            'client_id' => $this->clientId,
            'name' => $this->client->name,
            'logo_path' => $this->client->logo_path,
            'portal_primary_color' => $this->client->portal_primary_color ?? null,
            'portal_welcome_message' => $this->client->portal_welcome_message ?? null,
        ];
    }
}
