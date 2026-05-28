<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Para equipos pequeños que inician con helpdesk MSP.',
                'price_monthly' => 299,
                'price_yearly' => 2990,
                'max_clients' => 3,
                'max_users' => 10,
                'max_agents' => 2,
                'trial_days' => 14,
                'highlighted' => false,
                'features' => [
                    'Gestión de tickets',
                    'Portal cliente',
                    'SLA básico',
                    'Soporte por email',
                ],
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Operación MSP en crecimiento con más sedes y reportes.',
                'price_monthly' => 799,
                'price_yearly' => 7990,
                'max_clients' => 15,
                'max_users' => 50,
                'max_agents' => 8,
                'trial_days' => 14,
                'highlighted' => true,
                'features' => [
                    'Todo en Starter',
                    'Multi-sede',
                    'Despacho técnicos',
                    'SLA configurable',
                    'Reportes avanzados',
                    'KB interna',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Contrato a medida con límites y precios personalizados.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_clients' => null,
                'max_users' => null,
                'max_agents' => null,
                'trial_days' => 0,
                'highlighted' => false,
                'features' => [
                    'Todo en Growth',
                    'White-label',
                    'LDAP/AD',
                    'API completa',
                    'SLA personalizado',
                    'Soporte dedicado',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['is_active' => true, 'is_public' => true])
            );
        }
    }
}
