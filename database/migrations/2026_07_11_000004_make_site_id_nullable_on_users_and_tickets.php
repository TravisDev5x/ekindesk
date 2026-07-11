<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * site_id pasa a nullable en users y tickets (Fase 2 del sprint maestro):
 *
 * - users.site_id: site "hogar" del usuario — para solicitantes indica de
 *   dónde son; para agentes es solo referencia (su acceso real es por el
 *   pivote site_user). Un usuario recién invitado puede no tener site aún.
 * - tickets.site_id: NULL = "sin site asignado", estado explícito visible
 *   solo para admin/supervisor (scoping en TicketPolicy). Un requester sin
 *   site puede crear tickets igual; quedan pendientes de asignación.
 *
 * Laravel 12 soporta ->change() nativo en los tres drivers (en SQLite via
 * table rebuild), así que no hay que ramificar por driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No se revierte a NOT NULL: podrían existir filas con site_id NULL
        // legítimas (el revert fallaría o exigiría datos inventados).
    }
};
