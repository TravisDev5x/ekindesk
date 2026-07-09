<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Componente de un activo (ej. RAM, SSD); puede estar instalado o extraído (canibalización).
 *
 * @property int $id
 * @property int $asset_id
 * @property string $component_type
 * @property string $status
 * @property int|null $reused_in_asset_id
 */
class AssetComponent extends Model
{
    protected $table = 'asset_components';

    protected $fillable = [
        'asset_id',
        'component_type',
        'description',
        'status',
        'reused_in_asset_id',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function reusedInAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'reused_in_asset_id');
    }
}
