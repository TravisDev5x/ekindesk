<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RLS en customers, mismo patrón y misma expresión que
 * 2026_07_09_000026_enable_pgsql_rls_tickets_incidents_sites (solo
 * PostgreSQL; requiere TENANCY_PGSQL_RLS=true y usuario de aplicación sin
 * BYPASSRLS para que FORCE ROW LEVEL SECURITY tenga efecto real).
 */
return new class extends Migration
{
    private const POLICY = 'tikara_tenant_isolation';

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('customers')) {
            return;
        }

        $using = $this->policyExpression();

        DB::statement('ALTER TABLE customers ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE customers FORCE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS '.self::POLICY.' ON customers');
        DB::statement('CREATE POLICY '.self::POLICY." ON customers FOR ALL USING ({$using}) WITH CHECK ({$using})");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('customers')) {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS '.self::POLICY.' ON customers');
        DB::statement('ALTER TABLE customers DISABLE ROW LEVEL SECURITY');
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
