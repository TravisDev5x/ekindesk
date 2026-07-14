<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\FullDemoSeeder;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Fase 3 del sprint maestro: 4 roles nuevos (admin, supervisor, agente,
 * solicitante) coexistiendo con los roles legacy que sigue creando
 * FullDemoSeeder. Ver App\Console\Commands\MigrateLegacyRoles para la
 * migración (aditiva) de usuarios existentes.
 */
class TenantRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_four_new_roles_with_expected_permissions(): void
    {
        $this->seed(TenantRoleSeeder::class);

        $solicitante = Role::where('name', 'solicitante')->where('guard_name', 'web')->first();
        $agente = Role::where('name', 'agente')->where('guard_name', 'web')->first();
        $supervisor = Role::where('name', 'supervisor')->where('guard_name', 'web')->first();
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        $this->assertNotNull($solicitante);
        $this->assertNotNull($agente);
        $this->assertNotNull($supervisor);
        $this->assertNotNull($admin);

        $this->assertEqualsCanonicalizing(
            ['tickets.create', 'tickets.view_own'],
            $solicitante->permissions->pluck('name')->all()
        );

        $this->assertTrue($agente->hasPermissionTo('tickets.view_area'));
        $this->assertTrue($agente->hasPermissionTo('tickets.assign'));
        $this->assertFalse($agente->hasPermissionTo('tickets.manage_all'));
        $this->assertFalse($agente->hasPermissionTo('tickets.reassign'));

        // NO tickets.manage_all: bajo el scoping por site de Fase 4, esa
        // permission es "ve todo sin restricción de site" -- exclusiva de
        // admin. Supervisor ve sus sites vía el rol, no vía este permiso.
        $this->assertFalse($supervisor->hasPermissionTo('tickets.manage_all'));
        $this->assertTrue($supervisor->hasPermissionTo('tickets.reassign'));

        $this->assertTrue($admin->hasPermissionTo('tickets.reassign'));
    }

    public function test_tickets_reassign_permission_exists_for_web_and_sanctum_guards(): void
    {
        $this->seed(TenantRoleSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'tickets.reassign', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'tickets.reassign', 'guard_name' => 'sanctum']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(TenantRoleSeeder::class);
        $this->seed(TenantRoleSeeder::class);

        $this->assertSame(1, Role::where('name', 'agente')->where('guard_name', 'web')->count());
        $this->assertSame(1, DB::table('permissions')->where('name', 'tickets.reassign')->where('guard_name', 'web')->count());
    }

    public function test_new_roles_coexist_with_legacy_roles_without_touching_them(): void
    {
        $this->seed(FullDemoSeeder::class);
        $legacyAgentePermsBefore = Role::where('name', 'soporte')->where('guard_name', 'web')->first()->permissions->pluck('name')->all();

        $this->seed(TenantRoleSeeder::class);

        // El legacy 'soporte' sigue existiendo, intacto -- Fase 3 no lo toca.
        $legacySoporte = Role::where('name', 'soporte')->where('guard_name', 'web')->first();
        $this->assertNotNull($legacySoporte);
        $this->assertEqualsCanonicalizing($legacyAgentePermsBefore, $legacySoporte->permissions->pluck('name')->all());

        // 'admin' y 'supervisor' son el mismo nombre en ambos esquemas -- no
        // se duplican, solo se normalizan sus permisos in-place.
        $this->assertSame(1, Role::where('name', 'admin')->where('guard_name', 'web')->count());
        $this->assertSame(1, Role::where('name', 'supervisor')->where('guard_name', 'web')->count());
    }

    // ── MigrateLegacyRoles ───────────────────────────────────────────────

    public function test_migrate_legacy_roles_is_additive_and_idempotent(): void
    {
        $this->seed(FullDemoSeeder::class);
        $this->seed(TenantRoleSeeder::class);

        $user = $this->makeUser();
        $user->assignRole('soporte');

        $this->artisan('roles:migrate-legacy --dry-run')->assertSuccessful();
        $this->assertFalse($user->fresh()->hasRole('agente'));

        $this->artisan('roles:migrate-legacy')->assertSuccessful();
        $user->refresh();
        $this->assertTrue($user->hasRole('agente'));
        // Aditivo: el legacy NO se quita.
        $this->assertTrue($user->hasRole('soporte'));

        // Idempotente: correr de nuevo no falla ni duplica la asignación.
        $this->artisan('roles:migrate-legacy')->assertSuccessful();
        $this->assertSame(1, $user->fresh()->roles->where('name', 'agente')->count());
    }

    public function test_migrate_legacy_roles_maps_each_legacy_role_correctly(): void
    {
        $this->seed(FullDemoSeeder::class);
        $this->seed(TenantRoleSeeder::class);

        $cases = [
            'gerente' => 'supervisor',
            'soporte_n1' => 'agente',
            'soporte_n2' => 'agente',
            'soporte_n3' => 'agente',
            'usuario' => 'solicitante',
            'consultor' => 'agente',
        ];

        $users = [];
        foreach ($cases as $legacy => $expectedNew) {
            $user = $this->makeUser();
            $user->assignRole($legacy);
            $users[$legacy] = $user;
        }

        $this->artisan('roles:migrate-legacy')->assertSuccessful();

        foreach ($cases as $legacy => $expectedNew) {
            $this->assertTrue($users[$legacy]->fresh()->hasRole($expectedNew), "esperaba que {$legacy} -> {$expectedNew}");
        }
    }

    private function makeUser(): User
    {
        $now = now();
        $areaId = DB::table('areas')->insertGetId(['name' => 'A'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $positionId = DB::table('positions')->insertGetId(['name' => 'P'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        return User::create([
            'first_name' => 'Test', 'paternal_last_name' => 'User',
            'email' => 'u-'.uniqid().'@test.local', 'password' => Hash::make('x'),
            'employee_number' => (string) random_int(100000, 999999),
            'area_id' => $areaId, 'position_id' => $positionId, 'site_id' => null,
            'status' => 'active', 'onboarding_completed' => true,
        ]);
    }
}
