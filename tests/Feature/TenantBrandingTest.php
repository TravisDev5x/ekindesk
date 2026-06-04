<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_branding_exposes_mode_only(): void
    {
        config(['tenancy.base_domain' => 'ekindesk.test']);

        $request = Request::create('http://ekindesk.test/home', 'GET');
        $branding = app(TenantContextService::class)->resolve($request)->brandingForFrontend();

        $this->assertSame('platform', $branding['mode']);
        $this->assertArrayNotHasKey('name', $branding);
    }

    public function test_client_portal_branding_includes_logo_and_color(): void
    {
        if (! \Schema::hasColumn('clients', 'portal_slug')) {
            $this->markTestSkipped('Migración portal_slug no aplicada.');
        }

        Cliente::create([
            'name' => 'Empresa Alpha',
            'portal_slug' => 'alpha',
            'logo_path' => 'clients/logos/alpha.png',
            'portal_primary_color' => '#2563eb',
            'portal_welcome_message' => 'Bienvenido al portal.',
            'is_active' => true,
        ]);

        config(['tenancy.base_domain' => 'ekindesk.test']);

        $request = Request::create('http://alpha.ekindesk.test/home', 'GET');
        $branding = app(TenantContextService::class)->resolve($request)->brandingForFrontend();

        $this->assertSame('client_portal', $branding['mode']);
        $this->assertSame('Empresa Alpha', $branding['name']);
        $this->assertSame('clients/logos/alpha.png', $branding['logo_path']);
        $this->assertSame('#2563eb', $branding['portal_primary_color']);
        $this->assertSame('Bienvenido al portal.', $branding['portal_welcome_message']);
    }
}
