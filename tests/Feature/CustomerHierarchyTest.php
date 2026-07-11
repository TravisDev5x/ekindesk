<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use App\Services\InternalCustomerService;
use App\Services\TicketCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesTenantFixtures;
use Tests\TestCase;

/**
 * Fase 2 del sprint maestro (Opción B): customer implícito, site_id nullable
 * con herencia desde el requester, e integridad no-cross-tenant.
 */
class CustomerHierarchyTest extends TestCase
{
    use CreatesTenantFixtures;
    use RefreshDatabase;

    // ── Customer implícito ──────────────────────────────────────────────

    public function test_internal_customer_service_creates_implicit_customer_once(): void
    {
        $operator = $this->makeOperator();

        $service = app(InternalCustomerService::class);
        $customer = $service->ensureFor($operator, 'Mi Empresa SA de CV');

        $this->assertTrue($customer->is_internal);
        $this->assertSame($operator->id, $customer->operator_user_id);
        $this->assertSame('Mi Empresa SA de CV', $customer->name);
        $this->assertNotNull($customer->ticket_prefix);

        // Idempotente: segunda llamada devuelve el mismo, no duplica
        $again = $service->ensureFor($operator, 'Otro Nombre Ignorado');
        $this->assertSame($customer->id, $again->id);
        $this->assertSame(1, Client::where('operator_user_id', $operator->id)->where('is_internal', true)->count());
    }

    public function test_backfill_internal_customer_command(): void
    {
        $operator = $this->makeOperator();

        $this->artisan('tenants:backfill-internal-customer --dry-run')->assertSuccessful();
        $this->assertSame(0, Client::where('operator_user_id', $operator->id)->where('is_internal', true)->count());

        $this->artisan('tenants:backfill-internal-customer')->assertSuccessful();
        $this->assertSame(1, Client::where('operator_user_id', $operator->id)->where('is_internal', true)->count());

        // Idempotente
        $this->artisan('tenants:backfill-internal-customer')->assertSuccessful();
        $this->assertSame(1, Client::where('operator_user_id', $operator->id)->where('is_internal', true)->count());
    }

    // ── Herencia de site desde el requester ─────────────────────────────

    public function test_ticket_inherits_site_from_requester(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $ticket = app(TicketCreationService::class)->create($this->baseAttributes($fixture));

        $this->assertSame($fixture['site_id'], $ticket->site_id);
    }

    public function test_requester_without_site_creates_ticket_with_null_site(): void
    {
        $fixture = $this->createTenantFixtureSet();
        DB::table('users')->where('id', $fixture['user_id'])->update(['site_id' => null]);

        $ticket = app(TicketCreationService::class)->create($this->baseAttributes($fixture));

        $this->assertNull($ticket->site_id);
        // El tenant no se pierde por la falta de site
        $this->assertSame($fixture['client_id'], $ticket->client_id);
        $this->assertNotNull($ticket->folio);
    }

    public function test_explicit_site_id_is_respected_over_inheritance(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $otherSite = DB::table('sites')->insertGetId([
            'name' => 'Sede alterna', 'code' => 'ALT-'.uniqid(), 'type' => 'physical',
            'is_active' => true, 'client_id' => $fixture['client_id'],
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $attrs = $this->baseAttributes($fixture);
        $attrs['site_id'] = $otherSite;

        $ticket = app(TicketCreationService::class)->create($attrs);

        $this->assertSame($otherSite, $ticket->site_id);
    }

    // ── Integridad no-cross-tenant ──────────────────────────────────────

    public function test_site_of_another_tenant_is_rejected(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $foreignClient = Client::create(['name' => 'Tenant Ajeno', 'is_active' => true]);
        $foreignSite = DB::table('sites')->insertGetId([
            'name' => 'Sede ajena', 'code' => 'FOR-'.uniqid(), 'type' => 'physical',
            'is_active' => true, 'client_id' => $foreignClient->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $attrs = $this->baseAttributes($fixture);
        $attrs['site_id'] = $foreignSite;

        $this->expectException(\InvalidArgumentException::class);
        app(TicketCreationService::class)->create($attrs);
    }

    public function test_global_site_is_accepted_and_does_not_null_the_tenant(): void
    {
        $fixture = $this->createTenantFixtureSet();

        // "Remoto" del seed: site global sin client_id
        $globalSiteId = (int) DB::table('sites')->whereNull('client_id')->value('id');
        $this->assertGreaterThan(0, $globalSiteId);

        $attrs = $this->baseAttributes($fixture);
        $attrs['site_id'] = $globalSiteId;

        $ticket = app(TicketCreationService::class)->create($attrs);

        $this->assertSame($globalSiteId, $ticket->site_id);
        // El hook saving NO anula el client_id del ticket por site global
        $this->assertSame($fixture['client_id'], $ticket->client_id);
    }

    // ── sites.name único por client, ya no global ───────────────────────

    public function test_two_customers_can_have_sites_with_the_same_name(): void
    {
        $a = Client::create(['name' => 'Customer Uno', 'is_active' => true]);
        $b = Client::create(['name' => 'Customer Dos', 'is_active' => true]);

        $siteA = Site::create(['name' => 'Oficina Central', 'client_id' => $a->id, 'type' => 'physical', 'is_active' => true]);
        $siteB = Site::create(['name' => 'Oficina Central', 'client_id' => $b->id, 'type' => 'physical', 'is_active' => true]);

        $this->assertNotSame($siteA->id, $siteB->id);

        // Pero dentro del MISMO customer sí colisiona
        $this->expectException(\Illuminate\Database\QueryException::class);
        Site::create(['name' => 'Oficina Central', 'client_id' => $a->id, 'type' => 'physical', 'is_active' => true]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function makeOperator(): User
    {
        $now = now();
        $areaId = DB::table('areas')->insertGetId(['name' => 'A'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $positionId = DB::table('positions')->insertGetId(['name' => 'P'.uniqid(), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);

        return User::create([
            'first_name' => 'Operador', 'paternal_last_name' => 'Prueba',
            'email' => 'op-'.uniqid().'@test.local', 'password' => Hash::make('x'),
            'employee_number' => (string) random_int(100000, 999999),
            'area_id' => $areaId, 'position_id' => $positionId, 'site_id' => null,
            'status' => 'active', 'is_operator' => true, 'onboarding_completed' => true,
        ]);
    }

    private function baseAttributes(array $fixture): array
    {
        return [
            'subject' => 'Jerarquía',
            'area_origin_id' => $fixture['area_id'],
            'area_current_id' => $fixture['area_id'],
            'client_id' => $fixture['client_id'],
            'requester_id' => $fixture['user_id'],
            'ticket_type_id' => $fixture['ticket_type_id'],
            'priority_id' => $fixture['priority_id'],
            'ticket_state_id' => $fixture['ticket_state_id'],
        ];
    }
}
