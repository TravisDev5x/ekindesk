<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesMspTwinClientFixtures;
use Tests\TestCase;

/**
 * Verifica políticas RLS de PostgreSQL (fase 2.2).
 * Requiere DB_CONNECTION=pgsql, TENANCY_PGSQL_RLS=true y usuario BD no superuser.
 */
class PgsqlTenantRlsTest extends TestCase
{
    use CreatesMspTwinClientFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! PgsqlRowLevelSecurity::enabled()) {
            $this->markTestSkipped('Requiere PostgreSQL con TENANCY_PGSQL_RLS=true.');
        }
    }

    public function test_rls_filters_tickets_by_bound_user_client_id(): void
    {
        $world = $this->createTwinClientIsolationWorld();

        PgsqlRowLevelSecurity::setBypass(true);
        $this->assertSame(2, Ticket::query()->count());

        PgsqlRowLevelSecurity::applyForUser($world['agentA']);
        $this->assertSame(1, Ticket::query()->count());
        $this->assertSame('Ticket Alpha', Ticket::query()->value('subject'));

        PgsqlRowLevelSecurity::clear();
    }

    public function test_rls_blocks_select_of_foreign_ticket_by_id(): void
    {
        $world = $this->createTwinClientIsolationWorld();

        PgsqlRowLevelSecurity::applyForUser($world['agentA']);

        $this->assertNull(Ticket::query()->find($world['ticketB']->id));
        $this->assertNotNull(Ticket::query()->find($world['ticketA']->id));

        PgsqlRowLevelSecurity::clear();
    }

    public function test_rls_portal_context_limits_rows_to_enforced_client(): void
    {
        if (! \Schema::hasColumn('clients', 'portal_slug')) {
            $this->markTestSkipped('Migración portal_slug no aplicada.');
        }

        $world = $this->createTwinClientIsolationWorld();
        $this->resetTenantContext();

        $request = \Illuminate\Http\Request::create(
            $this->portalApiUrl($world['clientA'], '/api/tickets'),
            'GET'
        );
        app(\App\Services\TenantContextService::class)->resolve($request);

        PgsqlRowLevelSecurity::applyForUser($world['agentA']);

        $subjects = Ticket::query()->pluck('subject');
        $this->assertTrue($subjects->contains('Ticket Alpha'));
        $this->assertFalse($subjects->contains('Ticket Beta'));

        PgsqlRowLevelSecurity::clear();
        $this->resetTenantContext();
    }
}
