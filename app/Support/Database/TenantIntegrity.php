<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Verificación de integridad tenant: client_id en hijos alineado con sites.client_id.
 */
class TenantIntegrity
{
    public const PLATFORM_CLIENT_CODE = 'PLATFORM';

    /**
     * Filas con sede que ya tiene cliente pero el hijo no tiene client_id denormalizado.
     */
    public static function orphanTicketCount(): int
    {
        return self::orphanCount('tickets');
    }

    public static function orphanIncidentCount(): int
    {
        return self::orphanCount('incidents');
    }

    public static function orphanCounts(): array
    {
        return [
            'tickets' => self::orphanTicketCount(),
            'incidents' => self::orphanIncidentCount(),
        ];
    }

    public static function ticketsWithNullClientCount(): int
    {
        return self::nullClientCount('tickets');
    }

    public static function incidentsWithNullClientCount(): int
    {
        return self::nullClientCount('incidents');
    }

    public static function sitesWithoutClientCount(): int
    {
        if (! Schema::hasTable('sites') || ! Schema::hasColumn('sites', 'client_id')) {
            return 0;
        }

        return (int) DB::table('sites')->whereNull('client_id')->count();
    }

    /**
     * @throws RuntimeException
     */
    public static function assertSynced(): void
    {
        $orphans = self::orphanCounts();
        $total = $orphans['tickets'] + $orphans['incidents'];

        if ($total > 0) {
            throw new RuntimeException(sprintf(
                'Integridad tenant: %d ticket(s) y %d incidencia(s) con sede asignada a cliente pero client_id NULL. Ejecuta: php artisan tenant:client-id sync',
                $orphans['tickets'],
                $orphans['incidents']
            ));
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function assertReadyForNotNull(): void
    {
        self::assertSynced();

        $nullTickets = self::ticketsWithNullClientCount();
        $nullIncidents = self::incidentsWithNullClientCount();

        if ($nullTickets > 0 || $nullIncidents > 0) {
            throw new RuntimeException(sprintf(
                'No se puede aplicar client_id NOT NULL: %d ticket(s) y %d incidencia(s) sin client_id (%d sede(s) sin cliente). Asigna cliente a sedes o ejecuta tenant:client-id sync --assign-sites.',
                $nullTickets,
                $nullIncidents,
                self::sitesWithoutClientCount()
            ));
        }
    }

    /**
     * Cliente interno para sedes legacy (ej. Remoto) sin organización MSP.
     *
     * @return array{client_id: int, sites_updated: int}
     */
    public static function ensurePlatformClientOnSites(): array
    {
        if (! Schema::hasTable('clients')) {
            throw new RuntimeException('Tabla clients no existe.');
        }

        $clientId = DB::table('clients')->where('code', self::PLATFORM_CLIENT_CODE)->value('id');

        if (! $clientId) {
            $row = [
                'name' => 'Plataforma EkinDesk',
                'code' => self::PLATFORM_CLIENT_CODE,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('clients', 'operator_user_id')) {
                $row['operator_user_id'] = null;
            }
            $clientId = DB::table('clients')->insertGetId($row);
        }

        $sitesUpdated = DB::table('sites')->whereNull('client_id')->update([
            'client_id' => $clientId,
            'updated_at' => now(),
        ]);

        return ['client_id' => (int) $clientId, 'sites_updated' => $sitesUpdated];
    }

    public static function syncAll(bool $assignOrphanSites = false): array
    {
        if ($assignOrphanSites) {
            $platform = self::ensurePlatformClientOnSites();
        } else {
            $platform = ['client_id' => 0, 'sites_updated' => 0];
        }

        $tickets = TenantBackfill::syncClientIdFromSites('tickets');
        $incidents = Schema::hasColumn('incidents', 'client_id')
            ? TenantBackfill::syncClientIdFromSites('incidents')
            : 0;

        return [
            'sites_updated' => $platform['sites_updated'],
            'platform_client_id' => $platform['client_id'],
            'tickets_updated' => $tickets,
            'incidents_updated' => $incidents,
            'orphans' => self::orphanCounts(),
        ];
    }

    private static function orphanCount(string $childTable): int
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'client_id')) {
            return 0;
        }

        return (int) DB::table($childTable.' as c')
            ->join('sites as s', 's.id', '=', 'c.sede_id')
            ->whereNotNull('s.client_id')
            ->whereNull('c.client_id')
            ->count();
    }

    private static function nullClientCount(string $childTable): int
    {
        if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, 'client_id')) {
            return 0;
        }

        return (int) DB::table($childTable)->whereNull('client_id')->count();
    }
}
