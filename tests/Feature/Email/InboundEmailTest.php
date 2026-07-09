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
            'in_reply_to' => '<ticket-00042@techsolve.tikara.mx>',
        ];

        $this->assertEquals('00042', $this->service->detectFolio($parsed));
    }

    public function test_detect_folio_from_subject_brackets(): void
    {
        $parsed = [
            'subject'     => 'Re: [#00015] Problema con impresora',
            'in_reply_to' => '',
        ];

        $this->assertEquals('00015', $this->service->detectFolio($parsed));
    }

    public function test_detect_folio_from_subject_hash(): void
    {
        $parsed = [
            'subject'     => 'Re: Seguimiento #00099',
            'in_reply_to' => '',
        ];

        $this->assertEquals('00099', $this->service->detectFolio($parsed));
    }

    public function test_no_folio_for_new_ticket(): void
    {
        $parsed = [
            'subject'     => 'Mi computadora no enciende',
            'in_reply_to' => '',
        ];

        $this->assertNull($this->service->detectFolio($parsed));
    }

    public function test_ticket_sequence_increments_atomically(): void
    {
        $tenant = Client::factory()->create([
            'portal_slug' => 'test-seq',
            'is_active'   => true,
        ]);

        $folio1 = TicketSequence::nextFor($tenant->id);
        $folio2 = TicketSequence::nextFor($tenant->id);
        $folio3 = TicketSequence::nextFor($tenant->id);

        $this->assertMatchesRegularExpression('/^\d{5}$/', $folio1);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $folio2);
        $this->assertGreaterThan((int) $folio1, (int) $folio2);
        $this->assertGreaterThan((int) $folio2, (int) $folio3);
        $this->assertNotEquals($folio1, $folio2);
    }

    public function test_ticket_sequences_are_isolated_per_tenant(): void
    {
        $tenantA = Client::factory()->create(['portal_slug' => 'tenant-a', 'is_active' => true]);
        $tenantB = Client::factory()->create(['portal_slug' => 'tenant-b', 'is_active' => true]);

        $folioA1 = TicketSequence::nextFor($tenantA->id);
        $folioA2 = TicketSequence::nextFor($tenantA->id);
        $folioB1 = TicketSequence::nextFor($tenantB->id);

        // Tenant A va por su propia secuencia
        $this->assertEquals('00001', $folioA1);
        $this->assertEquals('00002', $folioA2);

        // Tenant B empieza en 1, independiente de A
        $this->assertEquals('00001', $folioB1);
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
