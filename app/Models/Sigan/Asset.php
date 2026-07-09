<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Activo de inventario (TI o mobiliario).
 *
 * @property int $id
 * @property string $type
 * @property string|null $subtype
 * @property string $name
 * @property string|null $serial_number
 * @property string $status
 * @property string|null $location
 */
class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'type',
        'subtype',
        'name',
        'serial_number',
        'status',
        'location',
        'notes',
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
