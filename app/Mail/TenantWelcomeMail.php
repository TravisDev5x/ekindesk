<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bienvenida cuando se activa un tenant nuevo (post-validación SAT).
 *
 * No existe todavía un flujo de activación de tenant que dispare esto — la
 * auditoría previa a este sprint no encontró ningún paso de "validación SAT" /
 * activación de tenant en el código (el onboarding completo es el siguiente
 * sprint). Este Mailable queda listo para usarse en cuanto ese flujo exista;
 * por ahora no hay ningún sitio que lo dispare.
 */
class TenantWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public string $recipientEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: TenantMailSender::resolve($this->client),
            subject: "¡Bienvenido a Tikara, {$this->client->name}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-welcome',
            with: [
                'client' => $this->client,
                'portalUrl' => $this->portalUrl(),
            ],
        );
    }

    private function portalUrl(): ?string
    {
        if (! $this->client->portal_slug || ! config('tenancy.base_domain')) {
            return null;
        }

        $scheme = config('tenancy.portal_scheme', 'https');

        return "{$scheme}://{$this->client->portal_slug}.".config('tenancy.base_domain');
    }
}
