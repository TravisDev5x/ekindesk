<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('clients')
                ->nullOnDelete();
        });

        DB::statement('
            UPDATE tickets
            INNER JOIN sites ON sites.id = tickets.sede_id
            SET tickets.client_id = sites.client_id
            WHERE sites.client_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
    }
};
