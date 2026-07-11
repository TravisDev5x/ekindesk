<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca al "customer implícito" del tenant: la fila de clients que representa
 * a la propia empresa del operador (modalidad IT Internal, o la parte interna
 * de Hybrid). Siempre existe uno por operador tras el onboarding — un solo
 * modelo de datos para las 3 modalidades, sin ramas de código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false);
            $table->index(['operator_user_id', 'is_internal'], 'clients_operator_internal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_operator_internal_idx');
            $table->dropColumn('is_internal');
        });
    }
};
