<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote site_user (Fase 4 del sprint maestro): a qué sites tiene acceso un
 * usuario staff (supervisor/agente) para efectos de TicketPolicy. Distinto
 * de users.site_id (su site "hogar", Fase 2) -- un agente puede tener
 * acceso a varios sites sin que ninguno sea necesariamente el suyo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['site_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_user');
    }
};
