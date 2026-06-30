<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Secuencia de folios por tenant.
 *
 * Cada cliente tiene su propio contador independiente.
 * El folio #1 del cliente A y el #1 del cliente B son distintos tickets.
 *
 * Uso:
 *   UPDATE ticket_sequences SET last_number = last_number + 1
 *   WHERE client_id = ? RETURNING last_number;
 *
 * El UPDATE atómico evita race conditions sin necesidad de locks adicionales.
 * En PostgreSQL se puede usar FOR UPDATE o nextval() por secuencia dedicada,
 * pero este enfoque es portátil y suficiente para los volúmenes esperados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sequences', function (Blueprint $table) {
            // client_id es bigint (clients.id es auto-increment bigint, no UUID)
            $table->unsignedBigInteger('client_id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sequences');
    }
};
