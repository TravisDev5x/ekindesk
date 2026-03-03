# Auditoría ITSM – Módulo de Tickets (HelpDesk)

**Fecha:** 2025-03  
**Alcance:** Ticketera interna (módulo Resolbeb / tickets). Omnicanalidad excluida.  
**Objetivo:** Comparar el estado actual con mejores prácticas de una ticketera profesional y proponer cierre de brechas.

---

## 1. Ciclo de vida del ticket

### 1.1 ¿Qué existe hoy?

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Estados configurables | ✅ Implementado | Tabla `ticket_states`: `name`, `code`, `is_active`, `is_final` |
| Estados más allá de Abierto/Cerrado | ✅ Implementado | Seeder: Abierto, En progreso, **En espera**, Resuelto, Cerrado, Cancelado |
| Marcado de estado final | ✅ Implementado | `is_final` en `ticket_states`; Cerrado y Cancelado son finales |
| Transición a “resuelto” | ✅ Implementado | En `TicketController::update`, al pasar a estado con `is_final` se setea `resolved_at` |
| Historial de cambios de estado | ✅ Implementado | `ticket_histories` con `ticket_state_id`, `action` (state_change, assigned, etc.) |

**Conclusión:** El ciclo de vida está bien cubierto. Los estados son configurables y se usan “En espera” y “Resuelto” de forma coherente. No es obligatorio tener un estado explícito “En espera de terceros”; “En espera” puede usarse para eso.

---

## 2. Matriz de prioridad (Impacto × Urgencia)

### 2.1 ¿Qué existe hoy?

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Prioridad en el ticket | ✅ Implementado | `tickets.priority_id` → `priorities` |
| Catálogo de prioridades | ✅ Implementado | Tabla `priorities`: `name`, `level` (1=Crítica … 4=Baja), `is_active` |
| Cálculo por Impacto/Urgencia | ❌ No existe | Prioridad es **selección manual**; no hay impacto ni urgencia |

**Conclusión:** La prioridad es un campo de selección simple. No hay matriz Impacto × Urgencia ni cálculo automático de prioridad.

### 2.2 Propuesta de implementación

**Opción A – Matriz en BD (recomendada para ITSM):**

1. **Migración**
   - Tabla `impact_levels`: `id`, `name`, `code`, `weight` (ej. 1–4), `is_active`.
   - Tabla `urgency_levels`: `id`, `name`, `code`, `weight` (ej. 1–4), `is_active`.
   - Tabla `priority_matrix`: `impact_level_id`, `urgency_level_id`, `priority_id` (FK a `priorities`). Unique `(impact_level_id, urgency_level_id)`.
   - En `tickets`: añadir `impact_level_id` y `urgency_level_id` (nullable al inicio). Mantener `priority_id`; puede rellenarse por matriz o manual.

2. **Lógica**
   - Al guardar/actualizar ticket: si existen `impact_level_id` y `urgency_level_id`, calcular `priority_id` desde `priority_matrix` (y opcionalmente sobrescribir si el usuario elige “prioridad manual”).
   - API: en `StoreTicketRequest` / `UpdateTicketRequest` aceptar `impact_level_id`, `urgency_level_id`; en controller resolver `priority_id` desde la matriz si aplica.

3. **Frontend**
   - En formulario de ticket (Resolbeb/Create, Resolbeb/Detalle, TicketCreate, TicketDetalle): selects Impacto y Urgencia; al elegir ambos, actualizar prioridad (o mostrar prioridad sugerida y permitir override).
   - Catálogos: `GET /api/impact-levels`, `GET /api/urgency-levels` (o incluir en catálogos existentes).

**Opción B – Solo impacto/urgencia sin matriz:**  
Añadir solo `impact_level_id` y `urgency_level_id` en `tickets` y mostrarlos en UI/reportes; la prioridad sigue siendo manual. Útil como paso intermedio.

---

## 3. Gestión de SLAs

### 3.1 ¿Qué existe hoy?

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Fecha límite (resolución) | ✅ Implementado | `tickets.due_at`; por defecto `created_at + 72h` (`Ticket::SLA_LIMIT_HOURS`) |
| Tiempo de resolución | ✅ Implementado | `tickets.resolved_at`; atributos `is_overdue`, `sla_due_at`, `sla_status_text` en modelo `Ticket` |
| Filtros por SLA en listado | ✅ Implementado | `TicketController::applyCatalogFilters`: `sla=overdue` \| `within` |
| Tiempo de primera respuesta | ❌ No existe | No hay campo `first_response_at` ni lógica que lo setee |

