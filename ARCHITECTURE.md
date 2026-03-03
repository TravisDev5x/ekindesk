# Documentación Maestra de Arquitectura — Plataforma Multi-Módulo

Este documento describe la arquitectura global del sistema, los módulos de negocio, el stack tecnológico y las convenciones del proyecto. Sirve como mapa de la plataforma y guía de onboarding para nuevos desarrolladores.

---

## 1. Visión General de la Plataforma

La plataforma es un **sistema multi-módulo** que convive bajo una misma base de código Laravel + React. No responde a un único propósito; integra varios dominios de negocio:

- **Helpdesk / Mesa de ayuda**: gestión de tickets (estados, prioridades, áreas, asignación, escalamiento, macros, auditoría).
- **Incidencias**: flujo paralelo al de tickets (tipos, severidades, estados, asignación por área).
- **Control de accesos y usuarios**: usuarios, roles, permisos (Spatie), sesiones, perfiles y catálogos base (campañas, áreas, puestos, sedes, ubicaciones).
- **TimeDesk**: asistencias (marcajes), horarios, asignación de horarios a usuarios, directorio de empleados, bajas laborales, catálogos RH (motivos de baja, estatus empleado, tipo de ingreso, medios de contratación), importación/exportación de empleados.
- **SIGUA**: módulo independiente para gestión de cuentas genéricas, sistemas, empleados RH, CA-01, bitácora, incidentes SIGUA, importaciones, cruces y reportes; con comandos programados (alertas, verificación CA-01, bitácora, cruces, resumen semanal).
- **Resolbeb**: ticketera expuesta en el frontend como submódulo (rutas `/resolbeb/*`), reutilizando la misma API de tickets y catálogos.

La aplicación se sirve como **SPA (Single Page Application)** en React; el backend expone una **API REST** consumida por el frontend. La autenticación unificada se basa en **Laravel Sanctum** en modo stateful (cookies) para la SPA, con verificación de sesión vía ruta web `/check-auth` y uso de tokens/cookies en las peticiones API.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión / Notas |
|------|------------|-----------------|
| **Backend** | PHP | ^8.2 |
| | Laravel | ^12.0 |
| | Laravel Sanctum | ^4.2 |
| | Laravel Tinker | ^2.10.1 |
| | Spatie Laravel Permission | ^6.24 |
| | Maatwebsite Excel | ^1.1 |
| | PhpSpreadsheet | ^5.4 |
| **Frontend** | React | ^19.2.3 |
| | React DOM | ^19.2.3 |
| | React Router DOM | ^7.12.0 |
| | Vite | ^7.0.7 |
| | @vitejs/plugin-react | ^5.1.2 |
| | Laravel Vite Plugin | ^2.0.0 |
| **UI / Estilos** | Tailwind CSS | ^4.1.18 |
| | Radix UI (Avatar, Checkbox, Dialog, Dropdown, Label, Popover, Progress, ScrollArea, Select, Separator, Slot, Switch, Toast, Tooltip) | Varios ^1.x / ^2.x |
| | Lucide React | ^0.562.0 |
| | class-variance-authority, clsx, tailwind-merge, tailwindcss-animate | Uso en componentes |
| **Formularios / Datos** | React Hook Form | ^7.71.1 |
| | @hookform/resolvers | ^5.2.2 |
| | Zod | ^4.3.5 |
| **Tablas / Gráficas** | TanStack React Table | ^8.21.3 |
| | Recharts | ^3.7.0 |
| | date-fns | ^4.1.0 |
| | react-day-picker | ^9.13.0 |
| **Otros frontend** | Axios | ^1.11.0 |
| | Sonner (toast) | ^2.0.7 |
| | Sileo (toaster) | ^0.1.4 |

- **Entorno**: `.env` (ejemplo en `.env.example`). Base de datos por defecto SQLite; soporte MySQL/Redis/cache/queue/session configurable.
- **Build**: `vite.config.js` con alias `@` → `resources/js`, chunks manuales (router, react-vendor, ui-vendor, vendor).
- **Herramientas de desarrollo**: Laravel Pail, Pint, Sail, PHPUnit; Faker en seeders.

---

## 3. Arquitectura Global del Sistema

### Comunicación Frontend — Backend

