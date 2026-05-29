<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function show(): Response|RedirectResponse
    {
        $user = auth()->user();
        $profile = $user->operatorProfile;

        if (! $profile) {
            return redirect()->route('onboarding.show')
                ->with('info', 'Completa tu perfil de empresa primero');
        }

        $profile->load('plan');

        return Inertia::render('Company/Show', [
            'profile' => $profile,
            'plan' => $profile->plan,
            'plans' => Plan::activePublic()->get([
                'id',
                'name',
                'slug',
                'type',
                'price_monthly',
                'trial_days',
                'highlighted',
            ]),
            'can' => [
                'edit' => $user->can('company.edit'),
            ],
        ]);
    }

    public function edit(): Response|RedirectResponse
    {
        abort_unless(auth()->user()->can('company.edit'), 403);

        $profile = auth()->user()->operatorProfile;

        if (! $profile) {
            return redirect()->route('onboarding.show')
                ->with('info', 'Completa tu perfil de empresa primero');
        }

        return Inertia::render('Company/Edit', [
            'profile' => $profile,
            'plans' => Plan::activePublic()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('company.edit'), 403);

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'phone' => 'nullable|string|size:10|regex:/^\d{10}$/',
            'website' => 'nullable|url|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'logo' => 'nullable|image|max:2048',
        ]);

        $profile = auth()->user()->operatorProfile;

        if (! $profile) {
            return redirect()->route('onboarding.show')
                ->with('info', 'Completa tu perfil de empresa primero');
        }

        $data = collect($validated)->only([
            'business_name',
            'rfc',
            'phone',
            'website',
            'address',
            'city',
            'country',
        ])->all();

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store(
                'operator-logos/'.auth()->id(),
                'public'
            );
        }

        $profile->update($data);

        return redirect()->route('company.show')
            ->with('success', 'Perfil de empresa actualizado');
    }

    public function destroyLogo(): RedirectResponse
    {
        abort_unless(auth()->user()->can('company.edit'), 403);

        $profile = auth()->user()->operatorProfile;

        if ($profile?->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo eliminado');
    }
}
