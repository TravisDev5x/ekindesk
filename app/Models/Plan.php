<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_clients',
        'max_users',
        'max_agents',
        'features',
        'is_active',
        'is_public',
        'highlighted',
        'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'highlighted' => 'boolean',
            'trial_days' => 'integer',
        ];
    }

    public function operatorProfiles(): HasMany
    {
        return $this->hasMany(OperatorProfile::class);
    }

    public function scopeActivePublic(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('price_monthly');
    }
}
