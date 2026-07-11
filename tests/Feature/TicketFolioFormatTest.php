<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\TicketSequence;
use App\Services\TicketCreationService;
use App\Services\TicketPrefixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTenantFixtures;
use Tests\TestCase;

/**
 * Fase 1 del sprint maestro: formato de folio
 * TK-{Letra}{Número:5}-{PrefijoTenant}-{Random:5}, ej. TK-A00042-SYD-88291.
 */
class TicketFolioFormatTest extends TestCase
{
    use CreatesTenantFixtures;
    use RefreshDatabase;

    private const FORMAT = '/^TK-[A-Z]\d{5}-[A-Z0-9]{1,10}-\d{5}$/';

    // ── Formato ─────────────────────────────────────────────────────────

    public function test_created_ticket_folio_matches_new_format(): void
    {
        $fixture = $this->createTenantFixtureSet();

        $ticket = $this->createTicketVia($fixture);

        $this->assertMatchesRegularExpression(self::FORMAT, $ticket->folio);
    }

    public function test_folio_embeds_client_ticket_prefix(): void
    {
        $client = Client::create(['name' => 'Soporte Digital Norte', 'is_active' => true]);

        $this->assertSame('SDN', $client->ticket_prefix);

        $folio = app(TicketCreationService::class)->nextFolioFor($client);
        $this->assertStringContainsString('-SDN-', $folio);
        $this->assertStringStartsWith('TK-A00001-', $folio);
    }

    // ── Rollover de letra (contador manipulado, no 100k tickets) ────────