- **Modelo**: API REST pura consumida por una SPA React. No se usa Inertia.js; el frontend es 100% React con React Router y llamadas HTTP vía Axios.
- **Rutas backend**:
  - **Web** (`routes/web.php`): solo lógica de frontend y sesión: `GET /check-auth` (verificación de autenticación por sesión), `GET /login` y catch-all `GET /{any}` que sirve la vista `app` (SPA). No se mezcla lógica de API en web.
  - **API** (`routes/api.php`): prefijo `/api`, middleware `api` (Sanctum stateful + `EnsureSessionForAuth`, `EnsureFrontendRequestsAreStateful`). Rutas de login/logout/register en API; el resto de endpoints requieren `auth:sanctum` y en muchos casos el middleware `perm:...` (permisos).
  - **SIGUA** (`routes/sigua.php`): registrado bajo prefijo `api` en `bootstrap/app.php`, por tanto todas las rutas SIGUA están bajo `/api/sigua/*`.

### Autenticación Unificada

- **Login**: el frontend obtiene cookie CSRF (`/sanctum/csrf-cookie`) y envía credenciales a `POST /api/login`. La respuesta incluye usuario y (en el flujo actual) roles/permisos; la sesión se mantiene por cookies.
- **Verificación de sesión**: en rutas no públicas el frontend llama `GET /check-auth` (ruta **web** con middleware `auth`). Esa ruta usa el guard `web` y devuelve JSON con `authenticated`, `user`, `roles`, `permissions`. No debe llamarse desde `/login` (allí no hay sesión aún).
- **Principio documentado en código**: “Sesión y API no se cruzan”; la API no valida sesión para “verificar login”, solo usa `auth:sanctum` (token/cookie). Los 401 en API se traducen en redirección a login desde el interceptor de Axios (`navigate-to-login`).
- **Axios**: `resources/js/lib/axios.js` — `baseURL: "/"`, `withCredentials: true`, uso de cookie XSRF; interceptor para métodos no-GET asegura CSRF; en 401/419 dispara evento para ir a login.

### Flujo de la SPA

- Entrada: `resources/js/app.jsx` → `Main.jsx`.
- `Main.jsx`: `AuthProvider` → `SidebarPositionProvider` → `I18nProvider` → `Toaster` (Sileo) → `BrowserRouter` con `Routes`. Rutas públicas (login, register, forgot/reset password, verify-email, manual); rutas protegidas con `ProtectedRoute` (Outlet); dentro, `AppLayout` con menú lateral y rutas hijas.
- Rutas privadas definidas en un único archivo (`Main.jsx`): lazy loading de páginas; rutas para dashboard, usuarios, catálogos, tickets, mis-tickets, resolbeb, incidencias, TimeDesk (envueltas en `TimeDeskGuard`), SIGUA, auditoría, perfil, etc.

---

## 4. Ecosistema de Módulos (Core Business)

Los hallazgos se agrupan por dominio según controladores, modelos, rutas y servicios.

### 4.1 Módulo de Helpdesk (Tickets)

- **Propósito**: Gestión de tickets de soporte: creación, asignación, escalamiento, estados, prioridades, áreas, sedes/ubicaciones, adjuntos, historial, alertas del solicitante, macros, auditoría y reportes.
- **Entidades principales**: `Ticket`, `TicketState`, `TicketType`, `TicketHistory`, `TicketAttachment`, `TicketAlert`, `TicketAreaAccess`, `TicketMacro`, `TicketAuditLog`; catálogos compartidos: `Priority`, `ImpactLevel`, `UrgencyLevel`, `PriorityMatrix`, `Area`, `Sede`, `Ubicacion`, `Campaign`, `Position`.
- **Rutas API**:  
  - `GET/POST /api/tickets`, `GET/PUT /api/tickets/{id}`, `POST take/assign/unassign/alert/cancel/escalate`, adjuntos, `GET tickets/analytics`, `GET tickets/summary`, `GET tickets/audit-logs`, `GET tickets/audit-export`, `GET tickets/export`.  
  - Catálogos: `ticket-states`, `ticket-types`, `priorities`, `impact-levels`, `urgency-levels`, `priority-matrix`, etc. (parte en `catalogs` y parte en recursos bajo `perm:catalogs.manage`).
