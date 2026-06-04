<?php

use App\Support\Database\TenantBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIncidentClientId();
        $this->ensureTenantIndexes();
        $this->relaxGlobalClientUniques();
        TenantBackfill::syncClientIdFromSites('tickets');
        TenantBackfill::syncClientIdFromSites('incidents');
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'client_id')) {
                $table->dropIndex('incidents_client_status_idx');
                $table->dropIndex('incidents_client_created_idx');
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            if ($this->indexExists('sites', 'sites_client_active_idx')) {
                $table->dropIndex('sites_client_active_idx');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if ($this->indexExists('tickets', 'tickets_client_created_idx')) {
                $table->dropIndex('tickets_client_created_idx');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if ($this->indexExists('clients', 'clients_operator_name_unique')) {
                $table->dropUnique('clients_operator_name_unique');
            }
            if ($this->indexExists('clients', 'clients_operator_code_unique')) {
                $table->dropUnique('clients_operator_code_unique');
            }
            if (! $this->indexExists('clients', 'clients_name_unique')) {
                $table->unique('name');
            }
            if (! $this->indexExists('clients', 'clients_code_unique')) {
                $table->unique('code');
            }
        });
    }

    private function addIncidentClientId(): void
    {
        if (Schema::hasColumn('incidents', 'client_id')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->index(['client_id', 'incident_status_id'], 'incidents_client_status_idx');
            $table->index(['client_id', 'created_at'], 'incidents_client_created_idx');
        });
    }

    private function ensureTenantIndexes(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! $this->indexExists('sites', 'sites_client_active_idx')) {
                $table->index(['client_id', 'is_active'], 'sites_client_active_idx');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (! $this->indexExists('tickets', 'tickets_client_created_idx')) {
                $table->index(['client_id', 'created_at'], 'tickets_client_created_idx');
            }
        });

        if (Schema::hasColumn('clients', 'operator_user_id') && ! $this->indexExists('clients', 'clients_operator_active_idx')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index(['operator_user_id', 'is_active'], 'clients_operator_active_idx');
            });
        }
    }

    /**
     * Global unique(name|code) blocks two MSP operators from using the same label.
     * Scope uniqueness per operator_user_id (tenant owner at MSP level).
     */
    private function relaxGlobalClientUniques(): void
    {
        if (! Schema::hasColumn('clients', 'operator_user_id')) {
            return;
        }

        $duplicateNames = DB::table('clients')
            ->select('operator_user_id', 'name')
            ->groupBy('operator_user_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicateNames) {
            throw new RuntimeException(
                'No se puede aplicar unicidad por operador: hay nombres de cliente duplicados para el mismo operator_user_id. Resuélvelos antes de migrar.'
            );
        }

        Schema::table('clients', function (Blueprint $table) {
            foreach (['clients_name_unique', 'clients_code_unique'] as $legacyIndex) {
                if ($this->indexExists('clients', $legacyIndex)) {
                    $table->dropUnique($legacyIndex);
                }
            }

            if (! $this->indexExists('clients', 'clients_operator_name_unique')) {
                $table->unique(['operator_user_id', 'name'], 'clients_operator_name_unique');
            }
            if (! $this->indexExists('clients', 'clients_operator_code_unique')) {
                $table->unique(['operator_user_id', 'code'], 'clients_operator_code_unique');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? null) === $indexName);
        }

        if ($driver === 'pgsql') {
            $row = $connection->selectOne(
                'SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

        $database = $connection->getDatabaseName();
        $row = $connection->selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $row !== null;
    }
};
