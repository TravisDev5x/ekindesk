# Modelo de catálogos maestros (decisión de producto)

**Estado:** vigente desde fase 1.2 (2026-06).

## Decisión

Los catálogos (prioridades, estados, tipos, áreas, etc.) **no son privados por cada empresa final** (`client_id`).

| Capa | Alcance |
|------|---------|
| **Plataforma** | `operator_user_id` = `NULL` — semilla global, visible para todos |
| **Operador MSP** | `operator_user_id` = dueño del MSP — solo ese operador y sus clientes/portales |
| **Empresa final** | Usa los catálogos del **operador** que la atiende; el portal por subdominio **no duplica** catálogos por `client_id` |

El aislamiento entre empresas finales del mismo MSP es por **datos operativos** (`tickets`, `incidents`, `sites`, `users`), no por filas de catálogo duplicadas.

## Columna `client_id` en tablas de catálogo

Existe por migración (`2026_06_04`) para **uso futuro opcional**. Con la configuración por defecto:

```env
TENANCY_CATALOG_PER_CLIENT=false
```

la aplicación **ignora** `client_id` al leer/escribir catálogos. No crear filas con `client_id` en producción salvo feature explícita.

Si en el futuro un producto requiere catálogos por empresa:

```env
TENANCY_CATALOG_PER_CLIENT=true
```

Entonces en portal estricto se filtra y asigna `client_id` del subdominio (comportamiento anterior a 1.2).

## Portal estricto (`TENANCY_STRICT_CLIENT_PORTAL=true`)

En `{portal_slug}.dominio`:

- **Lectura catálogos:** plataforma + operador del `clients.operator_user_id` del portal.
- **Creación:** nuevas filas llevan `operator_user_id` del operador del portal; `client_id` queda `NULL`.
- **Tickets/incidencias:** siguen filtrados por `client_id` del portal (sin cambio).

## Consola MSP (dominio raíz)

Comportamiento ya documentado en `OperatorCatalogScopeService`: global + filas del operador del usuario.

## Roles Spatie

Siguen **globales**; el vocabulario de permisos es compartido. El alcance lo dan scopes + portal, no roles distintos por cliente.

## Referencias

- `app/Services/OperatorCatalogScopeService.php`
- `config/tenancy.php` → `catalog_per_client`
- `docs/API_TENANCY_AUDIT.md`
