<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve contexto de tenant MSP desde subdominio o cabecera (preparación SaaS).
 *
 * Ejemplo: acme.ekindesk.test → request attributes['tenant_subdomain'] = acme
 * Cabecera opcional: X-Tenant-Subdomain
 */
class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $request->header('X-Tenant-Subdomain');

        if (! $subdomain && $request->getHost()) {
            $host = $request->getHost();
            $base = config('tenancy.base_domain');
            if ($base && str_ends_with($host, '.'.$base)) {
                $subdomain = substr($host, 0, -strlen('.'.$base));
            } elseif (substr_count($host, '.') >= 2) {
                $parts = explode('.', $host);
                $subdomain = $parts[0] !== 'www' ? $parts[0] : null;
            }
        }

        if (is_string($subdomain) && $subdomain !== '') {
            $request->attributes->set('tenant_subdomain', strtolower($subdomain));
        }

        return $next($request);
    }
}