- **Controladores**: `TicketController`, `TicketAttachmentController`, `TicketAnalyticsController`, `TicketStateController`, `TicketTypeController`, `PriorityController`, `ImpactLevelController`, `UrgencyLevelController`, `PriorityMatrixController`, `TicketMacroController`.
- **Políticas**: `TicketPolicy` (alcance por área: manage_all, view_area, view_own; acciones assign, change_status, comment, escalate, alert, cancel). `RequesterTicketPolicy` usada solo en `MyTicketsController` (vista solicitante).
- **Observers**: `TicketObserver` — escribe en `TicketAuditLog` en created/updated/deleted/restored.
- **Servicios**: `RequesterTicketService` (alertas y comentarios del solicitante, notificaciones).
- **Form Requests**: `StoreTicketRequest`, `UpdateTicketRequest`.
- **Frontend**: `Tickets`, `TicketCreate`, `TicketDetalle`, `TicketEstados`, `TicketTipos`, `TicketMacros`, `Prioridades`, `ImpactLevels`, `UrgencyLevels`, `Settings/PriorityMatrix`, `AuditCommandCenter`, `AuditTimeline`; catálogos leídos vía `GET /api/catalogs` y caché en front (p. ej. `lib/catalogCache.js`).

### 4.2 Módulo “Mis Tickets” (Solicitante)

- **Propósito**: Vista limitada al solicitante: listar sus tickets, crear, ver detalle, descargar adjuntos, subir adjuntos, comentar, enviar alerta, cancelar. Desacoplado del flujo operativo de agentes.
- **Rutas API**: `GET/POST /api/my-tickets`, `GET /api/my-tickets/{ticket}`, adjuntos y comentarios bajo ese prefijo.
- **Controlador**: `MyTicketsController`; autorización vía `RequesterTicketPolicy` (instanciada en controlador, no registrada como Gate global).
- **Servicio**: `RequesterTicketService` (alertas, comentarios, notificaciones).

### 4.3 Módulo de Incidencias

- **Propósito**: Flujo de incidencias con tipos, severidades y estados propios; asignación por área y alcance por permisos (manage_all, view_area, view_own).
- **Entidades**: `Incident`, `IncidentType`, `IncidentSeverity`, `IncidentStatus`, `IncidentAttachment`, `IncidentHistory`.
- **Rutas API**: `apiResource incidents`, más `take`, `assign`, `unassign`, adjuntos. Catálogos: `incident-types`, `incident-severities`, `incident-statuses`.
- **Controladores**: `IncidentController`, `IncidentAttachmentController`, `IncidentTypeController`, `IncidentSeverityController`, `IncidentStatusController`.
- **Política**: `IncidentPolicy` (misma lógica de alcance que tickets: all, area+own, area, own).
- **Frontend**: `Incidents`, `IncidentDetalle`, `IncidentTipos`, `IncidentSeveridades`, `IncidentEstados`.

### 4.4 Módulo de Control de Accesos y Usuarios

- **Propósito**: Usuarios, roles, permisos (Spatie), asignación usuario-rol, sesiones activas, perfil y preferencias.
- **Entidades**: `User`, `Role`, `Permission` (tablas de Spatie: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions).
- **Rutas API**:  
  - Auth: `POST /api/login`, `register`, `logout`, `GET ping`; `POST password/forgot`, `password/reset`.  
  - Usuarios: bajo `perm:users.manage` — `apiResource users`, mass-delete, restore, force-delete.  
  - Roles: `GET roles` (auth), mutaciones bajo `perm:roles.manage`; `POST roles/{role}/permissions` (sync).  
  - Permisos: `GET permissions` (auth), CRUD bajo `perm:permissions.manage|roles.manage`.  
  - Sesiones: `GET sessions`, `POST sessions/logout-user` (perm:users.manage).  
  - Perfil: `GET/POST profile`, `PUT profile/password`, theme, density, sidebar, preferences.  
  - Asignaciones: `POST users/{user}/roles` (UserRoleController).
