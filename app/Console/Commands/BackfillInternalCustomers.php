<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;
use App\Services\InternalCustomerService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Console\Command;

/**
 * Crea el customer implícito (customers.is_internal=true) para operadores
 * existentes que completaron onboarding antes de la Fase 2 del sprint
 * maestro, y liga hacia él cualquier sede huérfana (customer_id NULL) que ya
 * exista bajo el mismo client_id.
 *
 * Dos pasos independientes, cada corrida:
 * 1. Por operador SIN customer interno -> crearlo (InternalCustomerService,
 *    idempotente).
 * 2. Por TODO customer interno que ya exista (recién creado en el paso 1 o
 *    de una corrida anterior) -> ligar sedes huérfanas de su client_id. Esto
 *    corre siempre, no solo para los operadores procesados en el paso 1,
 *    porque una sede puede quedar huérfana después de que el customer ya
 *    existía (insert directo, seed, etc.).
 *
 * Alcance: SOLO sedes cuyo client_id ya es el del Client interno del
 * operador. Sedes de customers externos (MSP) no se tocan aquí -- esos
 * clients no tienen un Customer "default" definido todavía (eso es Fase 6,
 * alta de Customers/Sites en onboarding).
 *
 * Reemplaza el comando de "Opción B" (marcaba clients.is_internal). Idempotente.
 */
class BackfillInternalCustomers extends Command
{
    protected $signature = 'tenants:backfill-internal-customer {--dry-run : Solo reporta, no escribe nada}';

    protected $description = 'Crea el customer interno implícito para operadores que no lo tienen y liga sedes huérfanas de su client_id';

    public function handle(InternalCustomerService $service): int
    {
        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->createMissingInternalCustomers($service, $dryRun);
        $this->linkOrphanSites($dryRun);

        if ($dryRun) {
            $this->info('Dry-run: nada escrito. Vuelve a correr sin --dry-run para aplicar.');
        } else {
            $this->info('Listo.');
        }

        return self::SUCCESS;
    }

    private function createMissingInternalCustomers(InternalCustomerService $service, bool $dryRun): void
    {
        $operators = User::where('is_operator', true)
            ->whereDoesntHave('clients.customers', fn ($q) => $q->where('is_internal', true))
            ->with('operatorProfile:user_id,business_name')
            ->orderBy('id')
            ->get();

        if ($operators->isEmpty()) {
            $this->info('Todos los operadores ya tienen su customer interno.');

            return;
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
            $this->line("  operador #{$operator->id} ({$operator->email}) → customer #{$customer->id} \"{$customer->name}\" (client #{$customer->client_id})");
        }
    }

    private function linkOrphanSites(bool $dryRun): void
    {
        $internalCustomers = Customer::where('is_internal', true)->get(['id', 'client_id']);

        $totalOrphans = Site::whereIn('client_id', $internalCustomers->pluck('client_id'))
            ->whereNull('customer_id')
            ->count();

        if ($totalOrphans === 0) {
            return;
        }

        if ($dryRun) {
            $this->line("[dry-run] {$totalOrphans} sede(s) huérfana(s) bajo un client interno se ligarían a su customer.");

            return;
        }

        foreach ($internalCustomers as $customer) {
            $updated = Site::where('client_id', $customer->client_id)
                ->whereNull('customer_id')
                ->update(['customer_id' => $customer->id]);

            if ($updated > 0) {
                $this->line("  → {$updated} sede(s) del client #{$customer->client_id} ligada(s) al customer #{$customer->id}.");
            }
        }
    }
}
