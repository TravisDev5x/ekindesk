<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait CreatesTenantFixtures
{
    /**
     * @return array{client_id: int, site_id: int, area_id: int, user_id: int, ticket_state_id: int, priority_id: int, ticket_type_id: int}
     */
    protected function createTenantFixtureSet(): array
    {
        $now = now();

        $areaId = DB::table('areas')->insertGetId([
            'name' => 'Área test tenant',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $positionId = DB::table('positions')->insertGetId([
            'name' => 'Puesto test',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clientId = DB::table('clients')->insertGetId([
            'name' => 'Cliente test',
            'code' => 'TST-'.uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $siteId = DB::table('sites')->insertGetId([
            'name' => 'Sede test',
            'code' => 'SED-'.uniqid(),
            'type' => 'physical',
            'is_active' => true,
            'client_id' => $clientId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Test',
            'paternal_last_name' => 'User',
            'email' => 'tenant-test-'.uniqid().'@test.local',
            'password' => bcrypt('password'),
            'employee_number' => 'T'.random_int(10000, 99999),
            'area_id' => $areaId,
            'position_id' => $positionId,
            'site_id' => $siteId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $priorityId = DB::table('priorities')->insertGetId([
            'name' => 'Media test',
            'level' => 3,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $stateId = DB::table('ticket_states')->insertGetId([
            'name' => 'Abierto test',
            'code' => 'abierto_test_'.uniqid(),
            'is_active' => true,
            'is_final' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $typeId = DB::table('ticket_types')->insertGetId([
            'name' => 'Tipo test',
            'code' => 'tipo_test_'.uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'client_id' => $clientId,
            'site_id' => $siteId,
            'area_id' => $areaId,
            'user_id' => $userId,
            'ticket_state_id' => $stateId,
            'priority_id' => $priorityId,
            'ticket_type_id' => $typeId,
        ];
    }

    protected function insertTicket(array $fixture, ?int $clientId = null): int
    {
        $now = now();

        // La migración 2026_07_09_000027 deja client_id NOT NULL en Postgres (a
        // propósito, para producción). Estas fixtures simulan a propósito el
        // estado "huérfano" pre-backfill para probar TenantBackfill/TenantIntegrity,
        // así que hay que relajar el constraint aquí. RefreshDatabase envuelve cada
        // test en una transacción y Postgres soporta DDL transaccional, así que el
        // ALTER se revierte solo al terminar el test — no hace falta restaurarlo.
        if ($clientId === null && DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN client_id DROP NOT NULL');
        }

        return DB::table('tickets')->insertGetId([
            'subject' => 'Ticket tenant test',
            'area_origin_id' => $fixture['area_id'],
            'area_current_id' => $fixture['area_id'],
            'site_id' => $fixture['site_id'],
            'requester_id' => $fixture['user_id'],
            'ticket_type_id' => $fixture['ticket_type_id'],
            'priority_id' => $fixture['priority_id'],
            'ticket_state_id' => $fixture['ticket_state_id'],
            'client_id' => $clientId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
