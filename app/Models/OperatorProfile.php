<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'rfc',
        'phone',
        'website',
        'address',
        'city',
        'country',
        'logo_path',
        'plan_id',
        'trial_ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isInTrial(): bool
    {
        return $this->trial_ends_at !== null && now()->lt($this->trial_ends_at);
    }

    public function hasActivePlan(): bool
    {
        return $this->plan_id !== null && $this->is_active;
    }
}
