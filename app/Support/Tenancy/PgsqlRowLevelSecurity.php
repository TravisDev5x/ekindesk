<?php

namespace App\Support\Tenancy;

use App\Models\User;
use App\Services\OperatorScopeService;
use App\Services\TenantClientResolver;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;

/**
 * Variables de sesión PostgreSQL para políticas RLS (ver migración 2026_06_05).
 *
 * Requiere TENANCY_PGSQL_RLS=true y DB_CONNECTION=pgsql.
 * Efectivo con FORCE ROW LEVEL SECURITY: el usuario de BD no debe ser superuser.
 */
final class PgsqlRowLevelSecurity
{
    public const BYPASS = 'app.tenant_bypass';

    public const PORTAL_CLIENT_ID = 'app.tenant_client_id';

    public const OPERATOR_USER_ID = 'app.tenant_operator_id';

    public const USER_CLIENT_ID = 'app.tenant_user_client_id';

    public static function enabled(): bool
    {
        return (bool) config('tenancy.pgsql_rls_enabled', false)
            && DB::connection()->getDriverName() === 'pgsql';
    }

    public static function applyForUser(?User $user): void
    {
        if (! self::enabled()) {
            return;
        }

        if (! $user) {
            self::clear();

            return;
        }

        $operatorScope = app(OperatorScopeService::class);
        $tenant = app(TenantContextService::class)->current();
        $strictPortal = $tenant->enforcesStrictClientIsolation();

        $bypass = $operatorScope->bypassesOperatorScope($user)
            || $operatorScope->usesLegacyMspWideAccess($user);

        $portalClientId = $strictPortal ? $tenant->clientId : null;

        // RLS es el backstop de BD (defensa en profundidad), no un espejo del scope
        // de aplicación: solo confía en is_operator real (staff MSP), nunca en permisos
        // como tickets.manage_all — de lo contrario un agente de UN cliente con ese
        // permiso vería, a nivel de BD, los tickets de TODOS los clientes del mismo
        // operador. Y el contexto de portal estricto siempre gana sobre el alcance
        // operador-amplio, igual que ya hace el scoping SQL de la aplicación.
        $operatorUserId = (! $strictPortal && $user->is_operator) ? (int) $user->id : null;
        $userClientId = app(TenantClientResolver::class)->resolve($user);

        self::set(self::BYPASS, $bypass ? 'true' : 'false');
        self::set(self::PORTAL_CLIENT_ID, $portalClientId ? (string) $portalClientId : '');
        self::set(self::OPERATOR_USER_ID, $operatorUserId ? (string) $operatorUserId : '');
        self::set(self::USER_CLIENT_ID, $userClientId ? (string) $userClientId : '');
    }

    public static function setBypass(bool $bypass): void
    {
        if (! self::enabled()) {
            return;
        }

        self::set(self::BYPASS, $bypass ? 'true' : 'false');
    }

    public static function clear(): void
    {
        if (! self::enabled()) {
            return;
        }

        foreach ([self::BYPASS, self::PORTAL_CLIENT_ID, self::OPERATOR_USER_ID, self::USER_CLIENT_ID] as $key) {
            self::set($key, '');
        }
    }

    private static function set(string $key, string $value): void
    {
        DB::statement('SELECT set_config(?, ?, false)', [$key, $value]);
    }
}
