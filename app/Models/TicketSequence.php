<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TicketSequence extends Model
{
    protected $primaryKey = 'client_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['client_id', 'last_number'];

    /**
     * Genera el siguiente folio para un tenant de forma atómica.
     *
     * En PostgreSQL: INSERT ON CONFLICT DO NOTHING + UPDATE RETURNING — un round-trip,
     * sin locks adicionales, seguro bajo carga concurrente.
     * En SQLite (tests): firstOrCreate + increment (suficiente, sin concurrencia real).
     *
     * Retorna el número con zero-padding a 5 dígitos: "00042".
     */
    public static function nextFor(int $clientId): string
    {
        $number = DB::transaction(function () use ($clientId) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement(
                    'INSERT INTO ticket_sequences (client_id, last_number, created_at, updated_at)
                     VALUES (?, 0, NOW(), NOW())
                     ON CONFLICT (client_id) DO NOTHING',
                    [$clientId]
                );

                $result = DB::selectOne(
                    'UPDATE ticket_sequences
                     SET last_number = last_number + 1,
                         updated_at  = NOW()
                     WHERE client_id = ?
                     RETURNING last_number',
                    [$clientId]
                );

                return $result->last_number;
            }

            // SQLite (entorno de tests)
            $seq = static::firstOrCreate(
                ['client_id' => $clientId],
                ['last_number' => 0]
            );
            $seq->increment('last_number');
            return $seq->fresh()->last_number;
        });

        return str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }
}
