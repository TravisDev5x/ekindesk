<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * folio pasa a NOT NULL una vez que:
 * 1. `php artisan tickets:backfill-folios` corrió y ya no hay folios NULL
 *    históricos (bug de MyTicketsController::store(), ver Paso 2 del sprint).
 * 2. MyTicketsController::store() ya llama a TicketSequence::nextFor() antes de
 *    crear el ticket (Paso 2), así que no deberían crearse tickets nuevos sin
 *    folio a partir de ahora.
 *
 * up() verifica en runtime que no queden folios NULL antes de aplicar el
 * constraint — si el backfill no corrió (o corrió mal) en este entorno, falla
 * con un mensaje claro en vez de dejar el constraint a medias.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'folio')) {
            return;
        }

        $stillNull = DB::table('tickets')->whereNull('folio')->count();
        if ($stillNull > 0) {
            throw new RuntimeException(
                "No se puede aplicar folio NOT NULL: aún hay {$stillNull} ticket(s) con folio NULL. ".
                'Corre `php artisan tickets:backfill-folios` primero.'
            );
        }

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE tickets ALTER COLUMN folio SET NOT NULL'),
            'mysql' => DB::statement('ALTER TABLE tickets MODIFY folio VARCHAR(10) NOT NULL'),
            'sqlite' => $this->sqliteSetFolioNotNull(),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tickets', 'folio')) {
            return;
        }

        match (Schema::getConnection()->getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE tickets ALTER COLUMN folio DROP NOT NULL'),
            'mysql' => DB::statement('ALTER TABLE tickets MODIFY folio VARCHAR(10) NULL'),
            'sqlite' => null, // nunca se aplicó ahí (ver sqliteSetFolioNotNull)
            default => null,
        };
    }

    private function sqliteSetFolioNotNull(): void
    {
        try {
            DB::statement('ALTER TABLE tickets ALTER COLUMN folio SET NOT NULL');
        } catch (\Throwable) {
            // SQLite no soporta ALTER COLUMN ... SET NOT NULL (mismo caso que
            // client_id en 2026_07_09_000027) — el constraint solo se enforcea
            // realmente en Postgres/MySQL; en SQLite (tests) queda nullable.
        }
    }
};
