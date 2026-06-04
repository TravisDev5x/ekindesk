<?php

use App\Services\OperatorCatalogScopeService;
use App\Services\TenantContextService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'portal_slug')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('portal_slug', 63)->nullable()->unique();
                $table->string('portal_primary_color', 7)->nullable();
                $table->string('portal_welcome_message', 500)->nullable();
            });

            $this->backfillPortalSlugs();
        }

        foreach (OperatorCatalogScopeService::CATALOG_TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'client_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('client_id')
                    ->nullable()
                    ->constrained('clients')
                    ->nullOnDelete();
                $blueprint->index(['client_id', 'operator_user_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(OperatorCatalogScopeService::CATALOG_TABLES) as $table) {
            if (! Schema::hasColumn($table, 'client_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['client_id']);
                $blueprint->dropColumn('client_id');
            });
        }

        if (Schema::hasColumn('clients', 'portal_slug')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropUnique(['portal_slug']);
                $table->dropColumn(['portal_slug', 'portal_primary_color', 'portal_welcome_message']);
            });
        }
    }

    private function backfillPortalSlugs(): void
    {
        DB::table('clients')->orderBy('id')->lazyById()->each(function ($row) {
            if ($row->portal_slug) {
                return;
            }
            DB::table('clients')->where('id', $row->id)->update([
                'portal_slug' => TenantContextService::generateUniquePortalSlug($row->name, (int) $row->id),
            ]);
        });
    }
};
