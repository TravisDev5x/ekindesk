<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInboundReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    // TODO: Sprint 1
    // Agrega respuesta de email como comentario público en ticket existente.
    //
    // Constructor: public function __construct(array $parsedEmail, int $ticketId)
    //
    // handle():
    //   1. Encontrar ticket por ID (ya resuelto por InboundEmailService)
    //   2. Agregar TicketComment con body=parsedEmail['text'], is_public=true, source='email'
    //   3. Guardar adjuntos de la respuesta
    //   4. Reabrir ticket si ticket_state.is_final=true
    //   5. Dispatch TicketUpdated event (o TicketReopened si se reabrió)
    //   6. Notificar a los agentes asignados
}
