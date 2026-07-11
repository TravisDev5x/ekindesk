<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'client_id',
        'name',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'customer_id');
    }
}
