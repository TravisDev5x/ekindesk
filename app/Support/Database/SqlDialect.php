<?php

namespace App\Support\Database;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Expresiones SQL portables entre MySQL, PostgreSQL y SQLite.
 */
class SqlDialect
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    /** Columna + N horas <= instante (SLA sin due_at). */
    public static function createdAtPlusHoursLte(string $createdColumn, int $hours, string $compareTo = '?'): string
    {
        $created = self::qualify($createdColumn);

        return match (self::driver()) {
            'pgsql' => "({$created} + interval '{$hours} hours') <= {$compareTo}",
            'sqlite' => "datetime({$created}, '+{$hours} hours') <= {$compareTo}",
            default => "DATE_ADD({$created}, INTERVAL {$hours} HOUR) <= {$compareTo}",
        };
    }

    /** Segundos entre dos columnas / expresiones, dividido por 3600 → horas. */
    public static function avgHoursBetween(string $startColumn, string $endColumn): string
    {
        $start = self::qualify($startColumn);
        $end = self::qualify($endColumn);

        return match (self::driver()) {
            'pgsql' => "AVG(EXTRACT(EPOCH FROM ({$end} - {$start})) / 3600)",
            'sqlite' => "AVG((strftime('%s', {$end}) - strftime('%s', {$start})) / 3600.0)",
            default => "AVG(TIMESTAMPDIFF(SECOND, {$start}, {$end}) / 3600)",
        };
    }

    /** COALESCE(due_at, created_at + SLA horas) para ordenación. */
    public static function coalesceDueOrSlaDeadline(string $dueColumn, string $createdColumn, int $slaHours): Expression
    {
        $due = self::qualify($dueColumn);
        $created = self::qualify($createdColumn);

        $fallback = match (self::driver()) {
            'pgsql' => "({$created} + interval '{$slaHours} hours')",
            'sqlite' => "datetime({$created}, '+{$slaHours} hours')",
            default => "DATE_ADD({$created}, INTERVAL {$slaHours} HOUR)",
        };

        return DB::raw("COALESCE({$due}, {$fallback})");
    }

    private static function qualify(string $column): string
    {
        return str_contains($column, '.') || str_contains($column, '(')
            ? $column
            : $column;
    }
}
