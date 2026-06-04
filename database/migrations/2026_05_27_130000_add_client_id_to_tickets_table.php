<?php

use App\Support\Database\TenantBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('clients')
                ->nullOnDelete();
        });

        TenantBackfill::syncClientIdFromSites('tickets');
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
    }
};
