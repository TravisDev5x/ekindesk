<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Sede;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptInvitationController extends Controller
{
    public function show(Request $request): Response
    {
        $token = $request->query('token');

        if (! is_string($token) || trim($token) === '') {
            return $this->errorPage();
        }

        $invitation = UserInvitation::with(['role', 'client'])
            ->where('token', $token)
            ->where('status', UserInvitation::STATUS_PENDING)
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            if ($invitation && $invitation->isExpired()) {
                $invitation->update(['status' => UserInvitation::STATUS_EXPIRED]);
            }

            return $this->errorPage();
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $invitation->token,
            'email' => $invitation->email,
            'role_name' => $invitation->role?->name ?? 'Usuario',
            'client_name' => $invitation->client?->name,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'error' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|exists:user_invitations,token',
            'first_name' => 'required|string|max:255',
            'paternal_last_name' => 'required|string|max:255',
            'maternal_last_name' => 'nullable|string|max:255',
            'password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);

        $user = DB::transaction(function () use ($validated) {
                $invitation = UserInvitation::with('role')
                    ->where('token', $validated['token'])
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $invitation
                    || $invitation->status !== UserInvitation::STATUS_PENDING
                    || $invitation->isExpired()
                ) {
                    throw ValidationException::withMessages([
                        'token' => ['Esta invitación no es válida o ha expirado.'],
                    ]);
                }

                if (User::where('email', $invitation->email)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => ['Ya existe una cuenta con este correo.'],
                    ]);
                }

                $role = $this->resolveRoleForGuard((int) $invitation->role_id);
                if (! $role) {
                    throw ValidationException::withMessages([
                        'token' => ['El rol asignado ya no es válido.'],
                    ]);
                }

                $sedeId = $this->resolveSedeIdForInvitation($invitation);
                $isOperator = $invitation->client_id === null;

                $user = User::create([
                    'first_name' => $validated['first_name'],
                    'paternal_last_name' => $validated['paternal_last_name'],
                    'maternal_last_name' => $validated['maternal_last_name'] ?? null,
                    'email' => $invitation->email,
                    'password' => Hash::make($validated['password']),
                    'status' => 'active',
                    'client_id' => $invitation->client_id,
                    'is_operator' => $isOperator,
                    'onboarding_completed' => ! $isOperator,
                    'sede_id' => $sedeId,
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();

                $user->syncRoles([$role]);
                User::forgetPermissionCache($user);
                $invitation->markAccepted();

                return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->is_operator && ! $user->onboarding_completed) {
            return redirect()->intended('/');
        }

        return redirect()->intended('/');
    }

    protected function errorPage(): Response
    {
        return Inertia::render('Auth/AcceptInvitation', [
            'token' => null,
            'email' => null,
            'role_name' => null,
            'client_name' => null,
            'expires_at' => null,
            'error' => 'Esta invitación no es válida o ha expirado',
        ]);
    }

    protected function resolveSedeIdForInvitation(UserInvitation $invitation): int
    {
        if ($invitation->client_id) {
            $sedeId = Sede::where('client_id', $invitation->client_id)->value('id');
            if ($sedeId) {
                return (int) $sedeId;
            }
        }

        $remotoId = Sede::where('code', 'REMOTO')->value('id');

        return (int) ($remotoId ?? Sede::query()->value('id'));
    }

    protected function resolveRoleForGuard(int $roleId): ?Role
    {
        $role = Role::find($roleId);
        if (! $role) {
            return null;
        }

        if ($role->guard_name === 'web') {
            return $role;
        }

        return Role::where('name', $role->name)->where('guard_name', 'web')->first() ?? $role;
    }
}
