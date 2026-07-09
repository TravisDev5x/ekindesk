<?php

namespace Tests\Feature\Classification;

use App\Models\Client;
use App\Models\TicketClassificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassificationRuleTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required lookup rows that FK constraints reference
        \DB::table('ticket_types')->insert([
            ['id' => 1, 'name' => 'Falla de equipo', 'code' => 'falla', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Acceso a sistema', 'code' => 'acceso', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Solicitud de cambio', 'code' => 'cambio', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        \DB::table('priorities')->insert([
            ['id' => 1, 'name' => 'Crítica', 'level' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Alta', 'level' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Media', 'level' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Baja', 'level' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->client = Client::factory()->create();
    }

    // ─── evaluate() ──────────────────────────────────────────────────────────

    public function test_evaluate_returns_null_when_no_rules(): void
    {
        $result = TicketClassificationRule::evaluate('Server down', 'ping fails', $this->client->id);

        $this->assertNull($result);
    }

    public function test_evaluate_matches_keyword_in_subject(): void
    {
        $this->createRule(['keywords' => ['server'], 'ticket_type_id' => 1, 'priority_id' => 1]);

        $result = TicketClassificationRule::evaluate('Server is down', 'No details', $this->client->id);

        $this->assertNotNull($result);
        $this->assertSame(1, $result['ticket_type_id']);
        $this->assertSame(1, $result['priority_id']);
        $this->assertSame('rule', $result['source']);
    }

    public function test_evaluate_matches_keyword_in_body(): void
    {
        $this->createRule(['keywords' => ['impresora'], 'ticket_type_id' => 1, 'priority_id' => 3]);

        $result = TicketClassificationRule::evaluate('Problema', 'Mi impresora no imprime', $this->client->id);

        $this->assertNotNull($result);
        $this->assertSame(1, $result['ticket_type_id']);
    }

    public function test_evaluate_is_case_insensitive(): void
    {
        $this->createRule(['keywords' => ['VPN'], 'ticket_type_id' => 2, 'priority_id' => 2]);

        $result = TicketClassificationRule::evaluate('vpn no conecta', '', $this->client->id);

        $this->assertNotNull($result);
        $this->assertSame(2, $result['ticket_type_id']);
    }

    public function test_evaluate_skips_inactive_rules(): void
    {
        $this->createRule(['keywords' => ['backup'], 'is_active' => false, 'ticket_type_id' => 1]);

        $result = TicketClassificationRule::evaluate('backup failed', '', $this->client->id);

        $this->assertNull($result);
    }

    public function test_evaluate_ignores_other_tenant_rules(): void
    {
        $otherClient = Client::factory()->create();
        $this->createRule([
            'keywords'       => ['disco'],
            'ticket_type_id' => 1,
            'priority_id'    => 1,
        ], $otherClient->id);

        $result = TicketClassificationRule::evaluate('disco duro lleno', '', $this->client->id);

        $this->assertNull($result);
    }

    public function test_evaluate_respects_sort_order(): void
    {
        $this->createRule(['keywords' => ['acceso'], 'ticket_type_id' => 2, 'sort_order' => 10]);
        $this->createRule(['keywords' => ['acceso'], 'ticket_type_id' => 3, 'sort_order' => 5]);

        $result = TicketClassificationRule::evaluate('No tengo acceso al sistema', '', $this->client->id);

        // Lower sort_order wins
        $this->assertSame(3, $result['ticket_type_id']);
    }

    public function test_evaluate_matches_first_keyword_in_list(): void
    {
        $this->createRule(['keywords' => ['red', 'wifi', 'internet'], 'ticket_type_id' => 1, 'priority_id' => 2]);

        $result = TicketClassificationRule::evaluate('No hay internet', '', $this->client->id);

        $this->assertNotNull($result);
        $this->assertSame('test-rule', $result['rule_name']);
    }

    public function test_evaluate_returns_rule_name(): void
    {
        $this->createRule([
            'name'           => 'Regla VPN',
            'keywords'       => ['vpn'],
            'ticket_type_id' => 2,
        ]);

        $result = TicketClassificationRule::evaluate('VPN caída', '', $this->client->id);

        $this->assertSame('Regla VPN', $result['rule_name']);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function test_scope_active_filters_inactive(): void
    {
        $this->createRule(['is_active' => true]);
        $this->createRule(['is_active' => false]);

        $active = TicketClassificationRule::active()->count();

        $this->assertSame(1, $active);
    }

    public function test_scope_for_client_filters_by_client(): void
    {
        $otherClient = Client::factory()->create();
        $this->createRule([], $this->client->id);
        $this->createRule([], $otherClient->id);

        $count = TicketClassificationRule::forClient($this->client->id)->count();

        $this->assertSame(1, $count);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createRule(array $overrides = [], ?int $clientId = null): TicketClassificationRule
    {
        return TicketClassificationRule::create(array_merge([
            'client_id'      => $clientId ?? $this->client->id,
            'name'           => 'test-rule',
            'keywords'       => ['keyword'],
            'ticket_type_id' => 1,
            'priority_id'    => 3,
            'is_active'      => true,
            'sort_order'     => 1,
        ], $overrides));
    }
}
