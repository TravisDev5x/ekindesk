<?php

namespace App\Console\Commands;

use App\Support\Database\TenantIntegrity;
use Illuminate\Console\Command;
use RuntimeException;

class TenantClientIdCommand extends Command
{
    protected $signature = 'tenant:client-id
                            {action=verify : verify (solo comprueba) o sync (backfill + opcional sedes)}
                            {--assign-sites : Asigna cliente PLATFORM a sedes sin client_id antes del backfill}
                            {--strict : Falla si hay huérfanos o client_id NULL en tickets/incidencias (CI/release)}';

    protected $description = 'Verifica o sincroniza client_id en tickets e incidencias desde sites (multi-tenant)';

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! in_array($action, ['verify', 'sync'], true)) {
            $this->error('Acción inválida. Usa: verify o sync');

            return self::FAILURE;
        }

        if ($action === 'sync') {
            return $this->runSync();
        }

        return $this->runVerify();
    }

    private function runVerify(): int
    {
        $orphans = TenantIntegrity::orphanCounts();
        $nullTickets = TenantIntegrity::ticketsWithNullClientCount();
        $nullIncidents = TenantIntegrity::incidentsWithNullClientCount();
        $sitesWithout = TenantIntegrity::sitesWithoutClientCount();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Tickets huérfanos (sede con cliente, ticket sin)', $orphans['tickets']],
                ['Incidencias huérfanas', $orphans['incidents']],
                ['Tickets con client_id NULL', $nullTickets],
                ['Incidencias con client_id NULL', $nullIncidents],
                ['Sedes sin client_id', $sitesWithout],
            ]
        );

        try {
            if ($this->option('strict')) {
                TenantIntegrity::assertReadyForNotNull();
                $this->info('OK (strict): integridad tenant completa; sin huérfanos ni client_id NULL.');
            } else {
                TenantIntegrity::assertSynced();
                $this->info('OK: no hay filas huérfanas respecto a sites.client_id.');

                if ($nullTickets === 0 && $nullIncidents === 0) {
                    $this->info('OK: tickets e incidencias listos para client_id NOT NULL.');
                } else {
                    $this->warn('Aún hay filas con client_id NULL (sedes sin cliente o datos legacy). Usa sync --assign-sites si procede.');
                }
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runSync(): int
    {
        $result = TenantIntegrity::syncAll($this->option('assign-sites'));

        $this->info(sprintf(
            'Sedes actualizadas: %d | Tickets: %d | Incidencias: %d',
            $result['sites_updated'],
            $result['tickets_updated'],
            $result['incidents_updated']
        ));

        return $this->runVerify();
    }
}
