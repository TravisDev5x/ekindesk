# Base de datos: multi-tenant y PostgreSQL

## Modelo de aislamiento

Tikara usa un modelo **MSP (operador) → clientes finales (tenant operativo)**:

| Nivel | Tabla / columna | Rol |
|-------|-----------------|-----|
| Operador MSP | `operator_profiles`, `users.is_operator` | Cuenta que contrata el producto y da de alta clientes |
| Cliente (tenant de datos) | `clients`, `clients.operator_user_id` | Organización atendida por el operador; clave de aislamiento en tickets/sedes |
| Denormalización | `client_id` en `sites`, `tickets`, `incidents`, `users` | Filtros e índices sin JOIN costoso en listados |

En código y migraciones, **`client_id` = clave de tenant** para datos operativos (tickets, incidencias, sedes). No confundir con `users.client_id` del onboarding MSP (vínculo directo opcional del usuario al cliente).

## Catálogos maestros

Modelo vigente: **plataforma** (`operator_user_id` NULL) + **operador MSP**. No aislamiento por `client_id` en portal por defecto — ver `docs/CATALOG_TENANCY_MODEL.md` y `TENANCY_CATALOG_PER_CLIENT`.

## PostgreSQL

### Conexión

En `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tikara
DB_USERNAME=tikara
DB_PASSWORD=secret
DB_CHARSET=utf8
DB_SSLMODE=prefer
```

`config/database.php` ya define el driver `pgsql` con `search_path=public`.

### Migraciones portables

- **No editar** migraciones ya ejecutadas en producción con SQL solo MySQL.
- Backfills de `client_id` desde `sites`: usar `App\Support\Database\TenantBackfill::syncClientIdFromSites()`.
- La migración `2026_06_01_100000_prepare_tenant_isolation_and_postgresql` añade `incidents.client_id`, índices compuestos y unicidad `(operator_user_id, name|code)` en `clients`.

### ENUM y `after()`

- `enum()` en migraciones antiguas: Laravel los emite como tipos/check según driver; al migrar a PG, probar `migrate:fresh` en staging.
- `->after('column')` se ignora en PostgreSQL (sin efecto, no rompe).

## Índices recomendados (aplicados)

- `tickets (client_id, ticket_state_id)`, `(client_id, created_at)`
- `incidents (client_id, incident_status_id)`, `(client_id, created_at)`
- `sites (client_id, is_active)`
- `users (client_id, status)`
- `clients (operator_user_id, is_active)`

## Scoping en aplicación

### Operador MSP (`OperatorScopeService`)

| Rol / permiso | Alcance de `clients` |
|---------------|----------------------|
| `super_admin` | Toda la plataforma |
| `is_operator`, `clients.view_all`, `tickets.manage_all`, `incidents.manage_all` **sin** tenant vinculado (sede/client_id) | Todos los `clients.operator_user_id` = su operador |
| `tickets.manage_all` / `incidents.manage_all` **con** sede o `users.client_id` | Un solo tenant (`usesOperatorMspWideScope` = false) |
| Staff en sede | Un solo `client_id` (vía sede) |

- API/Web clientes: `OperatorScopeService::applyOnClients()` + validación `name`/`code` por operador.
- Catálogos Inertia/API: `clientsForCatalog()`, `applyOnSites()`.
- Tickets/incidencias con `manage_all`: ya no ven toda la plataforma; `TicketPolicy`/`IncidentPolicy` aplican scope de operador o tenant vinculado.
- Portal estricto: `enforcedClientId()` filtra listados **y** `show` (`ticketVisibleToUser` / `incidentVisibleToUser`); sedes vía `applyOnSites()`.

### Cliente final (`TenantClientResolver` + `ClientScopeService`)

**Resolver único** (`TenantClientResolver::resolve`):

1. `sites.client_id` vía `users.sede_id` (prioridad operativa)
2. `users.client_id` (invitación MSP sin sede con cliente)
3. Si ambos difieren, gana la sede (se registra en log debug)

- Tickets/incidencias: `TicketPolicy::scopeFor` / `IncidentPolicy::scopeFor` aplican área **y** `applyTicketScope` / `applyIncidentScope`.
- Staff con `view_*_area` + `area_id` pero sin tenant: solo filtro por área (no ve otros clientes).
- `view_*_area` sin `area_id`: 403 vía `ClientScopeService::guardOperationalModuleAccess()`.
- Solo `view_own` sin tenant: ve únicamente sus propios registros.

Al crear o actualizar incidencias/tickets, mantener `client_id` alineado con `sede_id` (observer `Incident::saving`; tickets vía controladores y `stampTicketSiteFromUser`).

---

## Catálogos maestros por operador MSP

Migración `2026_06_03_100000_add_operator_scope_to_catalogs_and_audit`:

- Columna `operator_user_id` nullable en tablas de catálogo (`OperatorCatalogScopeService::CATALOG_TABLES`).
- `NULL` = fila global (semilla por defecto); valor = catálogo del operador MSP.
- Lectura: globales + del operador del usuario (`OperatorCatalogScopeService`).
- API catálogos: `PriorityController`, `AreaController`, `CatalogController`, etc.
- Roles/permisos Spatie siguen **globales** (fase posterior: `operator_user_id` en roles o teams).

