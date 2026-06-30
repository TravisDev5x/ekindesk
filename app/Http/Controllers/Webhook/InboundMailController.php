<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InboundMailController extends Controller
{
    public function handle(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        // TODO: Sprint 1
        // 1. Verificar firma HMAC del proveedor ($provider: 'mailgun', 'ses', 'postal')
        // 2. Parsear payload con InboundEmailService::parse()
        // 3. Resolver tenant desde dirección destino (alias@tenant.tikara.mx)
        // 4. Detectar si es ticket nuevo o respuesta:
        //    - Ticket nuevo: dispatch ProcessInboundTicket
        //    - Respuesta: dispatch ProcessInboundReply (threading por In-Reply-To + folio)
        // 5. Retornar 200 inmediatamente (no bloquear webhook)
        return response()->json(['status' => 'queued'], 200);
    }
}
