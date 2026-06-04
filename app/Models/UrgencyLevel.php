<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrgencyLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'weight', 'is_active', 'operator_user_id', 'client_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];
}
