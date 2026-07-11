<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Str;

/**
 * Deriva y asigna clients.ticket_prefix (parte del folio nuevo
 * TK-{Letra}{Num5}-{PREFIX}-{Rand5}).
 *
 * Derivación: iniciales de las palabras significativas del nombre (se
 * ignoran conectores: y, de, del, la, el, los, las, e, o), sin acentos,
 * mayúsculas. Si el nombre tiene una sola palabra significativa, se usan
 * sus primeras 3 letras para no quedar con un prefijo de 1 carácter.
 * Colisión con un prefijo existente → sufijo numérico (SYD, SYD2, SYD3...).
 *
 * INMUTABLE una vez asignado: no se recalcula si el cliente cambia de
 * nombre (los folios ya emitidos lo llevan embebido) — el hook de
 * Client::booted() revierte cualquier intento de modificarlo.
 */
class TicketPrefixService
{
    private const CONNECTORS = ['y', 'de', 'del', 'la', 'el', 'los', 'las', 'e', 'o'];

    private const MAX_LENGTH = 10;

    public function derive(string $name): string
    {
        $clean = Str::ascii(mb_strtolower(trim($name)));
        $words = preg_split('/[^a-z0-9]+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $significant = array_values(array_filter(
            $words,
            fn (string $w) => ! in_array($w, self::CONNECTORS, true)
        ));

        if (count($significant) >= 2) {
            $base = implode('', array_map(fn (string $w) => $w[0], $significant));
        } elseif (count($significant) === 1) {
            $base = substr($significant[0], 0, 3);
        } else {
            $base = 'TKT';
        }

        return strtoupper(substr($base, 0, self::MAX_LENGTH - 2));
    }

    /**
     * Deriva el prefijo y resuelve colisiones contra los ya asignados.
     * $ignoreClientId excluye al propio cliente (para re-runs idempotentes).
     */
    public function uniquePrefixFor(string $name, ?int $ignoreClientId = null): string
    {
        $base = $this->derive($name);

        $candidate = $base;
        $suffix = 2;
        while ($this->taken($candidate, $ignoreClientId)) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    public function assignTo(Client $client): string
    {
        if ($client->ticket_prefix) {
            return $client->ticket_prefix;
        }

        $prefix = $this->uniquePrefixFor($client->name, $client->id);
        $client->forceFill(['ticket_prefix' => $prefix])->saveQuietly();

        return $prefix;
    }

    private function taken(string $prefix, ?int $ignoreClientId): bool
    {
        return Client::where('ticket_prefix', $prefix)
            ->when($ignoreClientId, fn ($q) => $q->where('id', '!=', $ignoreClientId))
            ->exists();
    }
}
