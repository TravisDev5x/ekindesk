<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Ticket;
use App\Models\Incident;
use App\Policies\RequesterTicketPolicy;
use App\Policies\TicketPolicy;
use App\Policies\IncidentPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
        RateLimiter::for('tickets', function (Request $request) {
            $key = $request->user() ? 'tickets.user.' . $request->user()->id : 'tickets.ip.' . $request->ip();
            // Dashboard hace varias llamadas (tickets, summary, analytics, filtros); 300/min evita 429 en uso normal
            return Limit::perMinute(300)->by($key)->response(function () {
                return response()->json(['message' => 'Demasiadas peticiones. Espera un momento.'], 429);
            });
        });

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Incident::class, IncidentPolicy::class);

        // Gates para contexto "Mis Tickets" (solicitante). No reemplazan TicketPolicy en rutas operativas.
        Gate::define('requester.viewAny.ticket', fn ($user) => app(RequesterTicketPolicy::class)->viewAny($user));
        Gate::define('requester.view.ticket', [RequesterTicketPolicy::class, 'view']);
        Gate::define('requester.create.ticket', fn ($user) => app(RequesterTicketPolicy::class)->create($user));
        Gate::define('requester.alert.ticket', [RequesterTicketPolicy::class, 'alert']);
        Gate::define('requester.comment.ticket', [RequesterTicketPolicy::class, 'comment']);
        Gate::define('requester.attach.ticket', [RequesterTicketPolicy::class, 'attach']);
        Gate::define('requester.cancel.ticket', [RequesterTicketPolicy::class, 'cancel']);

        Event::listen(\App\Events\TicketCreated::class, \App\Listeners\SendTicketNotification::class);
        Event::listen(\App\Events\TicketUpdated::class, \App\Listeners\SendTicketNotification::class);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            $email = urlencode((string) $user->email);
            return URL::to('/') . "/reset-password?token={$token}&email={$email}";
        });
    }
}
