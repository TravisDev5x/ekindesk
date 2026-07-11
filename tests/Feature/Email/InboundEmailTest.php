<?php

namespace Tests\Feature\Email;

use App\Models\Client;
use App\Models\TicketSequence;
use App\Services\Email\InboundEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundEmailTest extends TestCase
{
    use RefreshDatabase;

    private InboundEmailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InboundEmailService();
    }

    public function test_parse_mailgun_payload(): void
    {
        $payload = [
            'sender'      => 'usuario@empresa.com',
            'from'        => 'Juan Pérez <usuario@empresa.com>',
            'recipient'   => 'soporte@techsolve.tikara.mx',
            'subject'     => 'No puedo entrar a mi correo',
            'body-plain'  => 'Desde ayer no puedo acceder a Outlook.',
            'Message-Id'  => '<abc123@mail.empresa.com>',
            'In-Reply-To' => '',
        ];

        $parsed = $this->service->parse($payload);

        $this->assertEquals('usuario@empresa.com', $parsed['from']);
        $this->assertEquals('Juan Pérez', $parsed['from_name']);
        $this->assertEquals('soporte@techsolve.tikara.mx', $parsed['to']);
        $this->assertEquals('No puedo entrar a mi correo', $parsed['subject']);
        $this->assertEquals('Desde ayer no puedo acceder a Outlook.', $parsed['body_plain']);
        $this->assertEquals('<abc123@mail.empresa.com>', $parsed['message_id']);
    }

    public function test_detect_folio_from_reply_to_header(): void
    {
        $parsed = [
            'subject'     => 'Re: Tu caso fue recibido',
            'in_reply_to' => '<ticket-TK-A00042-SYD-88291@techsolve.tikara.mx>',
        ];

        $this->assertEquals('TK-A00042-SYD-88291', $this->service->detectFolio($parsed));
    }

    public function test_detect_folio_from_subject_brackets(): void
    {
        $parsed = [
            'subject'     => 'Re: [#TK-B00015-ACME-01234] Problema con impresora',
            'in_reply_to' => '',
        ];

        $this->assertEquals('TK-B00015-ACME-01234', $this->service->detectFolio($parsed));
    }

    public function test_detect_folio_from_subject_hash(): void
    {
        $parsed = [
            'subject'     => 'Re: Seguimiento #TK-A00099-TST2-55555',
            'in_reply_to' => '',
        ];

        $this->assertEquals('TK-A00099-TST2-55555', $this->service->detectFolio($parsed));
    }

    public function test_no_folio_for_new_ticket(): void
    {
        $parsed = [
            'subject'     => 'Mi computadora no enciende',
            'in_reply_to' => '',
        ];

        $this->assertNull($this->service->detectFolio($parsed));
    }

    public function test_old_five_digit_folio_format_is_not_detected(): void
    {
        // Formato viejo retirado sin compatibilidad: no existía ni un folio
        // en formato viejo en ninguna base al momento del cambio.
        $parsed = [
            'subject'     => 'Re: [#00015] Problema con impresora',
            'in_reply_to' => '<ticket-00042@techsolve.tikara.mx>',
        ];

        $this->assertNull($this->service->detectFolio($parsed));
    }

    public function test_ticket_sequence_increments_atomically(): void
    {
        $tenant = Client::factory()->create([
            'portal_slug' => 'test-seq',
            'is_active'   => true,
        ]);

        $n1 = TicketSequence::nextNumberFor($tenant->id);
        $n2 = TicketSequence::nextNumberFor($tenant->id);
        $n3 = TicketSequence::nextNumberFor($tenant->id);

        $this->assertSame(1, $n1);
        $this->assertSame(2, $n2);
        $this->assertSame(3, $n3);
    }

    public function test_ticket_sequences_are_isolated_per_tenant(): void
    {
        $tenantA = Client::factory()->create(['portal_slug' => 'tenant-a', 'is_active' => true]);
        $tenantB = Client::factory()->create(['portal_slug' => 'tenant-b', 'is_active' => true]);

        $nA1 = TicketSequence::nextNumberFor($tenantA->id);
        $nA2 = TicketSequence::nextNumberFor($tenantA->id);
        $nB1 = TicketSequence::nextNumberFor($tenantB->id);

        // Tenant A va por su propia secuencia
        $this->assertSame(1, $nA1);
        $this->assertSame(2, $nA2);

        // Tenant B empieza en 1, independiente de A
        $this->assertSame(1, $nB1);
    }

    public function test_resolve_tenant_from_tikara_subdomain(): void
    {
        config(['tenancy.base_domain' => 'tikara.mx']);

        $tenant = Client::factory()->create([
            'portal_slug' => 'miempresa',
            'is_active'   => true,
        ]);

        $resolved = $this->service->resolveTenant('soporte@miempresa.tikara.mx');

        $this->assertNotNull($resolved);
        $this->assertEquals($tenant->id, $resolved->id);
    }

    public function test_resolve_tenant_returns_null_for_unknown_domain(): void
    {
        config(['tenancy.base_domain' => 'tikara.mx']);

        $resolved = $this->service->resolveTenant('soporte@fantasma.tikara.mx');

        $this->assertNull($resolved);
    }

    public function test_mailgun_signature_allowed_in_local(): void
    {
        $payload = [
            'timestamp' => time(),
            'token'     => 'test-token',
            'signature' => 'invalid',
        ];

        // Sin key configurada en local → permite siempre
        $this->assertTrue($this->service->verifyMailgunSignature($payload));
    }

    public function test_mailgun_signature_rejected_without_key_outside_local(): void
    {
        $this->app['env'] = 'production';

        $payload = [
            'timestamp' => time(),
            'token'     => 'test-token',
            'signature' => 'invalid',
        ];

        // Sin key configurada y fuera de local/testing → falla cerrado (rechaza).
        $this->assertFalse($this->service->verifyMailgunSignature($payload));
    }

    public function test_mailgun_signature_valid_with_key_configured(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'a-real-signing-key']);

        $timestamp = (string) time();
        $token = 'abc123token';
        $signature = hash_hmac('sha256', $timestamp.$token, 'a-real-signing-key');

        $payload = [
            'timestamp' => $timestamp,
            'token'     => $token,
            'signature' => $signature,
        ];

        $this->assertTrue($this->service->verifyMailgunSignature($payload));
    }

    public function test_mailgun_signature_invalid_with_key_configured(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'a-real-signing-key']);

        $payload = [
            'timestamp' => (string) time(),
            'token'     => 'abc123token',
            'signature' => 'not-the-right-signature',
        ];

        $this->assertFalse($this->service->verifyMailgunSignature($payload));
    }

    public function test_parse_email_without_name(): void
    {
        $payload = [
            'sender'    => 'plain@empresa.com',
            'from'      => 'plain@empresa.com',
            'recipient' => 'tickets@soporte.tikara.mx',
            'subject'   => 'Falla de red',
            'body-plain'=> 'Internet caído.',
        ];

        $parsed = $this->service->parse($payload);

        $this->assertEquals('plain@empresa.com', $parsed['from']);
        $this->assertEquals('', $parsed['from_name']);
    }
}
