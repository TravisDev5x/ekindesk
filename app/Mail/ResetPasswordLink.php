<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ResetPasswordLink extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $url;

    protected ?Client $client;

    /**
     * $client es el tenant del usuario (User::client), si tiene uno. Antes
     * este Mailable existía pero nunca se usaba (el reset real pasaba por la
     * notificación default de Laravel, apuntando siempre al dominio
     * principal) — ver User::sendPasswordResetNotification().
     */
    public function __construct(string $token, string $email, ?Client $client = null)
    {
        $this->client = $client;
        $this->url = $this->buildUrl($token, $email);
    }

    public function build()
    {
        return $this->from(TenantMailSender::resolve($this->client))
            ->subject(__('passwords.subject'))
            ->view('emails.reset-password')
            ->with(['url' => $this->url]);
    }

    private function buildUrl(string $token, string $email): string
    {
        if ($this->client?->portal_slug && config('tenancy.base_domain')) {
            $scheme = config('tenancy.portal_scheme', 'https');
            $host = "{$this->client->portal_slug}.".config('tenancy.base_domain');

            return "{$scheme}://{$host}/reset-password?token={$token}&email=".urlencode($email);
        }

        return URL::to('/')."/reset-password?token={$token}&email=".urlencode($email);
    }
}
