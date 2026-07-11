<?php

namespace Tests\Feature;

use Tests\TestCase;

class MailgunOutboundConfigTest extends TestCase
{
    public function test_mailgun_mailer_is_registered(): void
    {
        $this->assertSame('mailgun', config('mail.mailers.mailgun.transport'));
    }

    public function test_mailgun_outbound_credentials_are_wired_to_env(): void
    {
        config([
            'services.mailgun.domain' => 'mg.tikara.mx',
            'services.mailgun.secret' => 'key-test',
            'services.mailgun.endpoint' => 'api.mailgun.net',
        ]);

        $this->assertSame('mg.tikara.mx', config('services.mailgun.domain'));
        $this->assertSame('key-test', config('services.mailgun.secret'));
        $this->assertSame('api.mailgun.net', config('services.mailgun.endpoint'));
    }

    public function test_mailgun_webhook_signing_key_is_a_separate_config_key(): void
    {
        config([
            'services.mailgun.webhook_signing_key' => 'signing-key-test',
            'services.mailgun.secret' => 'sending-key-test',
        ]);

        $this->assertNotSame(
            config('services.mailgun.webhook_signing_key'),
            config('services.mailgun.secret')
        );
    }
}
