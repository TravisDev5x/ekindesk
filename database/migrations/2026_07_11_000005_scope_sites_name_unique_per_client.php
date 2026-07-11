<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sites.name era UNIQUE global — dos customers distintos no podían tener
 * ambos una sede llamada "Oficina Central". Pasa a unicidad compuesta
 * (client_id, name). Sites globales (client_id NULL) siguen pudiendo
 * repetir nombre entre sí en Postgres (NULLs distintos) — irrelevante en
 * la práctica: solo existe el site global "Remoto" del seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['client_id', 'name'], 'sites_client_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique('sites_client_name_unique');
            $table->unique('name');
        });
    }
};
