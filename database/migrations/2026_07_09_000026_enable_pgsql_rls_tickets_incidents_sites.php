<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RLS mínimo en tickets, incidents, sites (solo PostgreSQL).
 *
 * Activar con TENANCY_PGSQL_RLS=true. Usuario de aplicación sin BYPASSRLS / no superuser.
 */
return new class extends Migration
{
    private const POLICY = 'tikara_tenant_isolation';

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        $using = $this->policyExpression();

        foreach (['tickets', 'incidents', 'sites'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            DB::statement("DROP POLICY IF EXISTS ".self::POLICY." ON {$table}");
            DB::statement("CREATE POLICY ".self::POLICY." ON {$table} FOR ALL USING ({$using}) WITH CHECK ({$using})");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['tickets', 'incidents', 'sites'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement('DROP POLICY IF EXISTS '.self::POLICY." ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }

    private function policyExpression(): string
    {
        return <<<'SQL'
(
    coalesce(nullif(current_setting('app.tenant_bypass', true), ''), 'false') = 'true'
)
OR (
    nullif(current_setting('app.tenant_client_id', true), '') IS NOT NULL
    AND client_id = nullif(current_setting('app.tenant_client_id', true), '')::bigint
)
OR (
    nullif(current_setting('app.tenant_operator_id', true), '') IS NOT NULL
    AND client_id IN (
        SELECT id FROM clients
        WHERE operator_user_id = nullif(current_setting('app.tenant_operator_id', true), '')::bigint
    )
)
OR (
    nullif(current_setting('app.tenant_user_client_id', true), '') IS NOT NULL
    AND client_id = nullif(current_setting('app.tenant_user_client_id', true), '')::bigint
)
SQL;
    }
};
