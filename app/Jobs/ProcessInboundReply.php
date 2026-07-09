<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\TicketState;
use App\Models\User;
use App\Services\Tenant\TenantContextService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInboundReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(
        private int    $clientId,
        private string $folio,
        private array  $parsedEmail
    ) {}

    public function handle(): void
    {
        $tenant = Client::find($this->clientId);
        if (! $tenant) {
            return;
        }

        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }
        TenantContextService::set($tenant);

        $ticket = Ticket::where('client_id', $this->clientId)
            ->where('folio', $this->folio)
            ->first();

        if (! $ticket) {
            Log::warning('ProcessInboundReply: ticket no encontrado', [
                'folio'     => $this->folio,
                'client_id' => $this->clientId,
            ]);
            return;
        }

        DB::transaction(function () use ($ticket) {
            // Buscar actor por email (puede no existir como usuario)
            $actor = User::where('email', $this->parsedEmail['from'])
                ->where('client_id', $this->clientId)
                ->first();

            $actorId = $actor?->id ?? $ticket->requester_id;

            TicketHistory::create([
                'ticket_id'   => $ticket->id,
                'actor_id'    => $actorId,
                'action'      => 'email_reply',
                'note'        => mb_substr($this->parsedEmail['body_plain'], 0, 5000),
                'is_internal' => false,
            ]);

            // Reabrir si el ticket está en estado final
            if ($ticket->state?->is_final) {
                $openState = TicketState::where('is_final', false)
                    ->orderBy('id')
                    ->first();

                if ($openState) {
                    $ticket->update(['ticket_state_id' => $openState->id]);

                    TicketHistory::create([
                        'ticket_id'   => $ticket->id,
                        'actor_id'    => $actorId,
                        'action'      => 'reopened_by_email',
                        'note'        => 'Reabierto automáticamente por respuesta de email.',
                        'is_internal' => true,
                    ]);
                }
            }

            Log::info('Tikara: respuesta de email agregada a ticket', [
                'folio'     => $this->folio,
                'ticket_id' => $ticket->id,
                'client_id' => $this->clientId,
                'from'      => $this->parsedEmail['from'],
            ]);

            // TODO Sprint 3: event(new \App\Events\TicketUpdated($ticket));
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInboundReply: job fallido', [
            'client_id' => $this->clientId,
            'folio'     => $this->folio,
            'error'     => $exception->getMessage(),
        ]);
    }
}
