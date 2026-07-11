<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El formato nuevo de folio (TK-A00042-SYD-88291) no cabe en varchar(10).
 * 30 da margen para prefijos largos (hasta 10) sin acercarse al límite.
 * En SQLite el largo de varchar no se aplica (afinidad TEXT) — no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'folio')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE tickets ALTER COLUMN folio TYPE varchar(30)'),
            'mysql' => DB::statement('ALTER TABLE tickets MODIFY folio VARCHAR(30) NOT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        // No se revierte a varchar(10): con folios en formato nuevo ya
        // escritos, encoger truncaría o fallaría. Ancho de 30 es inocuo.
    }
};
