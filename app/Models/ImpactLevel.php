<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ImpactLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'weight', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];
}
