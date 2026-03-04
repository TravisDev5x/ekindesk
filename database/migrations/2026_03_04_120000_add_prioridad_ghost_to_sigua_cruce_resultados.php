<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prioridad (CRITICA para usuarios fantasma) y flag de notificación inmediata (ISO 27001).
     */
    public function up(): void
    {
        Schema::table('sigua_cruce_resultados', function (Blueprint $table) {
            $table->string('prioridad', 32)->nullable()->after('categoria');
            $table->boolean('requiere_notificacion_inmediata')->default(false)->after('requiere_accion');
        });
    }

    public function down(): void
    {
        Schema::table('sigua_cruce_resultados', function (Blueprint $table) {
            $table->dropColumn(['prioridad', 'requiere_notificacion_inmediata']);
        });
    }
};
