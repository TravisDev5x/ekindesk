<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IGA: soporte tipo externo, empresa_cliente y categorías de cruce (externo_sin_justificacion, por_clasificar).
     */
    public function up(): void
    {
        Schema::table('sigua_accounts', function (Blueprint $table) {
            $table->string('empresa_cliente', 255)->nullable()->after('tipo');
        });

        DB::statement("ALTER TABLE sigua_accounts MODIFY COLUMN tipo ENUM('nominal','generica','servicio','prueba','desconocida','externo') DEFAULT 'desconocida'");

        DB::statement("ALTER TABLE sigua_cruce_resultados MODIFY COLUMN categoria ENUM(
            'ok_completo',
            'sin_cuenta_sistema',
            'cuenta_sin_rh',
            'generico_con_responsable',
            'generico_sin_responsable',
            'generica_sin_justificacion',
            'cuenta_baja_pendiente',
            'cuenta_servicio',
            'anomalia',
            'externo_sin_justificacion',
            'externo_con_justificacion',
            'por_clasificar'
        ) NOT NULL");
    }

    public function down(): void
    {
        Schema::table('sigua_accounts', function (Blueprint $table) {
            $table->dropColumn('empresa_cliente');
        });

        DB::statement("ALTER TABLE sigua_accounts MODIFY COLUMN tipo ENUM('nominal','generica','servicio','prueba','desconocida') DEFAULT 'desconocida'");

        DB::statement("ALTER TABLE sigua_cruce_resultados MODIFY COLUMN categoria ENUM(
            'ok_completo',
            'sin_cuenta_sistema',
            'cuenta_sin_rh',
            'generico_con_responsable',
            'generico_sin_responsable',
            'cuenta_baja_pendiente',
            'cuenta_servicio',
            'anomalia'
        ) NOT NULL");
    }
};
