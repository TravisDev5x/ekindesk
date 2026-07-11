<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversión de "Opción B": el flag clients.is_internal (2026_07_11_000003)
 * se reemplaza por customers.is_internal (2026_07_11_000006). Antes de
 * correr esto en un entorno con datos reales: `tenants:backfill-internal-customer`
 * debe haber migrado cualquier clients.is_internal=true existente hacia su
 * Customer equivalente (ver App\Console\Commands\BackfillInternalCustomers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_operator_internal_idx');
            $table->dropColumn('is_internal');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false);
            $table->index(['operator_user_id', 'is_internal'], 'clients_operator_internal_idx');
        });
    }
};
