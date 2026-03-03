<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriorityMatrix extends Model
{
    use HasFactory;

    protected $table = 'priority_matrix';

    protected $fillable = ['impact_level_id', 'urgency_level_id', 'priority_id'];

    public function impactLevel(): BelongsTo
    {
        return $this->belongsTo(ImpactLevel::class, 'impact_level_id');
    }

    public function urgencyLevel(): BelongsTo
    {
        return $this->belongsTo(UrgencyLevel::class, 'urgency_level_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }
}
