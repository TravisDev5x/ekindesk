<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\OperatorProfile;
use App\Models\Plan;
use App\Models\Sede;
use App\Services\OnboardingRedirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OperatorOnboardingController extends Controller
{
    public function __construct(
        protected OnboardingRedirectService $onboarding
    ) {}

    public function show(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ($user->onboarding_completed) {
            return redirect('/');
        }

        if ($this->onboarding->wasInvited($user)) {
            return redirect('/');
        }

        $slug = session('invited_plan_slug');
        $selectedPlan = $slug ? Plan::activePublic()->where('slug', $slug)->first() : null;

        if (! $selectedPlan && session('invited_plan_id')) {
            $selectedPlan = Plan::activePublic()->find(session('invited_plan_id'));
        }

        return Inertia::render('Onboarding/OperatorProfile', [
            'plans' => Plan::activePublic()->get(),
            'selectedPlan' => $selectedPlan,
            'step' => 1,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($this->onboarding->wasInvited($user)) {
            return redirect('/');
        }

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'phone' => 'nullable|string|size:10|regex:/^\d{10}$/',
            'website' => 'nullable|url|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'plan_id' => 'nullable|exists:plans,id',
            'logo' => 'nullable|image|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store(
                'operator-logos/'.$user->id,
                'public'
            );
        }

        $plan = ! empty($validated['plan_id']) ? Plan::find($validated['plan_id']) : null;
        $trialEndsAt = null;
        if ($plan && $plan->trial_days > 0) {
            $trialEndsAt = now()->addDays($plan->trial_days);
        }

        OperatorProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $validated['business_name'],
                'rfc' => $validated['rfc'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'country' => $validated['country'] ?? 'MX',
                'logo_path' => $logoPath,
                'plan_id' => $plan?->id,
                'trial_ends_at' => $trialEndsAt,
                'is_active' => true,
            ]
        );

        $user->update([
            'is_operator' => true,
            'onboarding_completed' => false,
            'client_id' => null,
        ]);

        session()->forget(['invited_plan_slug', 'invited_plan_id']);

        if ($plan && $plan->type === 'inhouse') {
            $user->update(['onboarding_completed' => true]);

            return redirect('/')
                ->with('success', '¡Bienvenido a EkinDesk! Tu panel está listo.');
        }

        return redirect()->route('onboarding.clients');
    }

    public function showClients(): Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (! $user->operatorProfile) {
            return redirect()->route('onboarding.show');
        }

        if ($user->onboarding_completed) {
            return redirect('/');
        }

        if ($this->onboarding->wasInvited($user)) {
            return redirect('/');
        }

        $user->load('operatorProfile.plan');
        $plan = $user->operatorProfile?->plan;

        if ($plan && $plan->type === 'inhouse') {
            $user->update(['onboarding_completed' => true]);

            return redirect('/');
        }

        return Inertia::render('Onboarding/ClientsStep', [
            'step' => 2,
            'operator_name' => $user->operatorProfile->business_name,
            'existing_clients' => Cliente::forOperator($user->id)->count(),
        ]);
    }

    public function storeClient(Request $request)
    {
        $user = Auth::user();

        if ($this->onboarding->wasInvited($user)) {
            return redirect('/');
        }

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'phone' => 'nullable|string|size:10|regex:/^\d{10}$/',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'site_name' => 'nullable|string|max:255',
            'site_address' => 'nullable|string|max:500',
            'site_city' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $cliente = Cliente::create([
                'name' => $validated['business_name'],
                'industry' => $validated['industry'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['phone'] ?? null,
                'operator_user_id' => $user->id,
                'is_active' => true,
            ]);

            if (! empty($validated['site_name'])) {
                Sede::create([
                    'client_id' => $cliente->id,
                    'name' => $validated['site_name'],
                    'address' => $validated['site_address'] ?? null,
                    'city' => $validated['site_city'] ?? null,
                    'type' => 'physical',
                    'is_active' => true,
                ]);
            }

            $user->update(['onboarding_completed' => true]);
        });

        return redirect('/')->with('success', 'Cliente agregado. ¡Bienvenido a EkinDesk!');
    }

    public function skipClients()
    {
        $user = Auth::user();

        if ($this->onboarding->wasInvited($user)) {
            return redirect('/');
        }

        $user->update(['onboarding_completed' => true]);

        return redirect('/')->with('success', 'Puedes agregar clientes desde el panel en cualquier momento.');
    }
}
