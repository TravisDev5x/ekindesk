<?php

namespace App\Models\Sigan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Componente de un activo (ej. RAM, SSD); puede estar instalado o extraído (canibalización).
 *
 * @property int $id
 * @property int $asset_id
 * @property string $tipo_componente
 * @property string $estado
 * @property int|null $usado_en_asset_id
 */
class AssetComponent extends Model
{
    protected $table = 'sigan_asset_components';

    protected $fillable = [
        'asset_id',
        'tipo_componente',
        'descripcion',
        'estado',
        'usado_en_asset_id',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function usadoEnAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'usado_en_asset_id');
    }
}
