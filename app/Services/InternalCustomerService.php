<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Models\User;

/**
 * Customer implícito (Fase 2 del sprint maestro, jerarquía Client (tenant) ->
 * Customer (empresa soportada) -> Site): SIEMPRE existe un Customer con
 * is_internal=true que representa a la propia empresa del tenant. En
 * modalidad IT Internal es su único customer; en MSP/Hybrid convive con los
 * customers externos. Un solo modelo de datos para las 3 modalidades, sin
 * ramas de código.
 *
 * Reemplaza el diseño "Opción B" (clients.is_internal, ver
 * 2026_07_11_000009_revert_clients_is_internal): ahora el flag vive en
 * customers.is_internal, no en clients. El Client (tenant) que ancla a ese
 * Customer se sigue creando/reutilizando igual que antes -- operator_user_id
 * identifica al operador dueño; un operador puede tener más de un Client
 * (customers externos vía OperatorOnboardingController::storeClient), pero
 * como mucho un Customer interno.
 */
class InternalCustomerService
{
    /**
     * Garantiza el customer interno del operador. Idempotente: si ya existe
     * uno (is_internal=true), lo devuelve sin tocar nada.
     */
    public function ensureFor(User $operator, ?string $businessName = null): Customer
    {
        $existing = Customer::where('is_internal', true)
            ->whereHas('client', fn ($q) => $q->where('operator_user_id', $operator->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        $name = $businessName
            ?: $operator->operatorProfile?->business_name
            ?: trim($operator->first_name.' '.$operator->paternal_last_name);

        $client = Client::create([
            'operator_user_id' => $operator->id,
            'name' => $name,
            'is_active' => true,
        ]);

        return Customer::create([
            'client_id' => $client->id,
            'name' => $name,
            'is_internal' => true,
        ]);
    }
}
