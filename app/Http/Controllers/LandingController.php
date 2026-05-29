<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\OnboardingRedirectService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        if ($user) {
            $onboarding = app(OnboardingRedirectService::class);
            if ($redirect = $onboarding->redirectPath($user)) {
                return redirect($redirect);
            }

            return redirect('/resolbeb');
        }

        return Inertia::render('Landing/Index', [
            'plans' => Plan::activePublic()->get([
                'id',
                'name',
                'slug',
                'type',
                'description',
                'price_monthly',
                'price_yearly',
                'max_clients',
                'max_users',
                'max_agents',
                'features',
                'highlighted',
                'trial_days',
            ]),
        ]);
    }
}
