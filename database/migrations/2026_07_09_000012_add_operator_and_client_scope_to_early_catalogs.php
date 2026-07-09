<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * campaigns/areas/positions are created before `users`/`clients` exist (they're needed by
 * users.campaign_id/area_id/position_id), so their operator/client scoping columns
 * (App\Services\OperatorCatalogScopeService::CATALOG_TABLES) are added here instead, once
 * both tables exist. The remaining catalog tables in CATALOG_TABLES are created after
 * `users`/`clients` and get these columns folded directly into their own create migration.
 */
return new class extends Migration
{
    private const TABLES = ['campaigns', 'areas', 'positions'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
                $blueprint->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $blueprint->index(['client_id', 'operator_user_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['operator_user_id']);
                $blueprint->dropForeign(['client_id']);
                $blueprint->dropColumn(['operator_user_id', 'client_id']);
            });
        }
    }
};
