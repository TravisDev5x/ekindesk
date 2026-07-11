<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;

/**
 * Customer implícito (Fase 2 del sprint maestro, bajo la jerarquía existente
 * Operator → clients): SIEMPRE existe una fila de clients con
 * is_internal=true por operador, que representa a la propia empresa del
 * tenant. En modalidad IT Internal es su único customer; en MSP/Hybrid
 * convive con los customers externos. Un solo modelo de datos para las 3
 * modalidades, sin ramas de código.
 */
class InternalCustomerService
{
    /**
     * Garantiza el customer interno del operador. Idempotente: si ya existe
     * uno (is_internal=true), lo devuelve sin tocar nada.
     */
    public function ensureFor(User $operator, ?string $businessName = null): Client
    {
        $existing = Client::where('operator_user_id', $operator->id)
            ->where('is_internal', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        $name = $businessName
            ?: $operator->operatorProfile?->business_name
            ?: trim($operator->first_name.' '.$operator->paternal_last_name);

        return Client::create([
            'operator_user_id' => $operator->id,
            'name' => $name,
            'is_internal' => true,
            'is_active' => true,
        ]);
    }
}
