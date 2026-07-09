<?php

namespace Tests\Feature\Multitenancy;

use App\Http\Middleware\ResolveTenantFromSubdomain;
use App\Models\Client;
use App\Services\Tenant\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContextService::clear();
        Cache::flush();
        config(['tenancy.base_domain' => 'tikara.test']);
    }

    protected function tearDown(): void
    {
        TenantContextService::clear();
        parent::tearDown();
    }

    public function test_tenant_resolves_from_subdomain(): void
    {
        $cliente = Client::create([
            'name'        => 'TechSolve',
            'portal_slug' => 'techsolve',
            'is_active'   => true,
        ]);

        $request = Request::create('http://techsolve.tikara.test/login', 'GET');
        $middleware = new ResolveTenantFromSubdomain();
        $response = $middleware->handle($request, fn ($r) => new Response('OK'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(TenantContextService::isResolved());
        $this->assertSame($cliente->id, TenantContextService::getId());
        $this->assertSame('techsolve', TenantContextService::getPortalSlug());
        $this->assertSame($cliente->id, $request->attributes->get('tenant_client_id'));
        $this->assertSame('techsolve', $request->attributes->get('tenant_subdomain'));
    }

    public function test_unknown_tenant_returns_404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $request = Request::create('http://fantasma.tikara.test/login', 'GET');
        $middleware = new ResolveTenantFromSubdomain();
        $middleware->handle($request, fn ($r) => new Response('OK'));
    }

    public function test_tenant_context_is_set_correctly(): void
    {
        $this->assertFalse(TenantContextService::isResolved());

        $cliente = Client::create([
            'name'        => 'ConstructMX',
            'portal_slug' => 'constructmx',
            'is_active'   => true,
        ]);

        $request = Request::create('http://constructmx.tikara.test/dashboard', 'GET');
        $middleware = new ResolveTenantFromSubdomain();
        $middleware->handle($request, fn ($r) => new Response('OK'));

        $this->assertTrue(TenantContextService::isResolved());
        $tenant = TenantContextService::get();
        $this->assertNotNull($tenant);
        $this->assertSame($cliente->id, $tenant->id);
        $this->assertSame('constructmx', $tenant->portal_slug);
    }

    public function test_root_domain_has_no_tenant_context(): void
    {
        $request = Request::create('http://tikara.test/', 'GET');
        $middleware = new ResolveTenantFromSubdomain();
        $middleware->handle($request, fn ($r) => new Response('OK'));

        $this->assertFalse(TenantContextService::isResolved());
    }

    public function test_inactive_tenant_returns_404(): void
    {
        Client::create([
            'name'        => 'Suspendido SA',
            'portal_slug' => 'suspendido',
            'is_active'   => false,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $request = Request::create('http://suspendido.tikara.test/login', 'GET');
        $middleware = new ResolveTenantFromSubdomain();
        $middleware->handle($request, fn ($r) => new Response('OK'));
    }

    public function test_x_tenant_subdomain_header_takes_priority_over_host(): void
    {
        $cliente = Client::create([
            'name'        => 'TechGroup',
            'portal_slug' => 'techgroup',
            'is_active'   => true,
        ]);

        // Host is root domain, but the explicit header overrides it
        $request = Request::create('http://tikara.test/dashboard', 'GET');
        $request->headers->set('X-Tenant-Subdomain', 'techgroup');

        $middleware = new ResolveTenantFromSubdomain();
        $middleware->handle($request, fn ($r) => new Response('OK'));

        $this->assertTrue(TenantContextService::isResolved());
        $this->assertSame($cliente->id, TenantContextService::getId());
    }

    public function test_tenant_context_is_cleared_between_tests(): void
    {
        // Static context is cleared in setUp — this verifies isolation holds
        $this->assertFalse(TenantContextService::isResolved());
        $this->assertNull(TenantContextService::get());
        $this->assertNull(TenantContextService::getId());
    }

    public function test_getorfail_throws_when_context_not_resolved(): void
    {
        $this->expectException(\RuntimeException::class);
        TenantContextService::getOrFail();
    }
}
