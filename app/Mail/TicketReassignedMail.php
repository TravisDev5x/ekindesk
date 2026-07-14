<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Al NUEVO responsable de un ticket reasignado (TicketController::assign()
 * cuando el ticket ya tenía responsable, ver TicketPolicy::reassign()). El
 * responsable anterior solo recibe TicketReassignedNotification in-app, sin
 * correo -- decisión de Fase 5, ver TicketController::notifyPreviousAssignee().
 */
class TicketReassignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public User $actor)
    {
        $this->ticket->loadMissing('client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: TenantMailSender::resolve($this->ticket->client),
            subject: "Ticket #{$this->ticket->folio} reasignado a ti",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tickets.reassigned',
            with: [
                'ticket' => $this->ticket,
                'actor' => $this->actor,
                'ticketUrl' => url("/resolbeb/tickets/{$this->ticket->id}"),
            ],
        );
    }
}
