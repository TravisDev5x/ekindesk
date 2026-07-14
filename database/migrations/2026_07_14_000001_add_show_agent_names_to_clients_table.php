<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-fase 5.2 del sprint maestro: si el solicitante ve el nombre (y email)
 * real del agente asignado/que comenta, o una etiqueta genérica. Default
 * true -- iguala el comportamiento actual (siempre visible) para no
 * sorprender a tenants existentes; ver App\Console\Commands\SetAgentVisibility
 * para activarlo/desactivarlo por tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('show_agent_names')->default(true)->after('ai_classification_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('show_agent_names');
        });
    }
};
