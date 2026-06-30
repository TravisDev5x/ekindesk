<?php

namespace App\Services\Classification;

use App\Models\TicketClassificationRule;
use Illuminate\Support\Facades\Log;

class TicketClassifierService
{
    /**
     * Mapeo de categoría IA → ticket_type_id (IDs reales de la DB).
     *
     * Tipos en DB:  1=Falla de equipo | 2=Acceso a sistema | 3=Solicitud de cambio
     */
    private const CATEGORY_TO_TYPE_ID = [
        'hardware'   => 1, // Falla de equipo
        'software'   => 1, // Falla de equipo
        'red'        => 1, // Falla de equipo
        'impresoras' => 1, // Falla de equipo
        'servidores' => 1, // Falla de equipo
        'accesos'    => 2, // Acceso a sistema
        'seguridad'  => 2, // Acceso a sistema
        'general'    => 3, // Solicitud de cambio
    ];

    /**
     * Mapeo de prioridad IA → priority_id (IDs reales de la DB).
     *
     * Prioridades: 1=Crítica | 2=Alta | 3=Media | 4=Baja
     */
    private const PRIORITY_TO_ID = [
        'critical' => 1, // Crítica
        'high'     => 2, // Alta
        'medium'   => 3, // Media
        'low'      => 4, // Baja
    ];

    public function __construct(
        private ClaudeClassifierService $claude
    ) {}

    /**
     * Clasifica un ticket en 3 capas:
     *
     * Capa 1 — Reglas por keywords del tenant (TicketClassificationRule)
     * Capa 2 — Claude Haiku vía Anthropic API
     * Capa 3 — Default (category='general', priority='medium')
     *
     * Retorna array con:
     * ['category', 'priority', 'summary', 'tags', 'confidence',
     *  'source', 'rule_name', 'ticket_type_id', 'priority_id']
     */
    public function classify(string $subject, string $body, int $clientId): array
    {
        // Capa 1 — Reglas por keywords
        $ruleResult = TicketClassificationRule::evaluate($subject, $body, $clientId);
        if ($ruleResult) {
            return array_merge([
                'category'   => 'general',
                'priority'   => 'medium',
                'summary'    => mb_substr($subject, 0, 80),
                'tags'       => [],
                'confidence' => 1.0,
            ], $ruleResult);
        }

        // Capa 2 — Claude API
        $aiEnabled = config('tikara.ai_classification_enabled', true);
        if ($aiEnabled && config('services.anthropic.key')) {
            try {
                $aiResult = $this->claude->classify($subject, $body);
                return array_merge($aiResult, [
                    'source'         => 'ai',
                    'rule_name'      => null,
                    'ticket_type_id' => $this->resolveTypeId($aiResult['category'] ?? 'general'),
                    'priority_id'    => $this->resolvePriorityId($aiResult['priority'] ?? 'medium'),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Tikara: clasificación IA falló, usando default', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Capa 3 — Default
        return [
            'category'       => 'general',
            'priority'       => 'medium',
            'summary'        => mb_substr($subject, 0, 80),
            'tags'           => [],
            'confidence'     => 0.0,
            'source'         => 'default',
            'rule_name'      => null,
            'ticket_type_id' => self::CATEGORY_TO_TYPE_ID['general'],   // 3
            'priority_id'    => self::PRIORITY_TO_ID['medium'],          // 3
        ];
    }

    public function resolveTypeId(string $category): int
    {
        return self::CATEGORY_TO_TYPE_ID[$category] ?? self::CATEGORY_TO_TYPE_ID['general'];
    }

    public function resolvePriorityId(string $priority): int
    {
        return self::PRIORITY_TO_ID[$priority] ?? self::PRIORITY_TO_ID['medium'];
    }
}
