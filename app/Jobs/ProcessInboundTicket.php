<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInboundTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    // TODO: Sprint 1
    // Crea ticket desde email inbound (Mailgun webhook).
    //
    // Constructor: public function __construct(array $parsedEmail, int $clientId)
    //
    // handle():
    //   1. Buscar o crear Contact por email del remitente
    //   2. Asignar folio secuencial del tenant (SELECT nextval('tenant_folio_seq'))
    //   3. Clasificar con TicketClassifierService (3 capas)
    //   4. Crear Ticket con source='email', origin_message_id del header
    //   5. Guardar adjuntos en Storage::disk('attachments')
    //   6. Dispatch TicketCreated event → notifica al solicitante con folio
}
