# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**EkinDesk** is a multi-module CRM/Ticketing System for IT support teams. It uses a **Laravel 12 REST API backend** with a **React 19 + Inertia.js frontend**. Authentication is via Laravel Sanctum (stateful cookies).

---

## Common Commands

### Development

```bash
composer dev
# Starts concurrently: artisan serve + queue:listen + pail (logs) + vite dev
```

### Build

```bash
npm run build       # Production Vite build
composer optimize   # Cache config, views, events for production
```

### Database

```bash
php artisan migrate
php artisan migrate:fresh --seed   # Reset + seed demo data
```

### Testing

```bash
composer test       # Clears config cache, then runs PHPUnit
php artisan test --filter=TicketTest   # Single test class
```

### Linting / Code Style

```bash
./vendor/bin/pint   # Laravel Pint (PHP)
```

---

## Architecture

### High-Level Stack

- **Backend**: Laravel 12, PHP 8.2+, MySQL/SQLite, Sanctum (auth), Spatie Permission (RBAC)
- **Frontend**: React 19, Inertia.js, Vite 7, Tailwind CSS 4, Radix UI, Zod + React Hook Form, Recharts, TanStack Table
- **Pages**: `resources/js/Inertia/Pages/` (rutas en `web.php`); UI compartida en `resources/js/components/`. API en `app/Http/Controllers/Api/`.

### Request Flow

```
Inertia (Vite → inertia.jsx → páginas en Inertia/Pages/)
  → Datos: Axios (CSRF + cookie) → /api/*
  → Navegación: visitas Inertia (routes/web.php)
    → auth:sanctum + perm:* en API
```

**Session handshake**: Frontend calls `GET /check-auth` (a web route) to verify session on load. All `/api/*` routes use `auth:sanctum` and work via cookie, not Bearer tokens.

### Key Directories

| Path | Purpose |
|------|---------|
| `app/Http/Controllers/Api/` | Active REST controllers |
| `app/Http/Controllers/Sigua/` | SIGUA module controllers |
| `app/Models/` | Eloquent models (35+) |
| `app/Models/Sigua/` | SIGUA-namespace models |
| `app/Services/` | Business logic (scoping, onboarding, listing, requester ops) |
| `app/Policies/` | Gate-registered authorization policies |
| `app/Http/Requests/` | Form validation classes |
| `app/Http/Middleware/` | Custom middleware |
| `resources/js/Inertia/Pages/` | Inertia page components (rutas en `web.php`) |
| `resources/js/components/` | UI compartida, dashboards, formularios |
| `resources/js/components/ui/` | Radix-based reusable UI |
| `resources/js/context/` | Auth, Sidebar, I18n, Theme providers |
| `resources/js/lib/` | Axios config, catalog cache utility |
| `resources/js/services/` | API service wrappers |
| `routes/api.php` | All REST endpoints |
| `routes/web.php` | Inertia routes, `/check-auth`, landing, redirects legacy |
| `routes/sigua.php` | SIGUA module routes (auto-registered in bootstrap/app.php) |

---

## Authorization

**Middleware alias `perm`** = `EnsurePermissionOrAdmin`. Usage: `perm:tickets.manage_all|tickets.view_area`

- **Bypasses**: admin users, the first user in DB, or users without any role yet (onboarding)
- **Policies**: `TicketPolicy`, `IncidentPolicy`, `RequesterTicketPolicy` — registered in `AppServiceProvider`
- **Note**: `RequesterTicketPolicy` is NOT registered as a Gate policy; it's instantiated and called explicitly inside `MyTicketsController`

**Ticket scope levels**:
- `manage_all` — full visibility across all areas/clients
- `view_area` — own area + personally assigned tickets
- `view_own` — only self-created or self-assigned

---

## Core Modules

