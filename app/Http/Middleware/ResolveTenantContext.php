<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve contexto de tenant MSP desde subdominio o cabecera (preparación SaaS).
 *
 * Ejemplo: acme.tikara.test → request attributes['tenant_subdomain'] = acme
 * Cabecera opcional: X-Tenant-Subdomain
 */
class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (! $subdomain && $request->getHost()) {
            $host = strtolower($request->getHost());
            $base = config('tenancy.base_domain');
            if ($base && str_ends_with($host, '.'.strtolower($base))) {
                $subdomain = substr($host, 0, -strlen('.'.strtolower($base)));
            } elseif (substr_count($host, '.') >= 2) {
                $parts = explode('.', $host);
                $first = $parts[0];
                if (! in_array($first, ['www', 'app', 'api'], true)) {
                    $subdomain = $first;
                }
            }
        }

        $subdomain = is_string($subdomain) ? strtolower(trim($subdomain)) : null;

        $reserved = config('tenancy.reserved_subdomains', []);
        if ($subdomain && $subdomain !== '' && ! in_array($subdomain, $reserved, true)) {
            $request->attributes->set('tenant_subdomain', $subdomain);
        }

        return $next($request);
    }
}
