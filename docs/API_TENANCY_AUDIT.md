# Auditoría de rutas API — multi-tenant

Última revisión: 2026-06-05. Fuente: `routes/api.php`.

## Leyenda

| Nivel tenant | Significado |
|--------------|-------------|
| **A** | Aislamiento fuerte (Policy + `ClientScopeService` / portal estricto) |
| **B** | Scope operador MSP (`OperatorScopeService` / `ManagesOperatorCatalog`) |
| **C** | Global / plataforma (roles, permisos, campañas sin `client_id`) |
| **D** | Solo auth + permiso; revisar scope en controlador |
| **P** | Público / pre-auth |

## Middleware global API (orden)

1. `EnsureSessionForAuth` + Sanctum stateful  
2. `EnforceTenantBoundary` — portal por subdominio  
3. `ApplyPgsqlTenantRls` — variables PG si `TENANCY_PGSQL_RLS=true`  
4. `EnforcePasswordChange`  
5. `perm:*` por grupo de ruta  

## Rutas por módulo

### Auth (P)

| Método | Ruta | Scope |
|--------|------|-------|
| POST | `/api/login` | Portal validado en `AuthController` |
| POST | `/api/register` | Público |
| GET | `/api/register/verify` | Público |
| POST | `/api/logout` | — |
| GET | `/api/ping` | — |

### Password (P)

| Método | Ruta | Scope |
|--------|------|-------|
| POST | `/api/password/forgot` | Público |
| POST | `/api/password/reset` | Público |

### Usuarios e invitaciones (B/D)

| Método | Ruta | Controlador | Scope aplicado |
|--------|------|-------------|----------------|
| GET/POST/PATCH/DELETE | `/api/users*` | `UserController` | `applyUserScope` |
| GET/POST/DELETE | `/api/invitations*` | `InvitationController` | `InvitationTenancyService` ✅ (flujo B: rol opcional) |
| GET | `/api/sessions` | `SessionMonitorController` | `applyUserScope` ✅ |
| POST | `/api/sessions/logout-user` | `SessionMonitorController` | `assertUserAccessible` ✅ |

### Roles y permisos (C)

| Método | Ruta | Notas |
|--------|------|-------|
| GET/POST/PUT/DELETE | `/api/roles*` | Spatie global |
| GET/POST/PUT/DELETE | `/api/permissions*` | Spatie global |
| POST | `/api/roles/{role}/permissions` | Global |
| POST | `/api/users/{user}/roles` | Global |

### Catálogos `catalogs.manage` (B)

**Modelo:** plataforma + operador MSP (`docs/CATALOG_TENANCY_MODEL.md`). Portal estricto no filtra por `client_id` salvo `TENANCY_CATALOG_PER_CLIENT=true`.

| Método | Ruta | Controlador | `ManagesOperatorCatalog` |
|--------|------|-------------|--------------------------|
| GET/POST/PUT/DELETE | `/api/clientes*` | `ClienteController` | OperatorScope ✅ |
| GET/POST/PUT/DELETE | `/api/sedes*` | `SedeController` | OperatorScope ✅ |
| GET/POST/PUT/DELETE | `/api/priorities*` | `PriorityController` | ✅ |
| GET/POST/PUT/DELETE | `/api/areas*` | `AreaController` | ✅ |
| GET/POST/PUT/DELETE | `/api/impact-levels*` | `ImpactLevelController` | ✅ |
| GET/POST/PUT/DELETE | `/api/urgency-levels*` | `UrgencyLevelController` | ✅ |
| GET/POST/PUT/DELETE | `/api/ticket-states*` | `TicketStateController` | ✅ |
| GET/POST/PUT/DELETE | `/api/ticket-types*` | `TicketTypeController` | ✅ |
| GET/POST/PUT/DELETE | `/api/incident-types*` | `IncidentTypeController` | ✅ |
| GET/POST/PUT/DELETE | `/api/incident-severities*` | `IncidentSeverityController` | ✅ |
| GET/POST/PUT/DELETE | `/api/incident-statuses*` | `IncidentStatusController` | ✅ |
| GET/POST/PUT/DELETE | `/api/campaigns*` | `CampaignController` | ✅ |
| GET/POST/PUT/DELETE | `/api/ubicaciones*` | `UbicacionController` | Sede scope |
| GET/POST | `/api/priority-matrix*` | `PriorityMatrixController` | ⚠️ Revisar |
| GET/POST/PUT/DELETE | `/api/positions*` | `PositionController` | ✅ |

### Lectura catálogos UI (B)

| Método | Ruta | Controlador | Scope |
|--------|------|-------------|-------|
| GET | `/api/catalogs` | `CatalogController` | `OperatorCatalogScopeService` ✅ |

### Tickets operativos (A)

| Método | Ruta | Controlador | Scope |
|--------|------|-------------|-------|
| * | `/api/tickets*` | `TicketController` | Policy + `ClientScopeService` ✅ |
| * | `/api/tickets/{ticket}/attachments*` | `TicketAttachmentController` | Policy ticket ✅ |
| GET | `/api/tickets/analytics` | `TicketAnalyticsController` | Scope ✅ |
| GET | `/api/tickets/dashboard-operativo` | `ResolbebController` | Scope ✅ |
| GET/POST | `/api/ticket-macros*` | `TicketMacroController` | ⚠️ Revisar operador |

### Mis tickets — solicitante (A)

| Método | Ruta | Controlador | Scope |
|--------|------|-------------|-------|
| * | `/api/my-tickets*` | `MyTicketsController` | `RequesterTicketPolicy` + own ✅ |

### Incidencias (A)

| Método | Ruta | Controlador | Scope |
|--------|------|-------------|-------|
| * | `/api/incidents*` | `IncidentController` | Policy + scope ✅ |
| * | `/api/incidents/{incident}/attachments*` | `IncidentAttachmentController` | Policy ✅ |

### Notificaciones (D)

| Método | Ruta | Notas |
|--------|------|-------|
| GET/POST | `/api/notifications*` | Solo `user_id` implícito ✅ |
| GET/POST | `/api/admin/notifications*` | Admin plataforma |

### Perfil (D)

| Método | Ruta | Notas |
|--------|------|-------|
| GET/POST/PUT | `/api/profile*` | Solo usuario autenticado ✅ |

## Acciones priorizadas (post-sprint)

1. ~~Añadir `ManagesOperatorCatalog` a controladores de catálogo~~ — **Hecho (fase 1.1, 2026-06)**. Tests: `CatalogApiOperatorScopeTest`.
2. Test de integración: usuario Cliente A no lista tickets de Cliente B (mismo MSP) vía API.
3. ~~`SessionMonitorController`: filtrar sesiones por operador/cliente.~~ — **Hecho (fase 1.3)**. Tests: `SessionMonitorScopeTest`.
4. SIGUA: rutas en `routes/sigua.php` — **sin tenant** (despliegue separado o fase 2).

## Checklist manual por release

- [ ] `php artisan tenant:client-id verify` en staging  
- [ ] Probar login en `{slug}.base_domain` con usuario ajeno → 403  
- [ ] Con `TENANCY_PGSQL_RLS=true`, listar tickets solo del tenant esperado  
- [ ] `SANCTUM_STATEFUL_DOMAINS` incluye subdominios de portal  
