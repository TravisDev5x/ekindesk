<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sites.customer_id (nullable): a qué Customer pertenece la sede.
 *
 * client_id NO se toca -- sigue siendo el ancla de RLS de sites (las
 * políticas ya existentes lo usan directamente); customer_id es una
 * subdivisión adicional dentro del mismo client_id, no un reemplazo.
 * La consistencia site.customer.client_id === site.client_id se valida en
 * Site::booted() (app/Models/Site.php), no aquí -- un CHECK constraint con
 * subquery entre tablas no es portable entre Postgres/SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('client_id')
                ->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
