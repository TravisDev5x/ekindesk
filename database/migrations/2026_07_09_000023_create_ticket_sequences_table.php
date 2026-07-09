<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant folio sequence.
 *
 * Each client has its own independent counter. Client A's folio #1 and client B's
 * folio #1 are different tickets.
 *
 * Usage:
 *   UPDATE ticket_sequences SET last_number = last_number + 1
 *   WHERE client_id = ? RETURNING last_number;
 *
 * The atomic UPDATE avoids race conditions without extra locks. PostgreSQL could use
 * FOR UPDATE or a dedicated sequence's nextval(), but this approach is portable and
 * sufficient for the expected volumes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sequences', function (Blueprint $table) {
            // client_id is bigint (clients.id is auto-increment bigint, not UUID)
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
