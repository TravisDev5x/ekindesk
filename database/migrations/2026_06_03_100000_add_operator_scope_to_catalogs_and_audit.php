<?php

use App\Services\OperatorCatalogScopeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (OperatorCatalogScopeService::CATALOG_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'operator_user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('operator_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $blueprint->index('operator_user_id');
            });
        }

        if (Schema::hasTable('audit_logs') && ! Schema::hasColumn('audit_logs', 'client_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('client_id')
                    ->nullable()
                    ->constrained('clients')
                    ->nullOnDelete();
                $table->index(['client_id', 'created_at']);
            });

            $this->backfillAuditClientIds();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'client_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            });
        }

        foreach (array_reverse(OperatorCatalogScopeService::CATALOG_TABLES) as $table) {
            if (! Schema::hasColumn($table, 'operator_user_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['operator_user_id']);
                $blueprint->dropColumn('operator_user_id');
            });
        }
    }

    private function backfillAuditClientIds(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        $ticketClass = \App\Models\Ticket::class;
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                UPDATE audit_logs AS a
                SET client_id = t.client_id
                FROM tickets AS t
                WHERE a.auditable_type = ?
                  AND a.auditable_id = t.id
                  AND t.client_id IS NOT NULL
                  AND (a.client_id IS NULL OR a.client_id IS DISTINCT FROM t.client_id)
            ", [$ticketClass]);

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('
                UPDATE audit_logs AS a
                INNER JOIN tickets AS t ON t.id = a.auditable_id
                SET a.client_id = t.client_id
                WHERE a.auditable_type = ?
                  AND t.client_id IS NOT NULL
            ', [$ticketClass]);

            return;
        }

        DB::table('audit_logs')
            ->where('auditable_type', $ticketClass)
            ->orderBy('id')
            ->chunkById(500, function ($logs) {
                foreach ($logs as $log) {
                    $clientId = DB::table('tickets')->where('id', $log->auditable_id)->value('client_id');
                    if ($clientId) {
                        DB::table('audit_logs')->where('id', $log->id)->update(['client_id' => $clientId]);
                    }
                }
            });
    }
};
