<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InternalCustomerService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Console\Command;

/**
 * Crea el customer implícito (clients.is_internal=true) para operadores
 * existentes que completaron onboarding antes de la Fase 2 del sprint
 * maestro. Idempotente: operadores que ya lo tienen se saltan.
 */
class BackfillInternalCustomers extends Command
{
    protected $signature = 'tenants:backfill-internal-customer {--dry-run : Solo reporta, no escribe nada}';

    protected $description = 'Crea el customer interno implícito para operadores que no lo tienen';

    public function handle(InternalCustomerService $service): int
    {
        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }

        $dryRun = (bool) $this->option('dry-run');

        $operators = User::where('is_operator', true)
            ->whereDoesntHave('clients', fn ($q) => $q->where('is_internal', true))
            ->with('operatorProfile:user_id,business_name')
            ->orderBy('id')
            ->get();

        if ($operators->isEmpty()) {
            $this->info('Todos los operadores ya tienen su customer interno.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Procesando {$operators->count()} operador(es) sin customer interno...");

        foreach ($operators as $operator) {
            $name = $operator->operatorProfile?->business_name
                ?: trim($operator->first_name.' '.$operator->paternal_last_name);

            if ($dryRun) {
                $this->line("  operador #{$operator->id} ({$operator->email}) → customer interno \"{$name}\"");

                continue;
            }

            $customer = $service->ensureFor($operator);
            $this->line("  operador #{$operator->id} ({$operator->email}) → client #{$customer->id} \"{$customer->name}\"");
        }

        if ($dryRun) {
            $this->info('Dry-run: nada escrito. Vuelve a correr sin --dry-run para aplicar.');
        } else {
            $this->info('Listo.');
        }

        return self::SUCCESS;
    }
}
