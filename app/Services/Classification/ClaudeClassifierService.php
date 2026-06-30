<?php

namespace App\Services\Classification;

use Illuminate\Support\Facades\Http;

class ClaudeClassifierService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key', '');
        $this->model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');
    }

    /**
     * Clasifica un ticket vía Claude API.
     *
     * Retorna: ['category', 'priority', 'summary', 'tags', 'confidence']
     * Lanza RuntimeException si la API falla o retorna JSON inválido.
     */
    public function classify(string $subject, string $body): array
    {
        $prompt = "Asunto: {$subject}\n\nCuerpo: " . mb_substr($body, 0, 500);

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 200,
            'system'     => $this->systemPrompt(),
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Claude API error: ' . $response->status());
        }

        $raw  = $response->json('content.0.text', '{}');
        $raw  = preg_replace('/```json|```/', '', $raw);
        $data = json_decode(trim($raw), true);

        if (! $data || ! isset($data['category'])) {
            throw new \RuntimeException('Claude retornó JSON inválido: ' . $raw);
        }

        return [
            'category'   => $data['category']  ?? 'general',
            'priority'   => $data['priority']   ?? 'medium',
            'summary'    => $data['summary']    ?? mb_substr($subject, 0, 80),
            'tags'       => $data['tags']       ?? [],
            'confidence' => (float) ($data['confidence'] ?? 0.5),
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres un clasificador de tickets de soporte técnico para empresas mexicanas.
Analiza el asunto y cuerpo del ticket. Responde ÚNICAMENTE con JSON válido,
sin markdown, sin texto adicional, sin backticks.

Categorías: hardware, software, red, accesos, impresoras, servidores, seguridad, general

Prioridades:
- critical: sistema caído, producción afectada, múltiples usuarios sin acceso
- high: usuario sin poder trabajar, falla activa de hardware
- medium: problema con workaround disponible, lentitud
- low: consulta, solicitud de mejora, información

Responde con este JSON exacto:
{"category":"string","priority":"string","summary":"resumen máximo 80 caracteres","tags":["array"],"confidence":0.0}
PROMPT;
    }
}
