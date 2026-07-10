<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Priority;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantSeedCatalogsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tenancy.catalog_per_client' => false]);
    }

    public function test_seeds_template_catalogs_for_operator_without_platform_rows(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', ['operator_user_id' => $operator->id])
            ->assertSuccessful();

        $this->assertGreaterThanOrEqual(4, Priority::where('operator_user_id', $operator->id)->count());
        $this->assertGreaterThanOrEqual(3, Area::where('operator_user_id', $operator->id)->count());
    }

    public function test_second_run_is_idempotent(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', ['operator_user_id' => $operator->id])->assertSuccessful();
        $countAfterFirst = Priority::where('operator_user_id', $operator->id)->count();

        $this->artisan('tenant:seed-catalogs', ['operator_user_id' => $operator->id])
            ->assertSuccessful()
            ->expectsOutputToContain('ya existían');

        $this->assertSame($countAfterFirst, Priority::where('operator_user_id', $operator->id)->count());
    }

    public function test_skips_clone_when_platform_row_already_exists(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        foreach ([
            ['name' => 'Crítica', 'level' => 1],
            ['name' => 'Alta', 'level' => 2],
            ['name' => 'Media', 'level' => 3],
            ['name' => 'Baja', 'level' => 4],
        ] as $row) {
            Priority::create([
                'name' => $row['name'],
                'level' => $row['level'],
                'is_active' => true,
                'operator_user_id' => null,
                'client_id' => null,
            ]);
        }

        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', [
            'operator_user_id' => $operator->id,
            '--only' => 'priorities',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('heredadas');

        $this->assertSame(0, Priority::where('operator_user_id', $operator->id)->count());
    }

    public function test_dry_run_does_not_persist_rows(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', [
            'operator_user_id' => $operator->id,
            '--dry-run' => true,
            '--only' => 'priorities',
        ])->assertSuccessful();

        $this->assertSame(0, Priority::where('operator_user_id', $operator->id)->count());
    }

    public function test_ticket_types_sync_operator_areas(): void
    {
        if (! \Schema::hasColumn('ticket_types', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', [
            'operator_user_id' => $operator->id,
            '--only' => 'areas,ticket_types',
        ])->assertSuccessful();

        $type = TicketType::where('operator_user_id', $operator->id)->where('code', 'falla_de_equipo')->first();
        $this->assertNotNull($type);
        $this->assertTrue($type->areas()->exists());
    }

    public function test_requires_client_id_when_catalog_per_client_enabled(): void
    {
        if (! \Schema::hasColumn('priorities', 'client_id')) {
            $this->markTestSkipped('Migración client_id en catálogos no aplicada.');
        }

        config(['tenancy.catalog_per_client' => true]);
        $operator = $this->bareOperator();

        $this->artisan('tenant:seed-catalogs', ['operator_user_id' => $operator->id])
            ->assertFailed();
    }

    public function test_invalid_operator_id_fails(): void
    {
        $this->artisan('tenant:seed-catalogs', ['operator_user_id' => 999999])
            ->assertFailed();
    }

    private function bareOperator(): User
    {
        $now = now();
        $areaId = DB::table('areas')->insertGetId(['name' => 'Seed'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $positionId = DB::table('positions')->insertGetId(['name' => 'P'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $siteId = DB::table('sites')->insertGetId([
            'name' => 'S'.uniqid(), 'code' => 'X'.uniqid(), 'type' => 'physical',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return User::create([
            'first_name' => 'Op',
            'paternal_last_name' => 'MSP',
            'email' => uniqid().'@operator.test',
            'password' => Hash::make('password'),
            'employee_number' => (string) random_int(100000, 999999),
            'area_id' => $areaId,
            'position_id' => $positionId,
            'site_id' => $siteId,
            'status' => 'active',
            'is_operator' => true,
            'email_verified_at' => now(),
        ]);
    }
}
