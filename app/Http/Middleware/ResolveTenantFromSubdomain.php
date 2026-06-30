<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use App\Services\Tenant\TenantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el tenant (Cliente) desde el subdominio de la request.
 *
 * Flujo:
 *   {slug}.tikara.test → busca Cliente por portal_slug={slug}
 *   tikara.test        → sin tenant (dominio raíz)
 *   admin.tikara.test  → sin tenant (reservado)
 *
 * Si el slug existe → TenantContextService::set($cliente)
 * Si el slug NO existe en BD → abort(404)
 * Si no hay subdominio → deja pasar sin tenant
 *
 * Se complementa con EnforceTenantBoundary que restringe el acceso
 * por usuario autenticado una vez el tenant está resuelto.
 */
class ResolveTenantFromSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $this->extractSlug($request);

        if ($slug === null) {
            return $next($request);
        }

        $reserved = array_merge(
            config('tenancy.reserved_subdomains', []),
            ['www', 'admin', 'api', 'app', 'mail', 'ftp', 'smtp']
        );

        if (in_array($slug, $reserved, true)) {
            return $next($request);
        }

        $cliente = Cache::remember("tenant.portal.{$slug}", 300, function () use ($slug) {
            return Cliente::where('portal_slug', $slug)
                ->where('is_active', true)
                ->first();
        });

        if ($cliente === null) {
            abort(404);
        }

        TenantContextService::set($cliente);

        $request->attributes->set('tenant_client_id', $cliente->id);
        $request->attributes->set('tenant_subdomain', $slug);

        return $next($request);
    }

    private function extractSlug(Request $request): ?string
    {
        // Cabecera explícita tiene prioridad (útil en tests y proxies)
        $header = $request->header('X-Tenant-Subdomain');
        if ($header) {
            return strtolower(trim($header));
        }

        $host = strtolower(explode(':', $request->getHost())[0]);
        $base = config('tenancy.base_domain');

        if ($base) {
            $base = strtolower($base);
            if (str_ends_with($host, '.' . $base)) {
                $slug = substr($host, 0, -(strlen($base) + 1));
                if ($slug !== '' && !str_contains($slug, '.')) {
                    return $slug;
                }
            }
        }

        // Fallback: si hay 2+ niveles de dominio y no es un host conocido
        if (substr_count($host, '.') >= 2) {
            $parts = explode('.', $host);
            $first = $parts[0];
            if (!in_array($first, ['www', 'app', 'api'], true)) {
                return $first;
            }
        }

        return null;
    }
}
