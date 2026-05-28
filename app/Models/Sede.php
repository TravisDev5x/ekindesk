<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use HasFactory;

    /** Table name in English (was: sedes) */
    protected $table = 'sites';

    protected $fillable = [
        'client_id',
        'name',
        'code',
        'type',      // physical | virtual
        'address',
        'city',
        'contact_name',
        'contact_phone',
        'contact_email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
