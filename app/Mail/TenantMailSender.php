<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Mail\Mailables\Address;

/**
 * Remitente de correo saliente.
 *
 * Decisión: soporte@tikara.mx genérico para TODOS los tenants, no
 * soporte@{portal_slug}.tikara.mx por tenant. Un remitente por subdominio
 * requeriría verificar cada subdominio como dominio propio en Mailgun (SPF/DKIM
 * por separado) — inviable para subdominios que se crean dinámicamente al
 * registrar un tenant nuevo, y sin eso el alineamiento SPF/DKIM del subdominio
 * no calificaría, dañando la entregabilidad. En su lugar, el nombre del tenant
 * se personaliza en el DISPLAY NAME ("Soporte {Cliente}" <soporte@tikara.mx>),
 * que el destinatario sí ve, sin ese costo de verificación por subdominio.
 */
class TenantMailSender
{
    public static function resolve(?Client $client = null): Address
    {
        $address = config('mail.from.address');
        $name = $client?->name
            ? "Soporte {$client->name}"
            : config('mail.from.name', 'Tikara');

        return new Address($address, $name);
    }
}
