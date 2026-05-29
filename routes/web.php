<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Onboarding\OperatorOnboardingController;
use App\Http\Controllers\Web\ClienteController;
use App\Models\Plan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| AQUÍ SOLO VIVE LÓGICA DE FRONTEND AUTENTICADO POR COOKIES (SESIÓN).
| - Verificación de sesión: /check-auth (guard web, middleware auth).
| - Vistas SPA y login visual.
| NO mezclar con API: la API no valida sesiones; solo tokens.
*/

// ==========================
// VERIFICACIÓN DE AUTENTICACIÓN (SESIÓN)
// ==========================
// - Solo en web. Usa exclusivamente middleware('auth') y guard web. NO auth:api.
// - El frontend debe llamar /check-auth DESPUÉS del login o desde layout autenticado.
// - Llamar /check-auth desde /login dará 401 (correcto; no es bug).
// - Requests AJAX sin sesión reciben 401 JSON { authenticated: false } (nunca redirect HTML).
Route::get('/check-auth', App\Http\Controllers\Web\CheckAuthController::class)
    ->middleware('auth')
    ->name('check-auth');

// ==========================
// DIAGNÓSTICO (opcional)
// ==========================
Route::get('/test-disco', function () {
    Storage::disk('public')->put('prueba.txt', 'OK');
    return 'OK';
});

// Cabeceras para la SPA: no almacenar en caché (evita versión antigua en Brave/Chromium)
$spaHeaders = [
    'Cache-Control' => 'no-store, no-cache, must-revalidate',
    'Pragma' => 'no-cache',
    'Expires' => '0',
];

// ==========================
// AUTH (Inertia) — antes del catch-all SPA
// ==========================
Route::get('/login', fn () => Inertia::render('Auth/Login'))->middleware('guest')->name('login');
Route::get('/register', function () {
    return Inertia::render('Auth/Register', [
        'plans' => Plan::activePublic()->get([
            'id', 'name', 'slug', 'type', 'price_monthly', 'trial_days', 'highlighted',
        ]),
    ]);
})->middleware('guest')->name('register');
Route::get('/register/accept', [AcceptInvitationController::class, 'show'])
    ->middleware('guest')
    ->name('invitation.accept');
Route::post('/register/accept', [AcceptInvitationController::class, 'store'])
    ->middleware('guest')
    ->name('invitation.accept.store');
Route::get('/forgot-password', fn () => Inertia::render('Auth/ForgotPassword'))->middleware('guest')->name('password.request');
Route::get('/reset-password', fn () => Inertia::render('Auth/ResetPassword'))->middleware('guest')->name('password.reset');
Route::get('/verify-email', fn () => Inertia::render('Auth/VerifyEmail'))->middleware('guest')->name('verification.verify');
Route::get('/force-change-password', fn () => Inertia::render('Auth/ForceChangePassword'))->middleware('auth')->name('password.force-change');

// Landing pública (antes del catch-all SPA)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Onboarding operador (auth sin middleware onboarding — evita bucle)
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OperatorOnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OperatorOnboardingController::class, 'store'])->name('onboarding.store');
    Route::get('/onboarding/clients', [OperatorOnboardingController::class, 'showClients'])
        ->name('onboarding.clients');
    Route::post('/onboarding/clients', [OperatorOnboardingController::class, 'storeClient'])
        ->name('onboarding.clients.store');
    Route::post('/onboarding/skip', [OperatorOnboardingController::class, 'skipClients'])
        ->name('onboarding.skip');

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->name('index');
        Route::get('/create', [ClienteController::class, 'create'])->name('create');
        Route::post('/', [ClienteController::class, 'store'])->name('store');
        Route::get('/{client}', [ClienteController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClienteController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClienteController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClienteController::class, 'destroy'])->name('destroy');
    });
});

// ==========================
// SPA (React)
// ==========================
// SIEMPRE AL FINAL
Route::get('/{any}', fn () => response()->view('app')->withHeaders($spaHeaders))->where('any', '^(?!api).*');
