<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Ticket;
use App\Models\TicketSequence;
use App\Models\User;
use App\Services\Classification\TicketClassifierService;
use App\Services\Tenant\TenantContextService;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessInboundTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 60;

    public function __construct(
        private int   $clientId,
        private array $parsedEmail
    ) {}

    public function handle(TicketClassifierService $classifier): void
    {
        $tenant = Cliente::find($this->clientId);
        if (! $tenant) {
            Log::error('ProcessInboundTicket: tenant no encontrado', [
                'client_id' => $this->clientId,
            ]);
            return;
        }

        // Jobs son código confiable — bypass RLS para operar como sistema
        if (PgsqlRowLevelSecurity::enabled()) {
            PgsqlRowLevelSecurity::setBypass(true);
        }
        TenantContextService::set($tenant);

        DB::transaction(function () use ($tenant, $classifier) {
            $folio = TicketSequence::nextFor($this->clientId);

            $classification = $classifier->classify(
                subject:  $this->parsedEmail['subject'],
                body:     $this->parsedEmail['body_plain'],
                clientId: $this->clientId
            );

            $requester = $this->findOrCreateRequester($tenant);

            // Lookup required NOT-NULL catalog IDs
            $defaultAreaId = DB::table('areas')
                ->where(fn ($q) => $q->whereNull('client_id')->orWhere('client_id', $this->clientId))
                ->orderByRaw('client_id IS NULL')  // tenant-specific first, then global
                ->orderBy('id')
                ->value('id');

            $defaultStateId = DB::table('ticket_states')
                ->where(fn ($q) => $q->whereNull('client_id')->orWhere('client_id', $this->clientId))
                ->where(fn ($q) => $q->whereNull('is_final')->orWhere('is_final', false))
                ->orderBy('id')
                ->value('id');

            if (! $defaultAreaId || ! $defaultStateId) {
                throw new \RuntimeException(
                    "Tenant {$this->clientId}: sin área o estado disponible para el ticket."
                );
            }

            $ticket = Ticket::create([
                'client_id'        => $this->clientId,
                'folio'            => $folio,
                'source'           => 'email',
                'origin_message_id'=> $this->parsedEmail['message_id'] ?: null,
                'subject'          => mb_substr($this->parsedEmail['subject'], 0, 255),
                'description'      => $this->parsedEmail['body_plain'],
                'requester_id'     => $requester->id,
                'sede_id'          => $requester->sede_id,
                'area_origin_id'   => $defaultAreaId,
                'area_current_id'  => $defaultAreaId,
                'ticket_state_id'  => $defaultStateId,
                'ticket_type_id'   => $classification['ticket_type_id'] ?? null,
                'priority_id'      => $classification['priority_id'] ?? null,
            ]);

            Log::info('Tikara: ticket creado por email', [
                'folio'      => $folio,
                'ticket_id'  => $ticket->id,
                'client_id'  => $this->clientId,
                'from'       => $this->parsedEmail['from'],
                'subject'    => $this->parsedEmail['subject'],
                'classifier' => $classification['source'],
            ]);

            // TODO Sprint 3: mapear classification['category'] → ticket_type_id, priority_id
            // event(new \App\Events\TicketCreated($ticket));
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInboundTicket: job fallido', [
            'client_id' => $this->clientId,
            'from'      => $this->parsedEmail['from'] ?? 'unknown',
            'error'     => $exception->getMessage(),
        ]);
    }

    /**
     * Busca o crea el usuario solicitante a partir del email entrante.
     *
     * sede_id es NOT NULL en users: usa la primera sede activa del tenant.
     * Si el tenant no tiene sedes, lanza excepción (el job reintentará).
     */
    private function findOrCreateRequester(Cliente $tenant): User
    {
        $sedeId = DB::table('sites')
            ->where('client_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if (! $sedeId) {
            throw new \RuntimeException(
                "Tenant {$tenant->id} no tiene sedes activas — no se puede crear requester."
            );
        }

        $fromName = $this->parsedEmail['from_name']
            ?: explode('@', $this->parsedEmail['from'])[0];

        return User::firstOrCreate(
            [
                'email'     => $this->parsedEmail['from'],
                'client_id' => $this->clientId,
            ],
            [
                'first_name'           => $fromName,
                'sede_id'              => $sedeId,
                'client_id'            => $this->clientId,
                'password'             => \Hash::make(\Str::random(32)),
                'status'               => 'active',
                'onboarding_completed' => true,
                'email_verified_at'    => now(),
            ]
        );
    }
}
