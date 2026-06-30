<?php

namespace App\Models;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'operator_user_id',
        'name',
        'code',
        'legal_name',
        'tax_id',
        'industry',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'logo_path',
        'portal_slug',
        'portal_primary_color',
        'portal_welcome_message',
        'notes',
        'is_active',
        'plan_id',
        'subscription_expires_at',
        'billing_email',
        'cancelled_at',
    ];

    protected $appends = [
        'business_name',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'subscription_expires_at' => 'datetime',
        'cancelled_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $client) {
            if ($client->wasChanged('is_active') || $client->wasChanged('portal_slug')) {
                if ($client->portal_slug) {
                    \App\Services\TenantContextService::clearPortalCache($client->portal_slug);
                }
                $oldSlug = $client->getOriginal('portal_slug');
                if ($oldSlug && $oldSlug !== $client->portal_slug) {
                    \App\Services\TenantContextService::clearPortalCache($oldSlug);
                }
            }
        });
    }

    public function getBusinessNameAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class, 'client_id');
    }

    /** Alias for sedes (API naming). */
    public function sites(): HasMany
    {
        return $this->sedes();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'client_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'client_id');
    }

    public function scopeForOperator(Builder $query, int $userId): Builder
    {
        return $query->where('operator_user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