### Consola legacy (`TENANCY_LEGACY_MSP_WIDE_ACCESS`)

Por defecto **`false`** (producción). Si `true`, usuarios con `tickets.manage_all` / `clients.view_all` sin `is_operator` ni sede vinculada a un operador ven **todos** los clientes en dominio raíz.

Migración recomendada antes de prod:

```bash
php artisan tenant:promote-legacy-operators          # dry-run
php artisan tenant:promote-legacy-operators --apply --assign-orphan-clients
# .env: TENANCY_LEGACY_MSP_WIDE_ACCESS=false
```

---

## Auditoría (`audit_logs`)

- Columna `client_id` denormalizada; backfill desde `tickets`.
- `Auditable` rellena `client_id` al auditar tickets.
- Listado/export: `OperatorScopeService::applyOnAuditLogs()`.

---

## Notificaciones de tickets

`SendTicketNotification` y alertas del solicitante filtran `tickets.manage_all` con `userInTicketOperatorScope()` (mismo operador MSP que el ticket).

---

## SQL portable (PostgreSQL)

`App\Support\Database\SqlDialect` — usado en dashboards (`MainDashboardController`, `ResolbebController`, `TicketAnalyticsController`).

---

## SIGUA

Módulo **sin** aislamiento tenant en esta fase. Documentar despliegue separado o añadir `operator_user_id` en fase SIGUA.

---

## SaaS avanzado (preparado)

| Pieza | Estado |
|-------|--------|
| Middleware `ResolveTenantContext` | Subdominio / cabecera `X-Tenant-Subdomain` |
| `config/tenancy.php` | `TENANCY_BASE_DOMAIN`, flag RLS |
| RLS PostgreSQL | Migración `2026_06_05_*`; `ApplyPgsqlTenantRls` + `PgsqlRowLevelSecurity` |
| Roles por operador | Pendiente (Spatie global) |
| Tests aislamiento | `OperatorScopeServiceTest`, `TenantClientResolverTest`, `TenantCatalogScopeTest` |
| Roadmap 100 % | `docs/MULTITENANT_ROADMAP.md` |

## Row Level Security (PostgreSQL)

Segunda línea de defensa en `tickets`, `incidents`, `sites`.

### Activar

```env
DB_CONNECTION=pgsql
TENANCY_PGSQL_RLS=true
```

```bash
php artisan migrate   # aplica política tikara_tenant_isolation
```

### Variables de sesión (por petición HTTP)

| Variable | Origen |
|----------|--------|
| `app.tenant_bypass` | `super_admin` o legacy `manage_all` sin operador |
| `app.tenant_client_id` | Portal estricto (`portal_slug`) |
| `app.tenant_operator_id` | MSP del usuario |
| `app.tenant_user_client_id` | `TenantClientResolver` |

`App\Http\Middleware\ApplyPgsqlTenantRls` las establece; Artisan/PHPUnit usa bypass en consola.

### Requisito de usuario de BD

Con `FORCE ROW LEVEL SECURITY`, el usuario en `.env` **no debe ser superuser** (los superusers ignoran RLS). Crear rol dedicado:

```sql
CREATE ROLE tikara_app LOGIN PASSWORD '...';
GRANT USAGE ON SCHEMA public TO tikara_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO tikara_app;
```

### MySQL / SQLite

La migración RLS es no-op; el aislamiento sigue solo en aplicación.

## Comandos

```bash
php artisan migrate
php artisan migrate:fresh --seed   # solo entornos locales / CI

# Verificar / reparar client_id denormalizado
php artisan tenant:client-id verify
php artisan tenant:client-id verify --strict   # CI/release: falla si huérfanos o client_id NULL
php artisan tenant:client-id sync
php artisan tenant:client-id sync --assign-sites   # asigna cliente PLATFORM a sedes sin client_id
```

### PostgreSQL local (Docker)

```bash
docker compose -f docker-compose.postgres.yml up -d
# .env → DB_CONNECTION=pgsql, DB_DATABASE=tikara, DB_USERNAME=tikara, DB_PASSWORD=secret
php artisan migrate:fresh --seed
php artisan tenant:client-id verify
php artisan tenant:client-id verify --strict   # CI/release: falla si huérfanos o client_id NULL
```

### CI (GitHub Actions)

Workflow `.github/workflows/tests.yml`:

- **SQLite** — suite rápida en cada push/PR (`composer test`).
- **PostgreSQL + RLS** — `TENANCY_PGSQL_RLS=true`, usuario `tikara_app` (no superuser), `composer test:pgsql` con `phpunit.pgsql.xml`.

Local con PG: crear rol sin superuser, activar RLS y ejecutar `composer test:pgsql`.

## Integridad `client_id` NOT NULL

La migración `2026_06_02_100000_enforce_tenant_client_id_not_null`:

1. Asigna cliente `PLATFORM` a sedes sin `client_id` (legacy, ej. Remoto).
2. Ejecuta backfill en tickets e incidencias.
3. Falla si quedan huérfanos (sede con cliente, hijo sin `client_id`).
4. Aplica `NOT NULL` en `tickets.client_id` e `incidents.client_id`.

Los modelos `Ticket` e `Incident` mantienen `client_id` alineado con `sede_id` al guardar.
