<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Ticket;
use App\Services\TicketCreationService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Console\Command;

/**
 * Asigna folio retroactivamente a tickets creados sin uno (bug histórico de
 * MyTicketsController::store(), que no asignaba folio).
 *
 * Cada ticket pasa por el mismo mecanismo atómico y el mismo formato nuevo
 * (TK-{Letra}{Num5}-{PREFIX}-{Rand5}) que la creación normal — vía
 * TicketCreationService::nextFolioFor() — uno por uno en orden cronológico
 * real (created_at ASC, id como desempate). Si el cliente aún no tiene
 * ticket_prefix, nextFolioFor lo deriva y asigna en el momento.
 *
 * No renumera folios ya existentes: si un ticket sin folio quedó cronológicamente
 * intercalado entre tickets ya numerados, recibe el siguiente número disponible
 * de la secuencia de su tenant (no se re-secuencian los ya asignados).
 */
class BackfillTicketFolios extends Command
{
    protected $signature = 'tickets:backfill-folios {--dry-run : Solo reporta, no escribe nada}';

    protected $description = 'Asigna folio (formato nuevo, vía TicketCreationService) a tickets existentes con folio NULL';

    public function handle(TicketCreationService $ticketCreation): int
    {
        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }

        $dryRun = (bool) $this->option('dry-run');

        $withoutClient = Ticket::whereNull('folio')->whereNull('client_id')->count();
        if ($withoutClient > 0) {
            $this->warn("{$withoutClient} ticket(s) con folio NULL Y client_id NULL — no se pueden asignar folio (sin tenant al que pertenecer). Se omiten; revísalos manualmente.");
        }

        $tickets = Ticket::whereNull('folio')
            ->whereNotNull('client_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'client_id', 'created_at', 'folio']);

        if ($tickets->isEmpty()) {
            $this->info('No hay tickets con folio NULL (y client_id asignado) por procesar.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Procesando {$tickets->count()} ticket(s) sin folio, en orden cronológico...");

        $clients = Client::whereIn('id', $tickets->pluck('client_id')->unique())->get()->keyBy('id');

        $assigned = 0;
        foreach ($tickets as $ticket) {
            if ($dryRun) {
                // No se llama nextFolioFor en dry-run: consumiría números de la
                // secuencia y podría asignar prefijos (escrituras reales).
                $prefix = $clients[$ticket->client_id]->ticket_prefix ?? '(se derivará)';
                $this->line("  ticket #{$ticket->id} (client_id={$ticket->client_id}, created_at={$ticket->created_at}) → siguiente folio de la secuencia, prefijo {$prefix}");

                continue;
            }

            $folio = $ticketCreation->nextFolioFor($clients[$ticket->client_id]);
            $ticket->forceFill(['folio' => $folio])->saveQuietly();
            $assigned++;
        }

        if ($dryRun) {
            $this->info('Dry-run: nada escrito. Vuelve a correr sin --dry-run para aplicar.');

            return self::SUCCESS;
        }

        $this->info("Listo: {$assigned} ticket(s) recibieron folio nuevo.");

        return self::SUCCESS;
    }
}
