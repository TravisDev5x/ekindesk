<?php

namespace App\Services\Email;

use App\Models\Cliente;
use App\Models\EmailDomain;

class InboundEmailService
{
    /**
     * Normaliza el payload de Mailgun a estructura uniforme.
     */
    public function parse(array $payload): array
    {
        return [
            'from'             => $this->extractEmail($payload['sender'] ?? $payload['from'] ?? ''),
            'from_name'        => $this->extractName($payload['from'] ?? ''),
            'to'               => $this->extractEmail($payload['recipient'] ?? $payload['To'] ?? ''),
            'subject'          => $payload['subject'] ?? $payload['Subject'] ?? '',
            'body_plain'       => $payload['body-plain'] ?? $payload['stripped-text'] ?? '',
            'body_html'        => $payload['body-html'] ?? $payload['stripped-html'] ?? '',
            'message_id'       => $payload['Message-Id'] ?? $payload['message-id'] ?? '',
            'in_reply_to'      => $payload['In-Reply-To'] ?? $payload['in-reply-to'] ?? '',
            'references'       => $payload['References'] ?? $payload['references'] ?? '',
            'attachments'      => $this->parseAttachments($payload),
        ];
    }

    /**
     * Resuelve el tenant (Cliente) desde la dirección destino del email.
     *
     * Prioridad:
     * 1. EmailDomain registrado y verificado (dominio propio del cliente)
     * 2. Subdominio de tikara: techsolve.tikara.mx → portal_slug='techsolve'
     */
    public function resolveTenant(string $toEmail): ?Cliente
    {
        $emailDomain = EmailDomain::resolveFromAddress($toEmail);
        if ($emailDomain) {
            return $emailDomain->cliente;
        }

        $atPos = strpos($toEmail, '@');
        if ($atPos === false) {
            return null;
        }
        $domain    = strtolower(trim(substr($toEmail, $atPos + 1)));
        $appDomain = config('tenancy.base_domain', 'tikara.mx');

        if (str_ends_with($domain, '.' . $appDomain)) {
            $slug = substr($domain, 0, -(strlen($appDomain) + 1));
            return Cliente::where('portal_slug', $slug)
                ->where('is_active', true)
                ->whereNull('cancelled_at')
                ->first();
        }

        return null;
    }

    /**
     * Detecta si el email es respuesta a un ticket existente.
     *
     * Busca en orden:
     * 1. In-Reply-To: <ticket-00042@dominio>
     * 2. Subject: [#00042] o Re: [#00042] o #00042
     */
    public function detectFolio(array $parsedEmail): ?string
    {
        $inReplyTo = $parsedEmail['in_reply_to'] ?? '';
        if ($inReplyTo && preg_match('/ticket-(\d{5})@/i', $inReplyTo, $m)) {
            return $m[1];
        }

        $subject = $parsedEmail['subject'] ?? '';
        if (preg_match('/\[#(\d{5})\]/i', $subject, $m)) {
            return $m[1];
        }
        if (preg_match('/#(\d{5})\b/i', $subject, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Verifica la firma HMAC-SHA256 del webhook de Mailgun.
     * En local sin key configurada, permite todo (facilita desarrollo).
     */
    public function verifyMailgunSignature(array $payload): bool
    {
        $signingKey = config('services.mailgun.webhook_signing_key');

        if (! $signingKey) {
            return app()->environment('local', 'testing');
        }

        $timestamp = (string) ($payload['timestamp'] ?? '');
        $token     = (string) ($payload['token'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');

        $expected = hash_hmac('sha256', $timestamp . $token, $signingKey);

        return hash_equals($expected, $signature);
    }

    private function extractEmail(string $from): string
    {
        if (preg_match('/<(.+?)>/', $from, $m)) {
            return strtolower(trim($m[1]));
        }
        return strtolower(trim($from));
    }

    private function extractName(string $from): string
    {
        if (preg_match('/^(.+?)\s*</u', $from, $m)) {
            return trim($m[1], ' "\'');
        }
        return '';
    }

    private function parseAttachments(array $payload): array
    {
        $count = (int) ($payload['attachment-count'] ?? 0);
        if ($count === 0) {
            return [];
        }

        $attachments = [];
        for ($i = 1; $i <= $count; $i++) {
            if (isset($payload["attachment-{$i}"])) {
                $attachments[] = $payload["attachment-{$i}"];
            }
        }
        return $attachments;
    }
}
