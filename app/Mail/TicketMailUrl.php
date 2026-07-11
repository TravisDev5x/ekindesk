<?php

namespace App\Mail;

use App\Models\Ticket;

/**
 * Link al ticket para correos al solicitante. Si el ticket pertenece a un
 * tenant con portal propio, apunta al portal ({portal_slug}.{base_domain});
 * si no (solicitante interno, sin client_id), a /resolbeb/mis-tickets en el
 * dominio principal — mismo patrón que TenantMailSender/ResetPasswordLink.
 */
class TicketMailUrl
{
    public static function resolve(Ticket $ticket): string
    {
        $client = $ticket->client;

        if ($client?->portal_slug && config('tenancy.base_domain')) {
            $scheme = config('tenancy.portal_scheme', 'https');
            $host = "{$client->portal_slug}.".config('tenancy.base_domain');

            return "{$scheme}://{$host}/tickets";
        }

        return url('/resolbeb/mis-tickets');
    }
}
