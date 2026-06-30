<?php

namespace App\Services\Classification;

use Illuminate\Support\Facades\Log;

class TicketClassifierService
{
    public function __construct(
        private ClaudeClassifierService $claude
    ) {}

    /**
     * Clasifica un ticket en 3 capas:
     * 1. Reglas por keywords (Sprint 3 — placeholder)
     * 2. Claude API como fallback
     * 3. Default si todo falla
     *
     * Retorna: ['category', 'priority', 'summary', 'tags', 'confidence', 'source', 'rule_name']
     */
    public function classify(string $subject, string $body, int $clientId): array
    {
        // Capa 1 — Keywords (Sprint 3)
        // $ruleResult = TicketClassificationRule::evaluate($subject, $body, $clientId);
        // if ($ruleResult) return array_merge($ruleResult, ['source' => 'rule']);

        // Capa 2 — Claude API
        $aiEnabled = config('tikara.ai_classification_enabled', true);
        if ($aiEnabled && config('services.anthropic.key')) {
            try {
                $result = $this->claude->classify($subject, $body);
                return array_merge($result, ['source' => 'ai', 'rule_name' => null]);
            } catch (\Throwable $e) {
                Log::warning('Tikara: clasificación IA falló, usando default', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Capa 3 — Default
        return [
            'category'   => 'general',
            'priority'   => 'medium',
            'summary'    => mb_substr($subject, 0, 80),
            'tags'       => [],
            'confidence' => 0.0,
            'source'     => 'default',
            'rule_name'  => null,
        ];
    }
}
