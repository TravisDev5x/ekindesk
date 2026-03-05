<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Activo de inventario (TI o mobiliario).
 *
 * @property int $id
 * @property string $tipo
 * @property string|null $subtipo
 * @property string $nombre
 * @property string|null $numero_serie
 * @property string $estado
 * @property string|null $ubicacion
 */
class Asset extends Model
{
    protected $table = 'sigan_assets';

    protected $fillable = [
        'tipo',
        'subtipo',
        'nombre',
        'numero_serie',
        'estado',
        'ubicacion',
        'observaciones',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(AssetComponent::class, 'asset_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'asset_id');
    }
}