    public function test_letter_rolls_over_to_b_at_100000(): void
    {
        $client = Client::create(['name' => 'Rollover Uno', 'is_active' => true]);

        DB::table('ticket_sequences')->insert([
            'client_id' => $client->id, 'last_number' => 99998,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(TicketCreationService::class);

        // n=99999 → todavía A99999
        $this->assertStringStartsWith('TK-A99999-', $service->nextFolioFor($client));
        // n=100000 → B00000 (mostrado = interno - 100000)
        $this->assertStringStartsWith('TK-B00000-', $service->nextFolioFor($client));
        // n=100001 → B00001
        $this->assertStringStartsWith('TK-B00001-', $service->nextFolioFor($client));
    }

    public function test_letter_c_block_and_z_exhaustion(): void
    {
        $client = Client::create(['name' => 'Rollover Dos', 'is_active' => true]);

        DB::table('ticket_sequences')->insert([
            'client_id' => $client->id, 'last_number' => 199999,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(TicketCreationService::class);

        // n=200000 → C00000
        $this->assertStringStartsWith('TK-C00000-', $service->nextFolioFor($client));

        // Agotar Z: n=2600000 → bloque 26, fuera de rango
        DB::table('ticket_sequences')->where('client_id', $client->id)->update(['last_number' => 2599999]);
        $this->expectException(\RuntimeException::class);
        $service->nextFolioFor($client);
    }

    // ── Derivación de prefijo ───────────────────────────────────────────

    public function test_prefix_ignores_connectors_and_strips_accents(): void
    {
        $service = app(TicketPrefixService::class);

        $this->assertSame('SDN', $service->derive('Soporte y Desarrollo del Norte'));
        $this->assertSame('TM', $service->derive('Tecnología de México'));
        // Una sola palabra significativa → primeras 3 letras
        $this->assertSame('ACM', $service->derive('Acme'));
        // "Los", "y", "la" son conectores; el acento de Álamos se normaliza
        $this->assertSame('AC', $service->derive('Los Álamos y la Cumbre'));
    }

    public function test_prefix_collision_appends_numeric_suffix(): void
    {
        Client::create(['name' => 'Soporte Digital Norte', 'is_active' => true]);
        $second = Client::create(['name' => 'Sistemas Digitales Nacionales', 'is_active' => true]);
        $third = Client::create(['name' => 'Servicios Dinámicos del Noreste', 'is_active' => true]);

        $this->assertSame('SDN2', $second->ticket_prefix);
        $this->assertSame('SDN3', $third->ticket_prefix);
    }

    public function test_prefix_is_unique_constraint_at_db_level(): void
    {
        Client::create(['name' => 'Alfa Beta', 'is_active' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        // Insert crudo saltándose el hook de derivación, con prefijo duplicado.
        DB::table('clients')->insert([
            'name' => 'Otro Nombre', 'ticket_prefix' => 'AB',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── Inmutabilidad ────────────────────────────────────────────────────

    public function test_prefix_is_immutable_after_assignment(): void
    {
        $client = Client::create(['name' => 'Empresa Original', 'is_active' => true]);
        $original = $client->ticket_prefix;

        // Renombrar al cliente NO recalcula el prefijo
        $client->update(['name' => 'Nombre Totalmente Distinto SA']);
        $this->assertSame($original, $client->fresh()->ticket_prefix);

        // Intento directo de cambiarlo → revertido por el hook updating
        $client->ticket_prefix = 'HACK';
        $client->save();
        $this->assertSame($original, $client->fresh()->ticket_prefix);
    }

    public function test_folio_never_changes_after_creation(): void
    {
        $fixture = $this->createTenantFixtureSet();
        $ticket = $this->createTicketVia($fixture);
        $folio = $ticket->folio;

        $ticket->update(['subject' => 'Asunto editado']);

        $this->assertSame($folio, $ticket->fresh()->folio);
    }

    // ── Unicidad bajo el mecanismo atómico ──────────────────────────────

    public function test_sequential_folios_are_all_distinct(): void
    {
        // La garantía real de concurrencia vive en el mecanismo de BD
        // (INSERT ON CONFLICT + UPDATE RETURNING toma row lock y serializa
        // escritores en Postgres); aquí se verifica que N generaciones
        // seguidas jamás repiten folio ni número.
        $client = Client::create(['name' => 'Carga Concurrente', 'is_active' => true]);
        $service = app(TicketCreationService::class);

        $folios = [];
        for ($i = 0; $i < 25; $i++) {
            $folios[] = $service->nextFolioFor($client);
        }

        $this->assertCount(25, array_unique($folios));
        $this->assertSame(25, (int) DB::table('ticket_sequences')->where('client_id', $client->id)->value('last_number'));
    }

    // ── Backfill de prefijos ────────────────────────────────────────────

    public function test_backfill_prefix_command_assigns_and_is_idempotent(): void
    {
        // Insert crudo: sin hook de modelo → sin prefijo (simula cliente
        // anterior a la columna).
        $id = DB::table('clients')->insertGetId([
            'name' => 'Cliente Legado Industrial', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('tenants:backfill-ticket-prefix --dry-run')->assertSuccessful();
        $this->assertNull(Client::find($id)->ticket_prefix);

        $this->artisan('tenants:backfill-ticket-prefix')->assertSuccessful();
        $prefix = Client::find($id)->ticket_prefix;
        $this->assertSame('CLI', $prefix);

        // Idempotente: segunda corrida no cambia nada
        $this->artisan('tenants:backfill-ticket-prefix')->assertSuccessful();
        $this->assertSame($prefix, Client::find($id)->ticket_prefix);
    }

    private function createTicketVia(array $fixture): Ticket
    {
        return app(TicketCreationService::class)->create([
            'subject' => 'Formato de folio',
            'area_origin_id' => $fixture['area_id'],
            'area_current_id' => $fixture['area_id'],
            'site_id' => $fixture['site_id'],
            'client_id' => $fixture['client_id'],
            'requester_id' => $fixture['user_id'],
            'ticket_type_id' => $fixture['ticket_type_id'],
            'priority_id' => $fixture['priority_id'],
            'ticket_state_id' => $fixture['ticket_state_id'],
        ]);
    }
}
