<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de mantenimiento (interno o externo) de un activo.
 *
 * @property int $id
 * @property int $asset_id
 * @property string $tipo
 * @property string|null $proveedor
 * @property string $fecha
 */
class MaintenanceRecord extends Model
{
    protected $table = 'sigan_maintenance';

    protected $fillable = [
        'asset_id',
        'tipo',
        'proveedor',
        'fecha',
        'descripcion',
        'resultado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
