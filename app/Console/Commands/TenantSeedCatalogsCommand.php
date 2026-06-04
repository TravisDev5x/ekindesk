<?php

namespace App\Console\Commands;

use App\Services\OperatorCatalogSeedService;
use Illuminate\Console\Command;

class TenantSeedCatalogsCommand extends Command
{
    protected $signature = 'tenant:seed-catalogs
                            {operator_user_id : ID del usuario operador MSP (is_operator)}
                            {--client= : client_id cuando TENANCY_CATALOG_PER_CLIENT=true}
                            {--only= : Tablas separadas por coma (areas,priorities,...)}
                            {--dry-run : Simula el resultado sin escribir en BD}';

    protected $description = 'Clona catálogos de plataforma al operador MSP (idempotente; compatible con herencia de filas globales)';

    public function handle(OperatorCatalogSeedService $seedService): int
    {
        try {
            $operator = $seedService->resolveOperator((int) $this->argument('operator_user_id'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $clientId = $this->option('client') !== null ? (int) $this->option('client') : null;
        $only = $this->parseOnlyOption($this->option('only'));

        try {
            $summary = $seedService->seedForOperator(
                $operator,
                (bool) $this->option('dry-run'),
                $clientId,
                $only
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach ($summary['by_table'] as $table => $counts) {
            $rows[] = [
                $table,
                $counts['created'],
                $counts['skipped_existing'],
                $counts['skipped_inherited'],
                $counts['failed'],
            ];
        }

        $this->table(
            ['Tabla', 'Creadas', 'Ya existían', 'Heredadas (plataforma)', 'Errores'],
            $rows
        );

        $this->newLine();
        $this->info(sprintf(
            'Operador #%d — total: %d creadas, %d ya existían, %d heredadas de plataforma, %d errores%s',
            $operator->id,
            $summary['created'],
            $summary['skipped_existing'],
            $summary['skipped_inherited'],
            $summary['failed'],
            $this->option('dry-run') ? ' (dry-run)' : ''
        ));

        if ($summary['skipped_inherited'] > 0 && $summary['created'] === 0) {
            $this->comment(
                'Las filas heredadas siguen visibles para el operador vía catálogo de plataforma (operator_user_id NULL).'
            );
        }

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<string>|null */
    private function parseOnlyOption(?string $only): ?array
    {
        if ($only === null || trim($only) === '') {
            return null;
        }

        return array_values(array_filter(array_map(
            fn (string $item) => strtolower(trim($item)),
            explode(',', $only)
        )));
    }
}
