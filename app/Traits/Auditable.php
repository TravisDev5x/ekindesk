<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    /**
     * Atributos que no deben guardarse en el log (contraseñas, tokens, etc.).
     */
    protected static function auditableHiddenAttributes(): array
    {
        return [
            'password',
            'password_confirmation',
            'remember_token',
            'updated_at',
        ];
    }

    protected static function bootAuditable(): void
    {
        static::created(function (self $model) {
            static::writeAuditLog($model, 'created', null, static::sanitizeForAudit($model->getAttributes()));
        });

        static::updated(function (self $model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if ($changes === []) {
                return;
            }
            $original = $model->getOriginal();
            $oldValues = [];
            foreach (array_keys($changes) as $key) {
                $oldValues[$key] = $original[$key] ?? null;
            }
            static::writeAuditLog(
                $model,
                'updated',
                static::sanitizeForAudit($oldValues),
                static::sanitizeForAudit($changes)
            );
        });

        static::deleted(function (self $model) {
            static::writeAuditLog(
                $model,
                'deleted',
                static::sanitizeForAudit($model->getOriginal()),
                null
            );
        });

        // Corrección: Solo escuchar 'restored' si el modelo usa SoftDeletes
        if (method_exists(static::class, 'restored')) {
            static::restored(function (self $model) {
                static::writeAuditLog($model, 'restored', null, static::sanitizeForAudit($model->getAttributes()));
            });
        }
    }

    /**
     * Escribe un registro en audit_logs sin interrumpir la transacción principal.
     */
    protected static function writeAuditLog(
        self $model,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        try {
            $request = request();
            AuditLog::create([
                'user_id' => auth()->id(),
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request ? $request->userAgent() : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditLog failed', [
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'action' => $action,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Convierte atributos a formato serializable para JSON y excluye sensibles.
     */
    protected static function sanitizeForAudit(array $attrs): array
    {
        $hidden = array_flip(static::auditableHiddenAttributes());
        $out = [];
        foreach ($attrs as $key => $value) {
            if (isset($hidden[$key])) {
                continue;
            }
            if ($value instanceof \DateTimeInterface) {
                $out[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            } elseif (is_array($value)) {
                $out[$key] = $value;
            } else {
                $out[$key] = (string) $value;
            }
        }
        return $out;
    }

    /**
     * Relación: historial de auditoría de este modelo.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->orderBy('created_at', 'desc');
    }
}