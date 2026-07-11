<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica que hay una respuesta nueva en un ticket, al destinatario correcto
 * (agente o solicitante) — la decisión de A QUIÉN se le manda vive en el
 * listener (SendTicketNotification), no aquí; este Mailable solo arma el
 * contenido. Se dispara desde TicketUpdated, disparado tanto por
 * ProcessInboundReply (email) como por MyTicketsController::addComment
 * (portal) desde el sprint de unificación (Paso 3).
 */
class TicketReplyNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing('client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: TenantMailSender::resolve($this->ticket->client),
            subject: "Nueva respuesta en el ticket #{$this->ticket->folio}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.reply-notification',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => TicketMailUrl::resolve($this->ticket),
            ],
        );
    }
}
