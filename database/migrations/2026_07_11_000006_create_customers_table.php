<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversión de "Opción B" (clients.is_internal) hacia el diseño original del
 * sprint maestro: Client (tenant) -> Customer (empresa soportada) -> Site.
 *
 * customers.client_id ancla la fila al mismo client_id que ya usan
 * sites/tickets/incidents -- mismo límite de RLS, sin inventar uno nuevo.
 * is_internal reemplaza a clients.is_internal: marca la fila que representa
 * a la propia empresa del tenant (creada automáticamente por
 * InternalCustomerService, ver 2026_07_11_000008).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'is_internal'], 'customers_client_internal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
