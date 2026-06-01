<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\UserInvitation;
use App\Mail\VerifyEmail;
use App\Services\OnboardingRedirectService;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => ['required'],
            'password'   => ['required'],
        ]);

        $input = trim($request->input('identifier'));

        // Detectar si es email o numero de empleado
        $fieldType = filter_var($input, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'employee_number';

        // Buscar usuario (mismo mensaje para no filtrar existencia)
        $user = User::where($fieldType, $input)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::channel('single')->warning('Login fallido', [
                'identifier_type' => $fieldType,
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'errors' => ['root' => 'Credenciales inválidas']
            ], 422);
        }

        if ($user->is_blacklisted) {
            return response()->json([
                'errors' => ['root' => 'Tu cuenta está vetada. Contacta al administrador']
            ], 403);
        }

        // Solo pending_email y blocked no pueden entrar. pending_admin puede entrar y ver app con mensaje de espera.
        if (in_array($user->status, ['pending_email', 'blocked'], true)) {
            $message = match ($user->status) {
                'pending_email' => 'Verifica tu correo para activar la cuenta',
                'blocked' => 'Tu cuenta está bloqueada',
                default => 'Tu cuenta no está activa',
            };
            return response()->json([
                'errors' => ['root' => $message]
            ], 403);
        }

        if ($user->status === 'active' && $user->email && is_null($user->email_verified_at)) {
            return response()->json([
                'errors' => ['root' => 'Verifica tu correo para activar la cuenta']
            ], 403);
        }

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        // Actualizar información de última conexión (compatible con monitor de sesiones)
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $authUser = Auth::user()->load([
            'roles:id,name,guard_name',
            'sede:id,name,client_id',
            'sede.cliente:id,name',
            'operatorProfile:id,user_id',
        ]);
        $authPayload = $authUser->toArray();
        $authPayload['client_id'] = $authUser->sede?->client_id;
        $authPayload['client_name'] = $authUser->sede?->cliente?->name;
        $permissions = $authUser->getAllPermissions()->pluck('name')->values();

        $onboarding = app(OnboardingRedirectService::class);

        return response()->json([
            'user' => $authPayload,
            'roles' => $authUser->roles->pluck('name'),
            'permissions' => $permissions,
            'onboarding_redirect' => $onboarding->redirectPath($authUser),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'paternal_last_name' => ['required', 'string', 'max:255'],
            'maternal_last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'digits:10'],
            'sede_id' => ['nullable', 'exists:sites,id'],
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
        ]);

        $sedeId = $validated['sede_id'] ?? \App\Models\Sede::where('code', 'REMOTO')->value('id');

        if ($request->filled('plan')) {
            session(['invited_plan_slug' => $request->input('plan')]);
        }

        $user = User::create([
            'employee_number' => null,
            'first_name' => $validated['first_name'],
            'paternal_last_name' => $validated['paternal_last_name'],
            'maternal_last_name' => $validated['maternal_last_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => 'pending_email',
            'sede_id' => $sedeId,
            'client_id' => null,
            'is_operator' => false,
            'onboarding_completed' => false,
        ]);

        $token = Str::uuid()->toString();
        DB::table('email_verification_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mailSent = false;
        $url = url("/verify-email?token={$token}");
        try {
            Mail::to($user->email)->send(new VerifyEmail($url));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::channel('single')->warning('Envío de correo de verificación fallido', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => $mailSent
                ? 'Registro creado. Revisa tu correo para activar tu cuenta.'
                : 'Registro creado. No se pudo enviar el correo de verificacion. Contacta al administrador.',
            'redirect_url' => url('/verify-email'),
            'onboarding_after_verify' => true,
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['message' => 'Token invalido'], 400);
        }

        $record = DB::table('email_verification_tokens')
            ->where('token', $token)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Token invalido'], 400);
        }

        if (now()->gt($record->expires_at)) {
            DB::table('email_verification_tokens')->where('token', $token)->delete();
            return response()->json(['message' => 'Token expirado'], 400);
        }

        $user = User::find($record->user_id);
        if (!$user) {
            DB::table('email_verification_tokens')->where('token', $token)->delete();
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        DB::transaction(function () use ($user, $token) {
            $user->email_verified_at = now();
            $user->status = 'active';

            $isInvited = UserInvitation::query()
                ->where('email', $user->email)
                ->where('status', 'accepted')
                ->exists();

            if (! $isInvited && $user->roles()->count() === 0) {
                $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
                if ($adminRole) {
                    $user->syncRoles([$adminRole]);
                }
            }

            $user->save();

            DB::table('email_verification_tokens')->where('token', $token)->delete();
        });

        $user->refresh()->load([
            'roles:id,name,guard_name',
            'operatorProfile:id,user_id',
        ]);

        $onboarding = app(OnboardingRedirectService::class);
        $redirect = $onboarding->redirectPath($user) ?? '/home';

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $authPayload = $user->toArray();
        $authPayload['client_id'] = $user->sede?->client_id;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Correo verificado correctamente.',
                'onboarding_redirect' => $redirect,
                'user' => $authPayload,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ]);
        }

        return redirect($redirect);
    }

    public function logout(Request $request)
    {
        // Cierre de sesión explícito con el guard de sesión (web)
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Sesion cerrada'
        ]);
    }

    /**
     * Ping ligero para heartbeat: actualiza last_activity de la sesión sin devolver datos.
     * Mejora la precisión del monitor de sesiones cuando el usuario tiene la app abierta.
     */
    public function ping()
    {
        return response()->noContent();
    }
}