- **Controladores**: `AuthController`, `UserController`, `RoleController`, `PermissionController`, `UserRoleController`, `RolePermissionController`, `SessionMonitorController`, `ProfileController`, `PasswordResetController`.
- **Middleware**: `EnsurePermissionOrAdmin` (alias `perm`): bypass admin, primer usuario o ventana sin asignaciones; en resto exige uno de los permisos indicados (soporta `perm:a|b`).
- **Frontend**: `Users`, `Roles`, `Permissions`, `Sessions`, `Profile`, `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `ForceChangePassword`, `VerifyEmail`.

### 4.5 Catálogos Transversales

- **Propósito**: Un único endpoint de lectura para la UI con campañas, áreas, puestos, sedes, ubicaciones, prioridades, impact/urgency, matriz de prioridad, estados/tipos de ticket e incidencias, horarios, roles, usuarios (según permisos), etc.
- **Rutas API**: `GET /api/catalogs` (auth:sanctum). Opción `nocache` para evitar caché.
- **Controlador**: `CatalogController` — construye payload con TTL de caché (ej. 600 s) por usuario.
- **Ubicaciones**: lectura adicional `GET /api/ubicaciones` con `perm:users.manage|catalogs.manage`. CRUD de campañas, áreas, puestos, sedes, ubicaciones, prioridades, impact/urgency, ticket-states/types, incident-types/severities/statuses, schedules bajo `perm:catalogs.manage`.
- **Frontend**: Uso de `catalogs` y `catalogCache.js` en formularios y listas.

### 4.6 TimeDesk (Asistencias, Horarios, RH)

- **Propósito**: Marcaje de asistencia (punch), consulta de “mi horario”, dashboard de asistencias, gestión de horarios (schedules) y asignación a usuarios, directorio de empleados (alta/baja, import/export), catálogos RH (motivos de baja, estatus empleado, tipo de ingreso, medios de contratación).
- **Entidades**: `Attendance`, `Schedule`, `ScheduleDay`, `ScheduleAssignment`, `EmployeeProfile`, `TerminationReason`, `EmployeeStatus`, `HireType`, `RecruitmentSource`.
- **Rutas API**:  
  - Perfil: `GET attendance/status`, `POST attendance/punch` (perm:attendances.record_own); `GET my-schedule` (perm:attendances.view_own).  
  - Bajo `perm:attendances.manage|attendances.view_all`, prefijo `timedesk`: dashboard, termination-reasons, employee-statuses, hire-types, recruitment-sources, employees (listado).  
  - Bajo `perm:attendances.manage`: CRUD de termination-reasons, employee-statuses, hire-types, recruitment-sources; import/export empleados; `TimeDeskEmployeeController` (catalogs, store, terminateEmployees); `ScheduleManagerController` (assignments, assign).  
  - Horarios: `apiResource schedules` bajo catalogs.manage.
- **Controladores**: `AttendanceController`, `MyScheduleController`, `TimeDeskController`, `ScheduleController`, `ScheduleManagerController`, `TerminationReasonController`, `EmployeeStatusController`, `HireTypeController`, `RecruitmentSourceController`, `EmployeeImportExportController`, `TimeDeskEmployeeController`.
- **Frontend**: `Attendance`, `Schedules`, `ScheduleAssignmentManager`, `TimeDesk/Dashboard`, `TimeDesk/Employees`, `TimeDesk/TerminationReasons`, `TimeDesk/EmployeeStatuses`, `TimeDesk/HireTypes`, `TimeDesk/RecruitmentSources`; acceso protegido por `TimeDeskGuard` (permisos attendances).

### 4.7 SIGUA

- **Propósito**: Gestión de cuentas genéricas, sistemas, empleados RH, formularios CA-01, bitácora, incidentes SIGUA, importaciones de datos, cruces y reportes; alertas y comandos programados.
- **Entidades** (namespace `App\Models\Sigua`): `CuentaGenerica`, `Sistema`, `EmpleadoRh`, `FormatoCA01`, `Bitacora`, `BitacoraSinUso`, `Incidente`, `Importacion`, `Cruce`, `CruceResultado`, `Alerta`, `Configuracion`; también `App\Models\Sigua\*` y traits como `SiguaRelations`.
- **Rutas API**: Todas bajo `/api/sigua`, middleware `auth:sanctum`, `locale`.  
  - Dashboard, catálogos (sistemas), empleados-rh, cuentas (CRUD, bulk-estado, clasificar, vincular).  
  - CA-01: index, store, show, update, renovar.  
  - Bitácora: index, hoy, porSede, sinUso, store, storeBulk, storeSinUso, cumplimiento.  
  - Incidentes: index, store, show, update, investigar, resolver, escalar.  
  - Importar: preview, importar, historial.  
  - Cruces: ejecutar, historial, detalle, comparar.  
  - Alertas: index, marcarLeida, resolver.  
  - Configuración: index, update.  
  - Reportes: resumen, exportar-cuentas, exportar-bitacora, exportar-cruce.
- **Controladores** (namespace `App\Http\Controllers\Sigua`): `SiguaDashboardController`, `SiguaCatalogController`, `SistemaController`, `EmpleadoRhController`, `CuentaGenericaController`, `CA01Controller`, `BitacoraController`, `IncidenteController`, `ImportacionController`, `CruceController`, `AlertaController`, `ConfiguracionController`, `ReporteController`.
- **Servicios**: `ImportacionService`, `AlertaService`, `CA01Service`, `BitacoraService`, `CruceService`, `ReporteService`.
- **Form Requests**: `ImportarArchivoRequest`, `StoreCA01Request`, `StoreIncidenteRequest`, `StoreBitacoraRequest`, `StoreBitacoraBulkRequest`, `StoreCuentaGenericaRequest`, `UpdateCuentaGenericaRequest`.
- **Comandos** (`routes/console.php`): `sigua:generar-alertas`, `sigua:verificar-ca01`, `sigua:verificar-bitacora`, `sigua:cruce`, `sigua:resumen-semanal`.
- **Frontend**: Páginas bajo `Pages/Sigua/` (Dashboard, Cuentas, CuentaDetalle, Empleados, EmpleadoDetalle, Sistemas, Alertas, Configuracion, CA01, CA01Nuevo, CA01Detalle, Bitacora, BitacoraSede, Incidentes, IncidenteDetalle, Importar, Cruces, Reportes); hooks y API en `hooks/sigua/`, `services/siguaApi.ts`, `types/sigua.ts`; parte en TypeScript.

### 4.8 Resolbeb (Ticketera en Frontend)

- **Propósito**: Misma lógica de negocio que Helpdesk (tickets), expuesta como submódulo en la SPA con rutas `/resolbeb`, `/resolbeb/mis-tickets`, `/resolbeb/tickets`, crear, detalle, estados, tipos.
- **Backend**: No hay controladores específicos “Resolbeb”; se reutilizan `TicketController`, `MyTicketsController`, catálogos y políticas de tickets.
- **Frontend**: `Resolbeb/Dashboard`, `Resolbeb/Index`, `Resolbeb/Create`, `Resolbeb/Detalle`, `Resolbeb/Estados/Index`, `Resolbeb/Tipos/Index` y variantes bajo `Resolbeb/Resolvev1/`.

### 4.9 Notificaciones y Administración

- **Notificaciones in-app**: `GET /api/notifications`, `POST read-all`, `POST notifications/{id}/read` (NotificationController).
- **Admin notifications**: `GET/POST admin/notifications`, `resolve-password` bajo `perm:notifications.manage` (AdminNotificationController).
- **Configuración global**: Headers de seguridad (`config/security.php`), auditoría de reportes y tickets (`config/helpdesk.php`), Sanctum (`config/sanctum.php`), Spatie (`config/permission.php`).

---

## 5. Seguridad y Trazabilidad Transversal

### Permisos y roles

- **Spatie Laravel Permission**: roles y permisos almacenados en BD; asignación a modelos (User). Nombres de permisos usados en rutas: `users.manage`, `roles.manage`, `permissions.manage`, `catalogs.manage`, `tickets.*`, `incidents.*`, `attendances.*`, `notifications.manage`, `sigua.*` (dashboard, cuentas.view|manage, ca01.view|manage, bitacora.*, incidentes.*, importar, cruces, reportes).
- **Middleware `perm`** (`EnsurePermissionOrAdmin`): aplicado en rutas API; permite acceso si el usuario es admin, no hay asignaciones de roles en el sistema (arranque), es el primer usuario, o tiene alguno de los permisos indicados (separadores `|` o `,`).
- **Alias adicionales** en `bootstrap/app.php`: `role`, `permission`, `role_or_permission` (middlewares de Spatie).

### Políticas (Policies)

- **TicketPolicy**: registrada para `Ticket` en `AppServiceProvider`. Define viewAny, view, create, update, changeStatus, changeArea, comment, assign, release, escalate, alert, cancel y scope `scopeFor` para filtrar por alcance (all, area+own, area, own).
- **IncidentPolicy**: registrada para `Incident`. Misma idea de alcance y acciones (view, update, assign, comment, changeStatus).
- **RequesterTicketPolicy**: no registrada como Gate; usada explícitamente en `MyTicketsController` para acciones del solicitante (view, create, alert, comment, attach, cancel).

### Middleware de seguridad y auditoría

- **EnsureSessionForAuth**: prepend en API para que login/logout/register tengan sesión disponible.
- **EnforcePasswordChange**: obliga cambio de contraseña cuando `force_password_change` está activo.
- **SecurityHeaders**: opcional vía `config('security.headers_enabled')`; añade X-Content-Type-Options, X-Frame-Options, Referrer-Policy.
- **AuditReportAccess** (alias `report.audit`): en rutas de reportes/analytics (ej. tickets/analytics, tickets/summary, tickets/export); si `config('helpdesk.reports.audit_enabled')` es true, escribe en un canal de log (reports) información de acceso (user, ruta, filtros, IP, etc.).

### Auditoría de tickets

- **TicketObserver**: en created/updated/deleted/restored de `Ticket` escribe en `ticket_audit_logs` (action, old_values, new_values, user_id, ip_address, user_agent). Activación por `config('helpdesk.tickets.audit_enabled')` y canal `audit`.
- **Export**: `TicketAuditExport` y endpoints `tickets/audit-logs`, `tickets/audit-export` para consulta y descarga de auditoría.

### Rate limiting

- Login: 10/min por IP; register: 5/min; tickets: 300/min por usuario o IP (evitar 429 en dashboards con muchas peticiones).

---

## 6. Estándares y Convenciones del Proyecto

- **Form Requests**: Validación en requests dedicados: `StoreTicketRequest`, `UpdateTicketRequest`; en SIGUA, `StoreCA01Request`, `StoreBitacoraRequest`, `StoreBitacoraBulkRequest`, `StoreIncidenteRequest`, `StoreCuentaGenericaRequest`, `UpdateCuentaGenericaRequest`, `ImportarArchivoRequest`.
- **Observers**: Un observer compartido por entidad cuando hace falta (ej. `TicketObserver` para auditoría); registrado en `AppServiceProvider`.
- **Servicios**: Lógica de negocio aislada por módulo: `RequesterTicketService` (Helpdesk); SIGUA: `ImportacionService`, `AlertaService`, `CA01Service`, `BitacoraService`, `CruceService`, `ReporteService`.
- **Rutas API**: Agrupación por dominio y middleware `auth:sanctum`, `locale`, `perm:...`; SIGUA en archivo aparte bajo prefijo `api`.
- **Frontend**:  
  - Rutas centralizadas en `Main.jsx` con lazy loading; layout único `AppLayout` con sidebar condicional por permisos.  
  - Contextos: `AuthContext` (user, login, logout, can, hasRole), `SidebarPositionContext`, `I18nProvider`.  
  - Componentes UI reutilizables en `components/ui/` (Radix + Tailwind); guards por módulo (ej. `TimeDeskGuard`).  
  - SIGUA: capa de hooks (`hooks/sigua/`), servicio API (`services/siguaApi.ts`), tipos TypeScript; páginas en `Pages/Sigua/`.  
  - Catálogos: consumo vía `GET /api/catalogs` y caché en cliente (`lib/catalogCache.js`).
- **Eventos/Listeners**: `TicketCreated` y `TicketUpdated` con `SendTicketNotification` (registrados en `AppServiceProvider`).
- **Internacionalización**: middleware `locale` en API; en frontend, `I18nProvider` y mensajes en `i18n/messages.js`.

---

## 7. Deuda Técnica Global y Áreas de Mejora

- **Resolbeb**: Nombre de carpeta con typo (`Resolbeb`); conviene alinear nombre con producto y evitar duplicación de vistas (Resolbeb vs Resolvev1) si ambas conviven de forma permanente.
- **Policies**: `RequesterTicketPolicy` no está registrada en Gate; el controlador la resuelve manualmente. Valorar registrar una policy “por contexto” o mantener explícito para no mezclar con `TicketPolicy`.
- **Catálogos**: El endpoint único `GET /api/catalogs` crece con cada módulo; valorar dividir por dominio o versionado para no sobrecargar respuesta y caché.
- **Frontend**: Mezcla de JSX y TypeScript (SIGUA en TS); unificar criterio de tipado (migrar a TS o mantener JS con JSDoc) mejora mantenibilidad.
- **Auditoría**: Solo tickets tienen observer y tabla de auditoría; incidencias y otros módulos no tienen el mismo nivel de trazabilidad.
- **Tests**: No se ha analizado cobertura; añadir tests de integración por módulo (tickets, incidencias, SIGUA, TimeDesk) y de políticas reduciría regresiones.
- **Documentación de permisos**: Lista de permisos y su asignación a roles está en seeders y migraciones; un documento único de matriz rol-permiso facilita onboarding y auditoría.
- **Rate limit tickets**: 300/min por usuario puede ser alto en entornos muy restringidos; hacer el límite configurable por entorno podría ayudar.

---

*Documento generado a partir del análisis del código fuente (configuración, rutas, modelos, controladores, observers, servicios, políticas, middleware, migraciones, seeders y frontend React). Referencias exactas a archivos y rutas corresponden al estado del repositorio analizado.*
