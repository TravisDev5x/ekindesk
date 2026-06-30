<?php

namespace App\Services\Tenant;

use App\Models\Cliente;

/**
 * Contexto estático de tenant para jobs, commands y código fuera del ciclo HTTP.
 *
 * En requests HTTP el contexto se resuelve via App\Services\TenantContextService
 * (resolución por subdominio + caché). Esta clase es el singleton estático que
 * permite acceder al tenant activo en jobs despachados (ProcessInboundTicket, etc.)
 * sin depender de la request.
 *
 * Uso en jobs:
 *   TenantContextService::set($cliente);
 *   // ... lógica del job
 *   TenantContextService::clear();
 */
final class TenantContextService
{
    private static ?Cliente $current = null;

    public static function set(Cliente $tenant): void
    {
        static::$current = $tenant;
    }

    public static function get(): ?Cliente
    {
        return static::$current;
    }

    public static function clear(): void
    {
        static::$current = null;
    }

    public static function isResolved(): bool
    {
        return static::$current !== null;
    }

    public static function getId(): ?int
    {
        return static::$current?->id;
    }

    public static function getOrFail(): Cliente
    {
        if (static::$current === null) {
            throw new \RuntimeException('Tenant context not resolved. Call TenantContextService::set() before accessing the tenant.');
        }

        return static::$current;
    }

    public static function getPortalSlug(): ?string
    {
        return static::$current?->portal_slug;
    }
}
