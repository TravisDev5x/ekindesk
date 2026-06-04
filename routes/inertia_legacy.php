<?php

/**
 * Redirects de URLs del SPA legacy (resources/js/Pages + app.jsx) hacia rutas Inertia actuales.
 *
 * Mantener al cerrar la épica Inertia; ver docs/INERTIA_MIGRATION.md.
 */

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Dashboard / inicio
    Route::redirect('/app', '/home');
    Route::redirect('/inicio', '/home');

    // Módulo tickets (antes /tickets y namespace resolvev1)
    Route::redirect('/resolvev1', '/resolbeb');
    Route::redirect('/resolvev1/dashboard', '/resolbeb');
    Route::redirect('/resolvev1/tickets', '/resolbeb/tickets');
    Route::redirect('/resolvev1/tickets/new', '/resolbeb/tickets/new');
    Route::redirect('/resolvev1/mis-tickets', '/resolbeb/mis-tickets');
    Route::redirect('/resolvev1/estados', '/resolbeb/estados');
    Route::redirect('/resolvev1/tipos', '/resolbeb/tipos');
    Route::get('/resolvev1/tickets/{id}', fn (string $id) => redirect("/resolbeb/tickets/{$id}"))
        ->where('id', '[0-9]+');

    Route::redirect('/resolbeb/resolvev1', '/resolbeb');
    Route::redirect('/resolbeb/resolvev1/dashboard', '/resolbeb');
    Route::redirect('/resolbeb/resolvev1/tickets', '/resolbeb/tickets');
    Route::redirect('/resolbeb/resolvev1/tickets/new', '/resolbeb/tickets/new');
    Route::redirect('/resolbeb/resolvev1/mis-tickets', '/resolbeb/mis-tickets');
    Route::redirect('/resolbeb/resolvev1/estados', '/resolbeb/estados');
    Route::redirect('/resolbeb/resolvev1/tipos', '/resolbeb/tipos');
    Route::get('/resolbeb/resolvev1/tickets/{id}', fn (string $id) => redirect("/resolbeb/tickets/{$id}"))
        ->where('id', '[0-9]+');

    // Catálogos tickets (nombres alternativos)
    Route::redirect('/ticket-estados', '/resolbeb/estados');
    Route::redirect('/ticket-tipos', '/resolbeb/tipos');

    // Incidencias / auditoría
    Route::redirect('/incidentes', '/incidents');
    Route::get('/incidentes/{id}', fn (string $id) => redirect("/incidents/{$id}"))
        ->where('id', '[0-9]+');
    Route::redirect('/audit-command-center', '/audit-command');
    Route::redirect('/audit', '/audit-command');

    // Usuarios / ajustes (alias)
    Route::redirect('/configuracion', '/settings');
    Route::redirect('/usuarios', '/users');
    Route::redirect('/invitaciones', '/users/invitations');

    // Catálogos (alias español)
    Route::redirect('/prioridades', '/priorities');
});

// Invitación: rutas antiguas públicas (query string se conserva en el navegador)
Route::get('/invitation/accept', fn () => redirect()->route('invitation.accept', request()->query()));
Route::get('/invitations/accept', fn () => redirect()->route('invitation.accept', request()->query()));
