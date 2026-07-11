<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prefijo de folio por cliente (formato nuevo TK-{Letra}{Num5}-{PREFIX}-{Rand5}).
 *
 * Nullable al nacer: los clientes existentes lo reciben vía
 * `php artisan tenants:backfill-ticket-prefix` (o lazy, al generar su primer
 * folio). Los clientes nuevos lo reciben automáticamente al crearse
 * (Client::booted). UNIQUE global — a diferencia de clients.code, cuya
 * unicidad es compuesta por operador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('ticket_prefix', 10)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['ticket_prefix']);
            $table->dropColumn('ticket_prefix');
        });
    }
};
