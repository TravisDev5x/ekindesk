<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Se envía cuando alguien manda un correo al buzón de soporte de un tenant
 * pero su email no está registrado como usuario de ESE tenant (ni
 * registrado en absoluto, ni registrado en otro). Política: un email = un
 * tenant, y solo usuarios ya registrados/invitados pueden generar tickets
 * por correo — nada de cuentas guest implícitas.
 *
 * Remitente fijo noreply@tikara.mx (no TenantMailSender): es un rechazo de
 * sistema, no una respuesta de soporte del tenant.
 */
class RegistrationRequiredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public Client $tenant
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@tikara.mx', 'Tikara'),
            subject: 'No pudimos crear tu ticket',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration-required',
            with: ['tenant' => $this->tenant],
        );
    }
}
