<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Priority;
use App\Models\User;
use App\Services\OperatorCatalogScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantCatalogScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_sees_global_and_own_catalog_rows(): void
    {
        if (! \Schema::hasColumn('priorities', 'operator_user_id')) {
            $this->markTestSkipped('Migración catálogos por operador no aplicada.');
        }

        $operator = $this->bareUser(['is_operator' => true]);
        $other = $this->bareUser(['is_operator' => true, 'email' => 'other-op@test.local']);

        Priority::create(['name' => 'Global', 'level' => 5, 'is_active' => true, 'operator_user_id' => null]);
        Priority::create(['name' => 'Del operador', 'level' => 4, 'is_active' => true, 'operator_user_id' => $operator->id]);
        Priority::create(['name' => 'Ajeno', 'level' => 3, 'is_active' => true, 'operator_user_id' => $other->id]);

        $scope = app(OperatorCatalogScopeService::class);
        $names = $scope->apply(Priority::query(), $operator, 'priorities')->pluck('name');

        $this->assertTrue($names->contains('Global'));
        $this->assertTrue($names->contains('Del operador'));
        $this->assertFalse($names->contains('Ajeno'));
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