**Conclusión:** SLA de **resolución** está cubierto (due_at, resolved_at, indicadores y filtros). Falta **tiempo de primera respuesta** (First Response Time).

### 3.2 Propuesta de implementación – Primera respuesta

1. **Migración**
   - En `tickets`: `first_response_at` (timestamp nullable).

2. **Lógica**
   - Definir “primera respuesta” = primer comentario **público** (visible al solicitante) en el ticket. Es decir, primer `TicketHistory` con `action = 'comment'` e `is_internal = false` (y opcionalmente `created_at` no nulo).
   - En `TicketController::update`, al guardar un comentario con `is_internal = false`: si `ticket.first_response_at` es null, setear `first_response_at = now()` y guardar el ticket dentro del mismo transaction.

3. **API / Reportes**
   - Incluir `first_response_at` en respuesta de `GET /api/tickets/:id`.
   - Para reportes: tiempo hasta primera respuesta = `first_response_at - created_at`; tiempo hasta resolución = `resolved_at - created_at` (ya disponible).

4. **Frontend**
   - En detalle de ticket, mostrar “Primera respuesta: &lt;fecha/hora&gt;” si existe; opcionalmente “Tiempo hasta primera respuesta: X h”.

---

## 4. Productividad del técnico

### 4.1 Notas internas

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Comentarios internos (ocultos al usuario) | ✅ Implementado | `ticket_histories.is_internal`; `action = 'comment'` |
| UI: checkbox “Nota interna” | ✅ Implementado | Resolbeb/Detalle y TicketDetalle: `isInternalNote`, envío `is_internal: true` |
| Ocultar internas al solicitante | ✅ Implementado | `TicketController::show`: si el usuario es el solicitante, filtra historial con `action === 'comment' && is_internal` |
| Sección “Notas internas” en detalle | ✅ Implementado | Frontend separa `internalNoteEntries` y las muestra solo a agentes |

**Conclusión:** Notas internas están implementadas y alineadas con buenas prácticas.

### 4.2 Respuestas predefinidas (Macros)

| Elemento | Estado |
|----------|--------|
| Tabla o entidad de macros/plantillas | ❌ No existe |
| API de macros | ❌ No existe |
| UI: insertar plantilla en comentario | ❌ No existe |

**Conclusión:** No hay respuestas predefinidas (macros).

### 4.3 Propuesta de implementación – Macros

1. **Migración**
   - Tabla `ticket_response_templates` (o `ticket_macros`):  
     `id`, `name`, `content` (text), `category` (nullable string, ej. “Cierre”, “Espera de usuario”), `is_active`, `created_at`, `updated_at`.  
     Opcional: `area_id` nullable para macros por área.

2. **Backend**
   - Modelo `TicketResponseTemplate` (o `TicketMacro`).
   - `TicketResponseTemplateController`: `index` (filtros por categoría/área), `store`, `update`, `destroy`. Policy solo para roles con permiso de comentar/gestión.
   - No es necesario “aplicar macro” en backend: el frontend obtiene la plantilla y envía el texto como comentario normal (opcionalmente marcando en metadata que se usó una macro, si se desea analítica).

3. **Frontend**
   - En pantalla de detalle de ticket (área de comentarios): desplegable o botón “Insertar plantilla” que abre lista de macros; al elegir una, rellenar el textarea con `content` (y opcionalmente reemplazar placeholders tipo `{{solicitante}}` si se añade luego).
   - Permiso sugerido: mismo que para comentar en ticket.

---

## 5. Vinculación de activos (CMDB / inventario)

### 5.1 ¿Qué existe hoy?

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Sede / ubicación del ticket | ✅ Implementado | `tickets.sede_id`, `tickets.ubicacion_id` |
| Relación con activo/equipo/CI | ❌ No existe | No hay tabla de inventario ni FK en `tickets` |

**Conclusión:** No hay CMDB ni vinculación ticket–activo.

### 5.2 Propuesta de implementación (opcional, fase posterior)

1. **Migración**
   - Tabla `assets` (o `configuration_items`):  
     `id`, `name`, `asset_tag` (único), `type` (ej. laptop, impresora, switch), `sede_id`, `ubicacion_id`, `area_id` (responsable), `user_id` (asignado a usuario, nullable), `is_active`, timestamps.  
     Ajustar nombres según convención del proyecto (ej. `locations` ya existe como `ubicaciones`).
   - En `tickets`: `asset_id` nullable, FK a `assets`.

