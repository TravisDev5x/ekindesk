<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReopened
{
    use Dispatchable, SerializesModels;

    // TODO: Sprint 1
    // Se dispara cuando un usuario responde por email a un ticket cerrado.
    // Listeners:
    //   - NotifyAssignedAgent: avisa al agente que el ticket fue reabierto
    //   - TriggerOutboundWebhooks: evento ticket.reopened
    //
    // Constructor: public function __construct(public readonly \App\Models\Ticket $ticket) {}
}
