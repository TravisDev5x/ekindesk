<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Support\Database\TenantBackfill;
use App\Support\Database\TenantIntegrity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTenantFixtures;
use Tests\TestCase;

class TenantClientIdIntegrityTest extends TestCase
{
    use CreatesTenantFixtures;
    use RefreshDatabase;

    public function test_backfill_sets_client_id_from_site(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $ticketId = $this->insertTicket($fixture, null);

        TenantBackfill::syncClientIdFromSites('tickets');

        $this->assertSame(
            $fixture['client_id'],
            (int) DB::table('tickets')->where('id', $ticketId)->value('client_id')
        );
    }

    public function test_ticket_model_saving_syncs_client_id_from_sede(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $ticket = Ticket::find($this->insertTicket($fixture, null));
        $ticket->client_id = null;
        $ticket->save();

        $this->assertSame($fixture['client_id'], (int) $ticket->fresh()->client_id);
    }

    public function test_integrity_detects_orphan_ticket(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $this->insertTicket($fixture, null);

        $this->assertGreaterThan(0, TenantIntegrity::orphanTicketCount());

        $this->expectException(\RuntimeException::class);
        TenantIntegrity::assertSynced();
    }

    public function test_artisan_sync_and_verify(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $this->insertTicket($fixture, null);

        $this->artisan('tenant:client-id', ['action' => 'sync'])
            ->assertSuccessful();

        $this->assertSame(0, TenantIntegrity::orphanTicketCount());
    }

    public function test_artisan_verify_strict_fails_on_orphan_ticket(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $this->insertTicket($fixture, null);

        $this->artisan('tenant:client-id', ['action' => 'verify', '--strict' => true])
            ->assertFailed();
    }

    public function test_artisan_verify_strict_passes_after_sync(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $this->insertTicket($fixture, null);

        $this->artisan('tenant:client-id', ['action' => 'sync'])
            ->assertSuccessful();

        $this->artisan('tenant:client-id', ['action' => 'verify', '--strict' => true])
            ->assertSuccessful();
    }

    public function test_artisan_verify_non_strict_warns_but_succeeds_with_null_client_on_orphan_site(): void
    {
        $fixture = $this->createTenantFixtureSet();
        DB::table('sites')->where('id', $fixture['site_id'])->update(['client_id' => null]);
        $this->insertTicket($fixture, null);

        $this->artisan('tenant:client-id', ['action' => 'verify'])
            ->assertSuccessful();

        $this->artisan('tenant:client-id', ['action' => 'verify', '--strict' => true])
            ->assertFailed();
    }

    public function test_no_tickets_without_client_id_after_migrations(): void
    {
        if (! \Schema::hasColumn('tickets', 'client_id')) {
            $this->markTestSkipped('Migraciones tenant no aplicadas.');
        }

        $this->assertSame(0, TenantIntegrity::ticketsWithNullClientCount());
        $this->assertSame(0, TenantIntegrity::orphanTicketCount());
    }
}