2. **Modelo y API**
   - Modelo `Asset` con relaciones a `Sede`, `Ubicacion`, `Area`, `User`; relación `tickets()`.
   - En `Ticket`: `belongsTo(Asset::class)`.
   - Controlador CRUD de activos (index, show, store, update, destroy) con permisos tipo `assets.view` / `assets.manage`.
   - En `TicketController::show` (y listado si se desea): incluir `asset` en el load. En store/update aceptar `asset_id` opcional.

3. **Frontend**
   - En formulario de creación/edición de ticket: select opcional “Activo/Equipo afectado” (buscar por etiqueta o nombre). En detalle, mostrar enlace al activo y datos básicos (nombre, etiqueta, ubicación).

---

## 6. Auditoría (logs de cambios)

### 6.1 ¿Qué existe hoy?

| Elemento | Estado | Ubicación |
|----------|--------|-----------|
| Historial por ticket | ✅ Implementado | `ticket_histories`: actor, acción, estado, área origen/destino, asignado origen/destino, nota, is_internal |
| Registro en cada cambio relevante | ✅ Implementado | Creación, update (estado/prioridad/área/comentario), take, assign, unassign, cancel, escalate, alerta: todos crean fila en `ticket_histories` |
| Auditoría adicional (log) | ✅ Parcial | `auditTicketChange()` escribe a **Log::channel** (archivo/syslog), no a tabla. No hay consultas SQL por auditoría |

**Conclusión:** La auditoría “operativa” (quién hizo qué en cada ticket) está en `ticket_histories` y es consultable por ticket. La “auditoría” extra es solo log en canal configurable (`helpdesk.tickets.audit_channel`); no existe tabla de auditoría global para reportes o compliance.

### 6.2 Propuesta de implementación (si se requiere auditoría en BD)

1. **Migración**
   - Tabla `ticket_audit_log`:  
     `id`, `ticket_id`, `actor_id`, `action` (string: update, assign, cancel, escalate, …), `changes` (JSON: pares from/to por campo), `meta` (JSON opcional), `created_at`.  
     Índices: `ticket_id`, `actor_id`, `action`, `created_at`.

2. **Lógica**
   - Sustituir o complementar la llamada a `Log::channel()` en `auditTicketChange()` por un `TicketAuditLog::create([...])` con los mismos datos (actor, ticket_id, action, changes, meta). Mantener el log a archivo opcional para trazabilidad en archivos.

3. **Uso**
   - Reportes “actividad por técnico”, “cambios por ticket”, “acciones por período” vía consultas a `ticket_audit_log`.  
   - No sustituye a `ticket_histories`: historial sigue siendo la fuente de verdad de “qué pasó en el ticket”; la tabla de auditoría es para trazabilidad y reportes de cumplimiento.

---

## 7. Resumen ejecutivo

| Criterio | Estado actual | Brecha principal |
|----------|----------------|------------------|
| Ciclo de vida del ticket | ✅ Completo | Ninguna; estados configurables y uso correcto de finales. |
| Matriz de prioridad | ⚠️ Parcial | Prioridad manual; falta Impacto × Urgencia y cálculo automático. |
| SLA (resolución) | ✅ Completo | due_at, resolved_at, indicadores y filtros. |
| SLA (primera respuesta) | ❌ Falta | Campo `first_response_at` y lógica en primer comentario público. |
| Notas internas | ✅ Completo | is_internal en historial y UI. |
| Macros / respuestas predefinidas | ❌ Falta | Tabla, API y UI de plantillas. |
| Vinculación activos (CMDB) | ❌ Falta | Sin tabla de activos ni FK en tickets. |
| Auditoría en BD | ⚠️ Parcial | Historial por ticket completo; auditoría extra solo en log, no en tabla. |

Recomendación de orden de implementación para robustez operativa interna:

1. **Primera respuesta (SLA)** – poco esfuerzo, alto valor para métricas.
2. **Macros** – mejora directa de productividad del técnico.
3. **Matriz de prioridad** – mejora consistencia y reportes.
4. **Auditoría en tabla** – si se necesitan reportes de compliance o por técnico.
5. **Activos/CMDB** – cuando se defina inventario y política de uso.

---

*Documento generado a partir del análisis del código (Laravel + React) del proyecto HelpDesk. Convenciones y nombres de modelos/tablas siguen el estilo actual del proyecto.*
