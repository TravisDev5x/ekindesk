<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de mantenimiento (interno o externo) de un activo.
 *
 * @property int $id
 * @property int $asset_id
 * @property string $type
 * @property string|null $provider
 * @property string $performed_at
 */
class MaintenanceRecord extends Model
{
    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id',
        'type',
        'provider',
        'performed_at',
        'description',
        'result',
    ];

    protected $casts = [
        'performed_at' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