| Module | API prefix | Key controllers |
|--------|-----------|-----------------|
| Helpdesk / Tickets | `/api/tickets` | `TicketController`, `TicketAttachmentController`, `TicketAnalyticsController` |
| Mis Tickets (Requester) | `/api/my-tickets` | `MyTicketsController` |
| Incidents | `/api/incidents` | `IncidentController` |
| Users & RBAC | `/api/users`, `/api/roles`, `/api/permissions` | `UserController`, `RoleController`, `PermissionController` |
| Catalogs | `/api/catalogs` | `CatalogController` (monolithic read; separate CRUD routes per catalog) |
| TimeDesk (Attendance/HR) | `/api/timedesk` | `AttendanceController`, `ScheduleController`, `TimeDeskEmployeeController` |
| SIGUA | `/api/sigua` | Controllers in `App\Http\Controllers\Sigua\*` |
| Notifications | `/api/notifications`, `/api/admin/notifications` | `NotificationController`, `AdminNotificationController` |

---

## Patterns & Conventions

### Backend

- **Validation**: Always in Form Request classes (`app/Http/Requests/`), not inline in controllers
- **Audit logging**: `TicketObserver` auto-logs all model changes to `ticket_audit_logs`; incidents do **not** have observers yet
- **Events/Listeners**: `TicketCreated` / `TicketUpdated` → `SendTicketNotification` listener
- **Client scoping (multi-tenant)**: Use `ClientScopeService` to filter queries by `client_id`—don't apply scoping manually in controllers
- **API responses**: Return plain JSON; use Laravel's `response()->json()` with appropriate HTTP status codes

### Frontend

- **State management**: React Context + hooks only (no Redux/Zustand/TanStack Query)
- **HTTP**: Axios (`resources/js/lib/axios.js`) — already configured with CSRF, interceptors, and base URL
- **Forms**: React Hook Form + Zod schemas
- **Routing**: Add routes in `routes/web.php` with `Inertia::render('Module/Page')`; create page under `resources/js/Inertia/Pages/` with optional `.layout = AuthenticatedLayout`
- **Catalog data**: Always read from `lib/catalogCache.js` (TTL 600s client-side cache), not raw API calls
- **Styling**: Tailwind CSS + shadcn tokens (`resources/css/app.css`); `class-variance-authority` + `cn()` (`lib/utils`). Tema global light/dark/system (opción A): auth/landing/app; utilidades `lib/chartColors.js`, `lib/badgeStyles.js` (badges, `userStatusClass`, `tableActionIcon`, `statValue`); badges `components/badges/EntityBadges.jsx`, KPI `components/dashboard/KpiCard.jsx`; auth/onboarding shells; marketing `lib/marketingTheme.js` + `.mkt-*`. Evitar `slate-*` y hex sueltos en pantallas nuevas.
- **TypeScript**: SIGUA pages use `.ts`/`.tsx`; rest of app is `.jsx`. New code in new SIGUA files should be TypeScript; new Helpdesk/Users/etc. files can remain JSX

---

## Multi-Tenant / Operator Onboarding

Recent additions (2026 migrations): `clients`, `user_invitations`, `operator_profiles`, `plans` tables. The `client_id` column exists on `users`, `tickets`, `incidents`, and `sites` (tenant key for operational data). See **`docs/DATABASE_TENANCY.md`** for PostgreSQL setup, indexes, and backfill helpers (`App\Support\Database\TenantBackfill`).

`EnsureOnboardingComplete` middleware (alias `onboarding`) blocks access to the main app until the operator completes setup. Don't bypass this middleware for new protected routes.

---

## SIGUA Module

A separate domain for account management, CA-01 forms, HR data cross-reference, and alert generation. Uses its own:
- Model namespace: `App\Models\Sigua\`
- Controller namespace: `App\Http\Controllers\Sigua\`
- Route file: `routes/sigua.php` (registered separately in `bootstrap/app.php`)
- Console commands: `sigua:generar-alertas`, `sigua:cruce`, `sigua:verificar-ca01`, `sigua:verificar-bitacora`, `sigua:resumen-semanal`
- Service classes: `ImportacionService`, `CruceService`, `AlertaService`, `CA01Service`, `BitacoraService`, `ReporteService`
