<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\TicketPrefixService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Console\Command;

/**
 * Asigna clients.ticket_prefix a clientes existentes (anteriores a la
 * columna). Los clientes nuevos lo reciben automáticamente al crearse
 * (Client::booted). Idempotente: los que ya tienen prefijo se saltan.
 */
class BackfillTicketPrefixes extends Command
{
    protected $signature = 'tenants:backfill-ticket-prefix {--dry-run : Solo reporta, no escribe nada}';

    protected $description = 'Deriva y asigna ticket_prefix a los clientes que no lo tienen';

    public function handle(TicketPrefixService $prefixService): int
    {
        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }

        $dryRun = (bool) $this->option('dry-run');

        $clients = Client::whereNull('ticket_prefix')->orderBy('id')->get();

        if ($clients->isEmpty()) {
            $this->info('Todos los clientes ya tienen ticket_prefix.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Procesando {$clients->count()} cliente(s) sin prefijo...");

        foreach ($clients as $client) {
            if ($dryRun) {
                // En dry-run los prefijos no se persisten, así que dos nombres
                // que colisionen mostrarían el mismo candidato — se anota.
                $candidate = $prefixService->uniquePrefixFor($client->name, $client->id);
                $this->line("  client #{$client->id} \"{$client->name}\" → {$candidate}");

                continue;
            }

            $prefix = $prefixService->assignTo($client);
            $this->line("  client #{$client->id} \"{$client->name}\" → {$prefix}");
        }

        if ($dryRun) {
            $this->info('Dry-run: nada escrito. Vuelve a correr sin --dry-run para aplicar.');
        } else {
            $this->info('Listo.');
        }

        return self::SUCCESS;
    }
}
