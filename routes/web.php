<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
// LOGIN VISUAL (SPA)
// ==========================
Route::get('/login', fn () => Inertia::render('Auth/Login'))->middleware('guest')->name('login');
Route::get('/forgot-password', fn () => Inertia::render('Auth/ForgotPassword'))->middleware('guest')->name('password.request');
Route::get('/reset-password', fn () => Inertia::render('Auth/ResetPassword'))->middleware('guest')->name('password.reset');
Route::get('/verify-email', fn () => Inertia::render('Auth/VerifyEmail'))->middleware('guest')->name('verification.verify');
Route::get('/force-change-password', fn () => Inertia::render('Auth/ForceChangePassword'))->middleware('auth')->name('password.force-change');

// ==========================
// SPA (React)
// ==========================
// SIEMPRE AL FINAL
Route::get('/{any}', fn () => response()->view('app')->withHeaders($spaHeaders))->where('any', '^(?!api).*');
