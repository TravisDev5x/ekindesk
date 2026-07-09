<?php

use App\Support\Database\TenantBackfill;
use App\Support\Database\TenantIntegrity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TenantBackfill::syncClientIdFromSites('tickets');
        TenantBackfill::syncClientIdFromSites('incidents');

        TenantIntegrity::assertReadyForNotNull();

        $this->makeClientIdRequired('tickets');
        $this->makeClientIdRequired('incidents');
    }

    public function down(): void
    {
        $this->makeClientIdNullable('incidents');
        $this->makeClientIdNullable('tickets');
    }

    private function makeClientIdRequired(string $table): void
    {
        if (! Schema::hasColumn($table, 'client_id')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement("ALTER TABLE {$table} ALTER COLUMN client_id SET NOT NULL"),
            'mysql' => DB::statement("ALTER TABLE {$table} MODIFY client_id BIGINT UNSIGNED NOT NULL"),
            'sqlite' => $this->sqliteSetClientIdNotNull($table),
            default => null,
        };
    }

    private function makeClientIdNullable(string $table): void
    {
        if (! Schema::hasColumn($table, 'client_id')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement("ALTER TABLE {$table} ALTER COLUMN client_id DROP NOT NULL"),
            'mysql' => DB::statement("ALTER TABLE {$table} MODIFY client_id BIGINT UNSIGNED NULL"),
            'sqlite' => DB::statement("ALTER TABLE {$table} ALTER COLUMN client_id DROP NOT NULL"),
            default => null,
        };
    }

    private function sqliteSetClientIdNotNull(string $table): void
    {
        try {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN client_id SET NOT NULL");
        } catch (\Throwable) {
            // SQLite antiguo: integridad vía observer + tenant:client-id verify
        }
    }
};
