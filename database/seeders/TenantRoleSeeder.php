<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Los 4 roles del sprint maestro (Fase 3): admin, supervisor, agente,
 * solicitante. Coexisten a propósito con los roles legacy que sigue
 * creando FullDemoSeeder (admin, super_admin, gerente, supervisor, soporte,
 * soporte_n1/n2/n3, usuario, consultor) -- el corte real (reasignar
 * usuarios y retirar los legacy) es un paso aparte y explícito, ver
 * App\Console\Commands\MigrateLegacyRoles.
 *
 * Mapeo acordado tras la auditoría del sprint (2026-07-12):
 *   admin                              -> admin
 *   super_admin                        -> (fuera del RBAC de tenant, es de plataforma)
 *   gerente, supervisor                -> supervisor
 *   soporte, soporte_n1/n2/n3          -> agente
 *   usuario                            -> solicitante
 *   consultor                          -> agente (colapsa, sin variante de solo lectura)
 *   visitante (nunca seedeado)         -> diferido a Fase 4 ("solicitante sin site")
 *
 * Los permisos base son los mismos que ya usan los roles legacy
 * equivalentes (FullDemoSeeder::seedRolesAndPermissions) -- Fase 3 es un
 * renombrado/consolidación de roles, no un rediseño del modelo de permisos.
 * La única adición real es tickets.reassign (antes no existía; la acción
 * "reassigned" de TicketController usa tickets.assign, ver Fase 5).
 */
class TenantRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('TenantRoleSeeder: admin, supervisor, agente, solicitante.');

        DB::transaction(function () {
            $this->seedPermissions();
            $this->seedRoles();
        });

        $this->command?->info('TenantRoleSeeder finalizado.');
    }

    private function seedPermissions(): void
    {
        foreach (['web', 'sanctum'] as $guard) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'tickets.reassign',
                'guard_name' => $guard,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedRoles(): void
    {
        $allWebPermissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $agentePerms = [
            'tickets.view_area',
            'tickets.comment',
            'tickets.change_status',
            'tickets.assign',
            'tickets.filter_by_site',
            'tickets.escalate',
        ];

        $supervisorPerms = array_values(array_unique(array_merge(
            $agentePerms,
            [
                'tickets.manage_all',
                'tickets.reassign',
                'incidents.view_area',
                'incidents.manage_all',
            ]
        )));

        $roles = [
            // Admin: todos los permisos (igual que el admin legacy).
            'admin' => $allWebPermissions,
            // Supervisor: colapsa gerente+supervisor legacy. Único rol de
            // tenant con tickets.reassign en esta fase -- Fase 5 decide si
            // agente también lo necesita cuando implemente la acción real.
            'supervisor' => $supervisorPerms,
            // Agente: colapsa soporte/soporte_n1/n2/n3 + consultor legacy
            // (consultor no tiene variante de solo lectura separada).
            'agente' => $agentePerms,
            // Solicitante: igual que usuario legacy.
            'solicitante' => [
                'tickets.create',
                'tickets.view_own',
            ],
        ];

        foreach ($roles as $roleName => $permNames) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['slug' => $roleName]
            );
            $role->syncPermissions($permNames);
        }
    }
}
