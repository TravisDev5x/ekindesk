<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\PgsqlRowLevelSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Establece variables de sesión PostgreSQL para RLS en cada petición autenticada.
 */
class ApplyPgsqlTenantRls
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PgsqlRowLevelSecurity::enabled()) {
            return $next($request);
        }

        PgsqlRowLevelSecurity::applyForUser($request->user());

        try {
            return $next($request);
        } finally {
            PgsqlRowLevelSecurity::clear();
        }
    }
}
