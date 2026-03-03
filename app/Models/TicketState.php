<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketState extends Model
{
    use HasFactory;

    /** Códigos de estado usados en consultas y lógica de negocio. */
    public const CODE_OPEN = 'abierto';
    public const CODE_IN_PROGRESS = 'en_progreso';
    public const CODE_CANCELED = 'cancelado';
    public const CODE_CLOSED = 'cerrado';
    public const CODE_RESOLVED = 'resuelto';

    protected $fillable = ['name', 'code', 'is_active', 'is_final'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
    ];

    /**
     * Retorna el ID del estado cuyo código coincide con el dado.
     */
    public static function findIdByCode(string $code): ?int
    {
        $id = static::where('code', $code)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Retorna el ID del estado "Cancelado" (por código o por nombre como fallback), o null si no existe.
     */
    public static function getCancelStateIdOrNull(): ?int
    {
        $id = static::findIdByCode(self::CODE_CANCELED)
            ?? static::where('name', 'Cancelado')->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Retorna el ID del estado "Cancelado" (por código o por nombre como fallback).
     *
     * @throws \RuntimeException si no existe el estado cancelado en el sistema
     */
    public static function getCancelStateId(): int
    {
        $id = static::getCancelStateIdOrNull();
        if ($id === null) {
            throw new \RuntimeException('No existe el estado Cancelado en el sistema');
        }
        return $id;
    }
}
