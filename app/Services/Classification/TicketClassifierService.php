<?php

namespace App\Services\Classification;

class TicketClassifierService
{
    // TODO: Sprint 1
    // Motor de clasificación en 3 capas:
    //   Capa 1 — dirección destino (email alias → área/tipo)
    //   Capa 2 — reglas por keywords (TicketClassificationRule)
    //   Capa 3 — Claude API (ClaudeClassifierService) como fallback
    //
    // classify(string $subject, string $body, string $clientId): array
    // Retorna: ['area_id', 'type_id', 'priority_id', 'summary', 'confidence']
}
