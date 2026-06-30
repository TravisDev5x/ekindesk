<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketClosed
{
    use Dispatchable, SerializesModels;

    // TODO: Sprint 1
    // Listeners:
    //   - SendTicketClosedNotification: email al solicitante "Tu caso #XXXXX fue cerrado"
    //   - TriggerOutboundWebhooks: evento ticket.closed
    //
    // Constructor: public function __construct(public readonly \App\Models\Ticket $ticket) {}
}
