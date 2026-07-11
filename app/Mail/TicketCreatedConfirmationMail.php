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
 * Confirma al solicitante que su ticket se registró, con folio y link.
 * Se dispara desde SendTicketNotification al escuchar TicketCreated — funciona
 * igual sin importar si el ticket vino por email (ProcessInboundTicket) o
 * portal (MyTicketsController::store), ya que ambos pasan por el mismo evento
 * desde el sprint de unificación (Paso 3).
 */
class TicketCreatedConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing('client', 'requester');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: TenantMailSender::resolve($this->ticket->client),
            subject: "Ticket #{$this->ticket->folio} recibido",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.created-confirmation',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => TicketMailUrl::resolve($this->ticket),
            ],
        );
    }
}
