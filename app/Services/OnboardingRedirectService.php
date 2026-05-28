<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserInvitation;

class OnboardingRedirectService
{
    public function wasInvited(User $user): bool
    {
        if (! $user->email) {
            return false;
        }

        return UserInvitation::query()
            ->where('email', $user->email)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Ruta de onboarding requerida, o null si puede continuar al dashboard.
     */
    public function redirectPath(User $user): ?string
    {
        if ($this->wasInvited($user)) {
            return null;
        }

        if ($user->onboarding_completed) {
            return null;
        }

        if ($user->client_id !== null) {
            return null;
        }

        if (! $user->is_operator) {
            return '/onboarding';
        }

        if ($user->relationLoaded('operatorProfile') ? $user->operatorProfile : $user->operatorProfile()->exists()) {
            return '/onboarding/clients';
        }

        return '/onboarding';
    }

    public function shouldBypassRoute(string $path): bool
    {
        $path = trim($path, '/');

        $exact = [
            'onboarding',
            'onboarding/clients',
            'logout',
            'login',
            'register',
            'register/accept',
            'verify-email',
            'landing',
            '',
            'manual',
            'check-auth',
        ];

        if (in_array($path, $exact, true)) {
            return true;
        }

        if (str_starts_with($path, 'api')) {
            return true;
        }

        if (str_starts_with($path, 'onboarding/')) {
            return true;
        }

        return false;
    }
}
