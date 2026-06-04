<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills denormalized client_id (tenant key) from sites — portable across MySQL, PostgreSQL and SQLite.
 */
class TenantBackfill
{
    /**
     * Copy sites.client_id into child rows linked by sede_id (tickets, incidents, …).
     */
    public static function syncClientIdFromSites(string $childTable, string $sedeColumn = 'sede_id'): int
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'client_id')) {
            return 0;
        }

        $driver = Schema::getConnection()->getDriverName();

        return match ($driver) {
            'pgsql' => (int) DB::affectingStatement("
                UPDATE {$childTable} AS c
                SET client_id = s.client_id
                FROM sites AS s
                WHERE s.id = c.{$sedeColumn}
                  AND s.client_id IS NOT NULL
                  AND (c.client_id IS NULL OR c.client_id IS DISTINCT FROM s.client_id)
            "),
            'mysql' => (int) DB::affectingStatement("
                UPDATE {$childTable} AS c
                INNER JOIN sites AS s ON s.id = c.{$sedeColumn}
                SET c.client_id = s.client_id
                WHERE s.client_id IS NOT NULL
                  AND (c.client_id IS NULL OR c.client_id <> s.client_id)
            "),
            default => self::syncClientIdFromSitesSqlite($childTable, $sedeColumn),
        };
    }

    private static function syncClientIdFromSitesSqlite(string $childTable, string $sedeColumn): int
    {
        $rows = DB::table($childTable.' as c')
            ->join('sites as s', 's.id', '=', 'c.'.$sedeColumn)
            ->whereNotNull('s.client_id')
            ->where(function ($q) {
                $q->whereNull('c.client_id')
                    ->orWhereColumn('c.client_id', '<>', 's.client_id');
            })
            ->select('c.id', 's.client_id')
            ->get();

        $updated = 0;
        foreach ($rows as $row) {
            $updated += DB::table($childTable)
                ->where('id', $row->id)
                ->update(['client_id' => $row->client_id]);
        }

        return $updated;
    }
}
