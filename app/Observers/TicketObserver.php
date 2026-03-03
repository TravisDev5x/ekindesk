<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketAuditLog;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    private function captureContext(): array
    {
        $request = request();
        return [
            'user_id' => auth()->id(),
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ];
    }

    /**
     * Registra un log de auditoría sin interrumpir la transacción principal.
     */
    private function writeLog(int $ticketId, string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            $ctx = $this->captureContext();
            TicketAuditLog::create([
                'ticket_id' => $ticketId,
                'user_id' => $ctx['user_id'],
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $ctx['ip_address'],
                'user_agent' => $ctx['user_agent'],
            ]);
        } catch (\Throwable $e) {
            Log::error('TicketAuditLog failed', [
                'ticket_id' => $ticketId,
                'action' => $action,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function created(Ticket $ticket): void
    {
        $this->writeLog(
            (int) $ticket->id,
            'created',
            null,
            $this->sanitizeForLog($ticket->getAttributes())
        );
    }

    public function updated(Ticket $ticket): void
    {
        $changes = $ticket->getChanges();
        unset($changes['updated_at']);
        if ($changes === []) {
            return;
        }
        $original = $ticket->getOriginal();
        $oldValues = [];
        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $original[$key] ?? null;
        }
        $this->writeLog(
            (int) $ticket->id,
            'updated',
            $this->sanitizeForLog($oldValues),
            $this->sanitizeForLog($changes)
        );
    }

    public function deleted(Ticket $ticket): void
    {
        $this->writeLog(
            (int) $ticket->id,
            'deleted',
            $this->sanitizeForLog($ticket->getOriginal()),
            null
        );
    }

    public function restored(Ticket $ticket): void
    {
        $this->writeLog(
            (int) $ticket->id,
            'restored',
            null,
            $this->sanitizeForLog($ticket->getAttributes())
        );
    }

    /**
     * Convierte atributos a formato serializable para JSON (evita objetos Carbon, etc.).
     */
    private function sanitizeForLog(array $attrs): array
    {
        $out = [];
        foreach ($attrs as $key => $value) {
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
}
