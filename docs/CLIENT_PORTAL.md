# Portal por empresa final (tenant aislado)

## Qué obtienes

Cada fila en `clients` (empresa atendida por el MSP) puede tener:

| Campo | Uso |
|-------|-----|
| `portal_slug` | Subdominio: `https://{slug}.{TENANCY_BASE_DOMAIN}` |
| `portal_primary_color` | Branding (CSS variable en frontend) |
| `portal_welcome_message` | Texto login / bienvenida |
| `logo_path` | Logo del portal |

Con `TENANCY_STRICT_CLIENT_PORTAL=true` (default):

- **Solo** tickets, incidencias, sedes y catálogos con ese `client_id`.
- Usuario de Cliente B **no** entra al subdominio de Cliente A (403).
- Personal del MSP del mismo operador **sí** puede entrar al portal de sus clientes (soporte).

## Configuración

```env
TENANCY_BASE_DOMAIN=ekindesk.test
TENANCY_PORTAL_SCHEME=http
TENANCY_STRICT_CLIENT_PORTAL=true
TENANCY_ENFORCE_SUBDOMAIN=true
```

Laragon / hosts:

```
127.0.0.1 ekindesk.test
127.0.0.1 cliente-a.ekindesk.test
```

Sanctum:

```
SANCTUM_STATEFUL_DOMAINS=localhost,ekindesk.test,cliente-a.ekindesk.test
```

## Migración

```bash
php artisan migrate
```

Genera `portal_slug` para clientes existentes y añade `client_id` a tablas de catálogo.

## Catálogos en el portal

Por defecto los catálogos son **compartidos del operador MSP** (plataforma + `operator_user_id`), no duplicados por empresa. Ver `docs/CATALOG_TENANCY_MODEL.md`.

```env
TENANCY_CATALOG_PER_CLIENT=false   # default
```

## Semillas de catálogo por operador MSP

Clona catálogos de **plataforma** (`operator_user_id` NULL) al operador, o aplica una **plantilla mínima** si no hay filas globales. Es **idempotente** y respeta la unicidad legacy en `name`/`code`: si ya existe la fila de plataforma, el operador la **hereda** vía scope (no se duplica).

```bash
# Operador MSP (usuario con is_operator o dueño de clients)
php artisan tenant:seed-catalogs 42

# Simular
php artisan tenant:seed-catalogs 42 --dry-run

# Solo algunas tablas
php artisan tenant:seed-catalogs 42 --only=areas,priorities,ticket_states

# Modo catálogo por empresa (TENANCY_CATALOG_PER_CLIENT=true)
php artisan tenant:seed-catalogs 42 --client=7
```

Ver `docs/CATALOG_TENANCY_MODEL.md` y `app/Services/OperatorCatalogSeedService.php`.

## Roles Spatie

Siguen siendo **globales** (mismos nombres de permiso). El aislamiento lo hace el **subdominio + scopes**, no roles distintos por cliente.

Fase futura: `model_has_roles.client_id` o teams por tenant.

## Consola MSP vs portal cliente

| URL | Modo |
|-----|------|
| `ekindesk.test` | Plataforma / MSP (varios clientes del operador) |
| `acme-corp.ekindesk.test` | Portal **solo** empresa Acme Corp |

## Frontend

Props Inertia compartidas: `tenant` (`mode`, `name`, `logo_path`, `portal_primary_color`, …).

- **Login:** panel lateral y formulario móvil (`Auth/Login.jsx`).
- **App autenticada:** sidebar (`Sidebar.jsx`), breadcrumb/header (`AuthenticatedLayout.jsx`) y variable CSS `--brand` en portal cliente.
- **Consola MSP** (dominio raíz): sigue mostrando marca EkinDesk; `tenant.mode === "platform"`.

Utilidades: `resources/js/lib/tenantBranding.js`, componentes `resources/js/components/TenantBrand.jsx`.

## Documentación relacionada

- `docs/SANCTUM_TENANCY.md` — cookies y subdominios (Laragon/producción)
- `docs/API_TENANCY_AUDIT.md` — rutas API y gaps de scope
- `docs/MULTITENANT_ROADMAP.md` — plan hasta multi-tenant 100 %
