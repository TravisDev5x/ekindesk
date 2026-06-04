<?php

namespace App\Models;

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
    ];

    protected $appends = [
        'business_name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getBusinessNameAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
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

    public function scopeForOperator(Builder $query, int $userId): Builder
    {
        return $query->where('operator_user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
