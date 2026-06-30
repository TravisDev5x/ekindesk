<?php

namespace App\Services\Email;

class InboundEmailService
{
    // TODO: Sprint 1
    // Procesa emails entrantes de Mailgun webhook.
    //
    // parse(array $payload): array
    //   Normaliza payload de Mailgun/SES/Postal a estructura uniforme.
    //
    // resolveThread(string $messageId, string $inReplyTo): ?string
    //   Devuelve ticket_id si el email es una respuesta a ticket existente.
    //   Busca por Message-ID, In-Reply-To y folio en el Subject.
    //
    // extractFolio(string $subject, string $replyTo): ?string
    //   Extrae folio #XXXX del asunto o del alias de respuesta (reply+FOLIO@...).
}
