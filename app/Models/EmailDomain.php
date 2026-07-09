<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDomain extends Model
{
    protected $fillable = [
        'client_id',
        'domain',
        'inbound_address',
        'is_verified',
        'verified_at',
        'verification_token',
        'provider_route_id',
        'is_active',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active'   => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Resuelve el EmailDomain desde la dirección de email destino (campo `to`).
     * Busca por dominio exacto, verificado y activo.
     *
     * soporte@acme.com → busca domain='acme.com'
     * tickets@techsolve.tikara.mx → busca domain='techsolve.tikara.mx'
     */
    public static function resolveFromAddress(string $email): ?self
    {
        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return null;
        }

        $domain = strtolower(trim(substr($email, $atPos + 1)));

        return static::query()
            ->verified()
            ->active()
            ->where('domain', $domain)
            ->first();
    }

    /**
     * Genera la dirección de reply-to con folio embebido.
     * Ejemplo: ticket-00042@techsolve.tikara.mx
     */
    public function threadAddress(string $folio): string
    {
        return "ticket-{$folio}@{$this->domain}";
    }
}
