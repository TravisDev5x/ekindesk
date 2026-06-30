<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketClassificationRule extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'keywords',
        'ticket_type_id',
        'priority_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'keywords'  => 'array',
        'is_active' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Evalúa las reglas del tenant contra subject+body.
     * Devuelve el resultado de la primera regla que coincide, o null.
     *
     * Retorna: ['ticket_type_id', 'priority_id', 'rule_name', 'source' => 'rule']
     */
    public static function evaluate(string $subject, string $body, int $clientId): ?array
    {
        $haystack = strtolower($subject . ' ' . $body);

        $rules = static::query()
            ->active()
            ->forClient($clientId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            foreach ((array) $rule->keywords as $keyword) {
                if (str_contains($haystack, strtolower((string) $keyword))) {
                    return [
                        'ticket_type_id' => $rule->ticket_type_id,
                        'priority_id'    => $rule->priority_id,
                        'rule_name'      => $rule->name,
                        'source'         => 'rule',
                    ];
                }
            }
        }

        return null;
    }
}
