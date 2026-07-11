<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTenantFixtures;
use Tests\TestCase;

class BackfillTicketFoliosTest extends TestCase
{
    use CreatesTenantFixtures;
    use RefreshDatabase;

    public function test_assigns_folios_in_chronological_order(): void
    {
        $fixture = $this->createTenantFixtureSet();

        // Insertados fuera de orden a propósito; el backfill debe procesarlos
        // por created_at ASC, no por orden de inserción/id.
        $idLater = $this->insertTicketAt($fixture, now()->subMinutes(5));
        $idEarliest = $this->insertTicketAt($fixture, now()->subHours(2));
        $idMiddle = $this->insertTicketAt($fixture, now()->subHour());

        $this->artisan('tickets:backfill-folios')->assertSuccessful();

        $folioEarliest = Ticket::find($idEarliest)->folio;
        $folioMiddle = Ticket::find($idMiddle)->folio;
        $folioLater = Ticket::find($idLater)->folio;

        $this->assertNotNull($folioEarliest);
        $this->assertNotNull($folioMiddle);
        $this->assertNotNull($folioLater);
        $this->assertMatchesRegularExpression('/^TK-A00001-[A-Z0-9]+-\d{5}$/', $folioEarliest);
        $this->assertMatchesRegularExpression('/^TK-A00002-[A-Z0-9]+-\d{5}$/', $folioMiddle);
        $this->assertMatchesRegularExpression('/^TK-A00003-[A-Z0-9]+-\d{5}$/', $folioLater);
    }

    public function test_does_not_touch_tickets_that_already_have_a_folio(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $idWithFolio = $this->insertTicketAt($fixture, now()->subHour(), '00050');
        $idWithoutFolio = $this->insertTicketAt($fixture, now());

        $this->artisan('tickets:backfill-folios')->assertSuccessful();

        $this->assertSame('00050', Ticket::find($idWithFolio)->folio);
        // El backfill continúa la secuencia del tenant, no reinicia en 1.
        $this->assertMatchesRegularExpression('/^TK-A00001-[A-Z0-9]+-\d{5}$/', Ticket::find($idWithoutFolio)->folio);
    }

    public function test_folios_are_unique_and_isolated_per_tenant(): void
    {
        $fixtureA = $this->createTenantFixtureSet();

        // Segundo tenant manual (no vía createTenantFixtureSet(), que hardcodea
        // nombres de área/puesto y no se puede invocar dos veces en el mismo
        // test). Reusa los catálogos no específicos de tenant de fixtureA.
        $now = now();
        $clientBId = DB::table('clients')->insertGetId([
            'name' => 'Cliente B test',
            'code' => 'TSTB-'.uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $siteBId = DB::table('sites')->insertGetId([
            'name' => 'Sede B test',
            'code' => 'SEDB-'.uniqid(),
            'type' => 'physical',
            'is_active' => true,
            'client_id' => $clientBId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $fixtureB = array_merge($fixtureA, ['client_id' => $clientBId, 'site_id' => $siteBId]);

        $idA1 = $this->insertTicketAt($fixtureA, now()->subMinutes(2));
        $idA2 = $this->insertTicketAt($fixtureA, now()->subMinute());
        $idB1 = $this->insertTicketAt($fixtureB, now()->subMinutes(2));

        $this->artisan('tickets:backfill-folios')->assertSuccessful();

        $this->assertMatchesRegularExpression('/^TK-A00001-[A-Z0-9]+-\d{5}$/', Ticket::find($idA1)->folio);
        $this->assertMatchesRegularExpression('/^TK-A00002-[A-Z0-9]+-\d{5}$/', Ticket::find($idA2)->folio);
        // Tenant B tiene su propia secuencia, no continúa la de A.
        $this->assertMatchesRegularExpression('/^TK-A00001-[A-Z0-9]+-\d{5}$/', Ticket::find($idB1)->folio);
    }

    public function test_skips_tickets_without_client_id(): void
    {
        $fixture = $this->createTenantFixtureSet();

        // Este ticket simula el caso "doblemente huérfano" (sin client_id NI
        // folio) que el comando debe reportar y omitir sin tronar — ambos
        // constraints NOT NULL (Postgres) hay que relajarlos para insertarlo.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN client_id DROP NOT NULL');
            DB::statement('ALTER TABLE tickets ALTER COLUMN folio DROP NOT NULL');
        }

        $now = now();
        $orphanId = DB::table('tickets')->insertGetId([
            'subject' => 'Sin cliente',
            'area_origin_id' => $fixture['area_id'],
            'area_current_id' => $fixture['area_id'],
            'site_id' => $fixture['site_id'],
            'requester_id' => $fixture['user_id'],
            'ticket_type_id' => $fixture['ticket_type_id'],
            'priority_id' => $fixture['priority_id'],
            'ticket_state_id' => $fixture['ticket_state_id'],
            'client_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->artisan('tickets:backfill-folios')->assertSuccessful();

        $this->assertNull(Ticket::find($orphanId)->folio);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $id = $this->insertTicketAt($fixture, now());

        $this->artisan('tickets:backfill-folios --dry-run')->assertSuccessful();

        $this->assertNull(Ticket::find($id)->folio);
    }

    private function insertTicketAt(array $fixture, $createdAt, ?string $folio = null): int
    {
        // folio es NOT NULL en Postgres (Paso 1b). Estos tests simulan a
        // propósito el estado pre-backfill (folio NULL) para probar el
        // comando — relaja el constraint igual que hace
        // CreatesTenantFixtures::insertTicket() para client_id. Se revierte
        // solo al terminar el test (RefreshDatabase + DDL transaccional).
        if ($folio === null && DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN folio DROP NOT NULL');
        }

        return DB::table('tickets')->insertGetId([
            'subject' => 'Ticket sin folio',
            'area_origin_id' => $fixture['area_id'],
            'area_current_id' => $fixture['area_id'],
            'site_id' => $fixture['site_id'],
            'requester_id' => $fixture['user_id'],
            'ticket_type_id' => $fixture['ticket_type_id'],
            'priority_id' => $fixture['priority_id'],
            'ticket_state_id' => $fixture['ticket_state_id'],
            'client_id' => $fixture['client_id'],
            'folio' => $folio,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
