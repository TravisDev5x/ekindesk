<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Inertia\ResolbebIndexController;
use App\Http\Controllers\Inertia\UserController as InertiaUserController;
use App\Http\Controllers\Onboarding\OperatorOnboardingController;
use App\Http\Controllers\Web\ClienteController;
use App\Models\Area;
use App\Models\Campaign;
use App\Models\Cliente;
use App\Models\ImpactLevel;
use App\Models\Position;
use App\Models\Priority;
use App\Models\PriorityMatrix;
use App\Models\Role;
use App\Models\Sede;
use App\Models\TicketMacro;
use App\Models\TicketState;
use App\Models\TicketType;
use App\Models\Ubicacion;
use App\Models\UrgencyLevel;
use App\Models\User;
use App\Models\Permission;
use App\Models\Plan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| AQUÍ SOLO VIVE LÓGICA DE FRONTEND AUTENTICADO POR COOKIES (SESIÓN).
| - Verificación de sesión: /check-auth (guard web, middleware auth).
| - Vistas Inertia (login, app autenticada) y sesión por cookies.
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

// ==========================
// AUTH (Inertia) — rutas públicas
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

Route::get('/manual', fn () => Inertia::render('Manual'))->name('manual');

// Landing pública
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

    Route::redirect('/clientes', '/clients');

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->name('index');
        Route::get('/create', [ClienteController::class, 'create'])->name('create');
        Route::post('/', [ClienteController::class, 'store'])->name('store');
        Route::get('/{client}', [ClienteController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClienteController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClienteController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClienteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('company')->name('company.')->group(function () {
        Route::get('/', [CompanyController::class, 'show'])->name('show');
        Route::get('/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/', [CompanyController::class, 'update'])->name('update');
        Route::delete('/logo', [CompanyController::class, 'destroyLogo'])->name('logo.destroy');
    });

    Route::get('/areas', fn () => Inertia::render('Catalogs/Areas', [
        'areas' => Area::orderBy('name')->get(['id', 'name', 'is_active', 'created_at']),
    ]))->name('areas.index');

    Route::get('/priorities', fn () => Inertia::render('Catalogs/Prioridades', [
        'priorities' => Priority::orderBy('level')->orderBy('name')->get(),
    ]))->name('priorities.index');

    Route::get('/impact-levels', fn () => Inertia::render('Catalogs/ImpactLevels', [
        'impactLevels' => ImpactLevel::orderBy('weight')->orderBy('name')->get(),
    ]))->name('impact-levels.index');

    Route::get('/urgency-levels', fn () => Inertia::render('Catalogs/UrgencyLevels', [
        'urgencyLevels' => UrgencyLevel::orderBy('weight')->orderBy('name')->get(),
    ]))->name('urgency-levels.index');

    Route::get('/campaigns', fn () => Inertia::render('Catalogs/Campaigns', [
        'campaigns' => Campaign::orderBy('name')->get(['id', 'name', 'is_active', 'created_at']),
    ]))->name('campaigns.index');

    Route::get('/positions', fn () => Inertia::render('Catalogs/Positions', [
        'positions' => Position::orderBy('name')->get(['id', 'name', 'is_active', 'created_at']),
    ]))->name('positions.index');

    Route::get('/roles', fn () => Inertia::render('Catalogs/Roles', [
        'roles' => Role::orderBy('guard_name')->orderBy('name')->get(['id', 'name', 'slug', 'guard_name', 'created_at']),
    ]))->name('roles.index');

    Route::get('/sessions', fn () => Inertia::render('Catalogs/Sessions'))->name('sessions.index');

    Route::get('/home', fn () => Inertia::render('Home/Dashboard'))
        ->middleware('onboarding')
        ->name('home');

    Route::redirect('/dashboard', '/home');

    Route::get('/resolbeb', fn () => Inertia::render('Resolbeb/Dashboard', [
        'catalogs' => [
            'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'area_users' => User::where('status', 'active')
                ->whereNotNull('area_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ],
    ]))
        ->middleware('onboarding')
        ->name('resolbeb.dashboard');

    Route::get('/tickets/wallboard', fn () => Inertia::render('Resolbeb/Wallboard', [
        'catalogs' => [
            'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'area_users' => User::where('status', 'active')
                ->whereNotNull('area_id')
                ->orderBy('name')
                ->get(['id', 'name']),
        ],
    ]))->name('resolbeb.wallboard');

    Route::get('/resolbeb/estados', fn () => Inertia::render('Catalogs/TicketStates', [
        'ticketStates' => TicketState::orderBy('is_final')->orderBy('name')->get(),
    ]))->name('resolbeb.estados');

    Route::get('/resolbeb/tipos', fn () => Inertia::render('Catalogs/TicketTypes', [
        'ticketTypes' => TicketType::with('areas:id,name')->orderBy('name')->get(),
        'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
    ]))->name('resolbeb.tipos');

    Route::get('/resolbeb/tickets/new', fn () => Inertia::render('Resolbeb/Create', [
        'catalogs' => [
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'ticket_types' => TicketType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::where('is_active', true)->orderBy('level')->orderBy('name')->get(['id', 'name', 'level']),
            'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'impact_levels' => ImpactLevel::where('is_active', true)->orderBy('weight')->orderBy('name')->get(['id', 'name']),
            'urgency_levels' => UrgencyLevel::where('is_active', true)->orderBy('weight')->orderBy('name')->get(['id', 'name']),
            'ticket_states' => TicketState::orderBy('name')->get(['id', 'name', 'code', 'is_final']),
            'priority_matrix' => PriorityMatrix::all(['impact_level_id', 'urgency_level_id', 'priority_id']),
        ],
    ]))
        ->middleware('onboarding')
        ->name('resolbeb.create');

    Route::get('/resolbeb/tickets', [ResolbebIndexController::class, 'index'])
        ->middleware('onboarding')
        ->name('resolbeb.tickets');

    Route::get('/resolbeb/mis-tickets', [ResolbebIndexController::class, 'misTickets'])
        ->middleware('onboarding')
        ->name('resolbeb.mis-tickets');

    Route::get('/resolbeb/tickets/{id}', function ($id) {
        return Inertia::render('Resolbeb/Detalle', [
            'ticketId' => (int) $id,
            'catalogs' => [
                'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'ticket_states' => TicketState::orderBy('name')->get(['id', 'name', 'code', 'is_final']),
                'priorities' => Priority::where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
                'ticket_types' => TicketType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    })->where('id', '[0-9]+')
        ->middleware(['auth', 'onboarding'])
        ->name('resolbeb.detalle');

    Route::get('/sedes', fn () => Inertia::render('Catalogs/Sedes', [
        'sedes' => Sede::with('cliente:id,name')->orderBy('type')->orderBy('name')->get(),
        'clientes' => Cliente::where('is_active', true)->orderBy('name')->get(['id', 'name']),
    ]))->name('sedes.index');

    Route::get('/ubicaciones', fn () => Inertia::render('Catalogs/Ubicaciones', [
        'ubicaciones' => Ubicacion::with('sede:id,name,type')->orderBy('sede_id')->orderBy('name')->get(),
        'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
    ]))->name('ubicaciones.index');

    Route::get('/ticket-macros', fn () => Inertia::render('Catalogs/TicketMacros', [
        'ticketMacros' => TicketMacro::orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'is_active', 'created_at']),
    ]))->name('ticket-macros.index');

    Route::get('/priority-matrix', fn () => Inertia::render('Catalogs/PriorityMatrix', [
        'matrix' => PriorityMatrix::all(['impact_level_id', 'urgency_level_id', 'priority_id']),
        'impactLevels' => ImpactLevel::where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']),
        'urgencyLevels' => UrgencyLevel::where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']),
        'priorities' => Priority::where('is_active', true)->orderBy('level')->orderBy('name')->get(['id', 'name', 'level']),
    ]))->name('priority-matrix.index');

    Route::get('/permissions', fn () => Inertia::render('System/Permissions', [
        'roles' => Role::with('permissions')->orderBy('name')->get(),
        'permissions' => Permission::orderBy('name')->get(['id', 'name', 'guard_name']),
    ]))->name('permissions.index');

    Route::get('/audit-command', fn () => Inertia::render('System/AuditCommandCenter'))->name('audit.index');

    Route::get('/incident-types', fn () => Inertia::render('Incidents/Types', [
        'incidentTypes' => \App\Models\IncidentType::orderBy('name')->get(['id', 'name', 'code', 'is_active', 'created_at']),
    ]))->middleware('onboarding')->name('incident-types.index');

    Route::get('/incident-severities', fn () => Inertia::render('Incidents/Severities', [
        'incidentSeverities' => \App\Models\IncidentSeverity::orderBy('level')->orderBy('name')->get(['id', 'name', 'code', 'level', 'is_active', 'created_at']),
    ]))->middleware('onboarding')->name('incident-severities.index');

    Route::get('/incident-statuses', fn () => Inertia::render('Incidents/Statuses', [
        'incidentStatuses' => \App\Models\IncidentStatus::orderBy('name')->get(['id', 'name', 'code', 'is_final', 'is_active', 'created_at']),
    ]))->middleware('onboarding')->name('incident-statuses.index');

    Route::get('/incidents', function () {
        return Inertia::render('Incidents/Index', [
            'catalogs' => [
                'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'incident_types' => \App\Models\IncidentType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'incident_severities' => \App\Models\IncidentSeverity::where('is_active', true)->orderBy('level')->get(['id', 'name', 'level', 'code']),
                'incident_statuses' => \App\Models\IncidentStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'is_final']),
                'area_users' => User::where('status', 'active')->whereNotNull('area_id')->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    })->middleware('onboarding')->name('incidents.index');

    Route::get('/incidents/{id}', function ($id) {
        return Inertia::render('Incidents/Detalle', [
            'incidentId' => (int) $id,
            'catalogs' => [
                'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'sedes' => Sede::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'incident_types' => \App\Models\IncidentType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'incident_severities' => \App\Models\IncidentSeverity::where('is_active', true)->orderBy('level')->get(['id', 'name', 'level', 'code']),
                'incident_statuses' => \App\Models\IncidentStatus::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'is_final']),
                'area_users' => User::where('status', 'active')->whereNotNull('area_id')->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    })->where('id', '[0-9]+')->middleware('onboarding')->name('incidents.detalle');

    Route::get('/calendario', fn () => Inertia::render('Calendario'))->name('calendario.index');

    Route::get('/profile', fn () => Inertia::render('Profile', [
        'user' => auth()->user()->load('roles'),
    ]))->name('profile.index');

    Route::get('/users', [InertiaUserController::class, 'index'])
        ->middleware('onboarding')
        ->name('users.inertia.index');

    Route::get('/users/invitations', fn () => Inertia::render('Users/Invitations'))
        ->middleware('onboarding')
        ->name('users.invitations.index');

    Route::get('/settings', fn () => Inertia::render('Settings'))
        ->middleware('onboarding')
        ->name('settings.index');

    // URLs legacy → Resolbeb (redirects)
    Route::redirect('/tickets', '/resolbeb/tickets');
    Route::redirect('/mis-tickets', '/resolbeb/mis-tickets');
    Route::redirect('/tickets/new', '/resolbeb/tickets/new');
    Route::get('/tickets/{id}', fn (string $id) => redirect("/resolbeb/tickets/{$id}"))
        ->where('id', '[0-9]+');
    Route::redirect('/ticket-states', '/resolbeb/estados');
    Route::redirect('/ticket-types', '/resolbeb/tipos');
});
