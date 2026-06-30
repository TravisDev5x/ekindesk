<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Plan;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crea 3 tenants de prueba para desarrollo local.
 * NO usar en producción.
 *
 * Requiere tener corriendo: php artisan serve + dominio tikara.test en /etc/hosts
 *   127.0.0.1 tikara.test techsolve.tikara.test constructmx.tikara.test techgroup.tikara.test
 *
 * Uso:
 *   php artisan db:seed --class=TenantTestSeeder
 */
class TenantTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando operador de plataforma...');
        $operatorUser = $this->ensureOperatorUser();

        $this->command->info('Obteniendo planes disponibles...');
        $plans = Plan::pluck('id', 'slug');

        $tenants = [
            [
                'name'          => 'TechSolve MSP',
                'portal_slug'   => 'techsolve',
                'industry'      => 'Tecnología',
                'contact_email' => 'admin@techsolve.mx',
                'is_active'     => true,
                'plan_slug'     => 'growth',
            ],
            [
                'name'          => 'ConstructMX',
                'portal_slug'   => 'constructmx',
                'industry'      => 'Manufactura',
                'contact_email' => 'it@constructmx.com',
                'is_active'     => true,
                'plan_slug'     => 'pro',
            ],
            [
                'name'          => 'TechGroup Híbrido',
                'portal_slug'   => 'techgroup',
                'industry'      => 'Tecnología',
                'contact_email' => 'cio@techgroup.com.mx',
                'is_active'     => true,
                'plan_slug'     => 'enterprise',
            ],
        ];

        foreach ($tenants as $data) {
            $planId = isset($data['plan_slug']) ? ($plans[$data['plan_slug']] ?? null) : null;
            unset($data['plan_slug']);

            $cliente = Cliente::firstOrCreate(
                ['portal_slug' => $data['portal_slug']],
                array_merge($data, [
                    'operator_user_id' => $operatorUser->id,
                    'plan_id'          => $planId,
                ])
            );

            // Crear sede por defecto si no existe
            if (! DB::table('sites')->where('client_id', $cliente->id)->exists()) {
                DB::table('sites')->insert([
                    'name'       => 'Sede Principal',
                    'code'       => strtoupper(substr($cliente->portal_slug, 0, 3)) . '01',
                    'type'       => 'physical',
                    'is_active'  => true,
                    'client_id'  => $cliente->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Inicializar secuencia de folios si la tabla existe
            if (\Illuminate\Support\Facades\Schema::hasTable('ticket_sequences')) {
                DB::table('ticket_sequences')->updateOrInsert(
                    ['client_id' => $cliente->id],
                    ['last_number' => 0, 'updated_at' => now(), 'created_at' => now()]
                );
            }

            $this->command->line("  ✓ {$cliente->name} → {$cliente->portal_slug}.tikara.test");
        }

        $this->command->newLine();
        $this->command->info('3 tenants de prueba listos.');
        $this->command->line('Agrega a /etc/hosts:');
        $this->command->line('  127.0.0.1 techsolve.tikara.test constructmx.tikara.test techgroup.tikara.test');
    }

    private function ensureOperatorUser(): User
    {
        $areaId = DB::table('areas')->insertGetId([
            'name' => 'Plataforma', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $positionId = DB::table('positions')->insertGetId([
            'name' => 'Administrador', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $siteId = DB::table('sites')->insertGetId([
            'name' => 'Oficina Central', 'code' => 'HQ01', 'type' => 'physical',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::firstOrCreate(
            ['email' => 'operator@tikara.mx'],
            [
                'first_name'          => 'Operador',
                'paternal_last_name'  => 'Tikara',
                'email'               => 'operator@tikara.mx',
                'password'            => 'TikaraAdmin2026!',
                'employee_number'     => 'OP001',
                'area_id'             => $areaId,
                'position_id'         => $positionId,
                'sede_id'             => $siteId,
                'status'              => 'active',
                'is_operator'         => true,
                'email_verified_at'   => now(),
                'onboarding_completed'=> true,
            ]
        );
    }
}
