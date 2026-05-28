<?php

namespace App\Http\Middleware;

use App\Services\OnboardingRedirectService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function __construct(
        protected OnboardingRedirectService $onboarding
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if ($this->onboarding->shouldBypassRoute($request->path())) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('check-auth')) {
            return $next($request);
        }

        $user = Auth::user();
        $redirect = $this->onboarding->redirectPath($user);

        if ($redirect && ! $request->is(ltrim($redirect, '/'))) {
            return redirect($redirect);
        }

        return $next($request);
    }
}
