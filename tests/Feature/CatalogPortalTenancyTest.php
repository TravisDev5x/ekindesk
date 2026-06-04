<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Priority;
use App\Models\User;
use App\Services\OperatorCatalogScopeService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogPortalTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tenancy.catalog_per_client' => false]);
        $this->resetTenantContext();
    }

    public function test_portal_sees_platform_and_operator_catalog_not_other_operator_rows(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $op = $this->bareUser(['is_operator' => true]);
        $clientA = Cliente::create([
            'name' => 'Portal A',
            'portal_slug' => 'portal-a',
            'operator_user_id' => $op->id,
            'is_active' => true,
        ]);
        $otherOp = $this->bareUser(['is_operator' => true, 'email' => 'other-msp@test.local']);
        Cliente::create([
            'name' => 'Portal B',
            'portal_slug' => 'portal-b',
            'operator_user_id' => $otherOp->id,
            'is_active' => true,
        ]);

        Priority::create(['name' => 'Plataforma', 'level' => 5, 'is_active' => true, 'operator_user_id' => null, 'client_id' => null]);
        Priority::create(['name' => 'Del MSP', 'level' => 4, 'is_active' => true, 'operator_user_id' => $op->id, 'client_id' => null]);
        Priority::create(['name' => 'Otro operador', 'level' => 3, 'is_active' => true, 'operator_user_id' => $otherOp->id, 'client_id' => null]);

        config(['tenancy.base_domain' => 'ekindesk.test', 'tenancy.strict_client_portal' => true]);

        $request = Request::create('http://portal-a.ekindesk.test/api/catalogs', 'GET');
        $this->resetTenantContext();
        $this->app->instance('request', $request);
        app(TenantContextService::class)->resolve($request);

        $staff = $this->bareUser([
            'email' => 'staff@portal-a.test',
            'client_id' => $clientA->id,
        ]);

        $names = app(OperatorCatalogScopeService::class)
            ->apply(Priority::query(), $staff, 'priorities')
            ->pluck('name');

        $this->assertTrue($names->contains('Plataforma'));
        $this->assertTrue($names->contains('Del MSP'));
        $this->assertFalse($names->contains('Otro operador'));
    }

    public function test_portal_create_assigns_operator_not_client_id(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $op = $this->bareUser(['is_operator' => true]);
        $client = Cliente::create([
            'name' => 'Portal C',
            'portal_slug' => 'portal-c',
            'operator_user_id' => $op->id,
            'is_active' => true,
        ]);

        config(['tenancy.base_domain' => 'ekindesk.test', 'tenancy.strict_client_portal' => true]);

        $request = Request::create('http://portal-c.ekindesk.test/', 'GET');
        $this->resetTenantContext();
        $this->app->instance('request', $request);
        app(TenantContextService::class)->resolve($request);

        $staff = $this->bareUser(['client_id' => $client->id]);
        $attrs = app(OperatorCatalogScopeService::class)->operatorAttributesForCreate($staff);

        $this->assertSame($op->id, $attrs['operator_user_id']);
        $this->assertNull($attrs['client_id'] ?? null);
    }

    public function test_per_client_catalog_mode_filters_by_client_id(): void
    {
        if (! \Schema::hasColumn('priorities', 'client_id') || ! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Columnas client_id/operator en catálogos no aplicadas.');
        }

        config([
            'tenancy.catalog_per_client' => true,
            'tenancy.base_domain' => 'ekindesk.test',
            'tenancy.strict_client_portal' => true,
        ]);

        $op = $this->bareUser(['is_operator' => true]);
        $clientA = Cliente::create(['name' => 'A', 'portal_slug' => 'pc-a', 'operator_user_id' => $op->id, 'is_active' => true]);
        $clientB = Cliente::create(['name' => 'B', 'portal_slug' => 'pc-b', 'operator_user_id' => $op->id, 'is_active' => true]);

        Priority::create(['name' => 'Para A', 'level' => 3, 'is_active' => true, 'operator_user_id' => $op->id, 'client_id' => $clientA->id]);
        Priority::create(['name' => 'Para B', 'level' => 2, 'is_active' => true, 'operator_user_id' => $op->id, 'client_id' => $clientB->id]);

        $request = Request::create('http://pc-a.ekindesk.test/', 'GET');
        $this->resetTenantContext();
        $this->app->instance('request', $request);
        $ctx = app(TenantContextService::class)->resolve($request);

        $this->assertTrue($ctx->isClientPortal());
        $this->assertTrue(app(OperatorCatalogScopeService::class)->usesPerClientCatalogInPortal());

        $staff = $this->bareUser(['client_id' => $clientA->id]);
        $names = app(OperatorCatalogScopeService::class)
            ->apply(Priority::query(), $staff, 'priorities')
            ->pluck('name');

        $this->assertTrue($names->contains('Para A'));
        $this->assertFalse($names->contains('Para B'));
    }

    private function resetTenantContext(): void
    {
        $this->app->forgetInstance(TenantContextService::class);
    }

    private function bareUser(array $overrides = []): User
    {
        $now = now();
        $areaId = DB::table('areas')->insertGetId(['name' => 'A'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $positionId = DB::table('positions')->insertGetId(['name' => 'P'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $siteId = DB::table('sites')->insertGetId([
            'name' => 'S'.uniqid(), 'code' => 'X'.random_int(100, 999), 'type' => 'physical',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return User::create(array_merge([
            'first_name' => 'T', 'paternal_last_name' => 'U',
            'email' => uniqid().'@t.local', 'password' => Hash::make('x'),
            'employee_number' => (string) random_int(100000, 999999),
            'area_id' => $areaId, 'position_id' => $positionId, 'sede_id' => $siteId, 'status' => 'active',
        ], $overrides));
    }
}
