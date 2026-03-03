# Reporte: Valores hardcodeados en el módulo de Tickets (Helpdesk)

**Objetivo:** Identificar números mágicos, strings quemados y malas prácticas en Backend (Laravel) y Frontend (React) del módulo de Tickets.

---

## Backend (Laravel)

### 1. Códigos de estado de ticket quemados en consultas

**Archivo:** `app/Http/Controllers/Api/TicketController.php`

**Fragmentos problemáticos:**

```php
$cancelStateId = TicketState::where('code', 'cancelado')->value('id');
if (!$cancelStateId) {
    $cancelStateId = TicketState::where('name', 'Cancelado')->value('id');
}
// (líneas 176-179, 888-891)
```

```php
$openStateId = TicketState::where('code', 'abierto')->value('id');
$progressStateId = TicketState::where('code', 'en_progreso')->value('id');
// (líneas 614-615)
```

**Problema:** Los códigos `'cancelado'`, `'abierto'`, `'en_progreso'` y el nombre `'Cancelado'` están repetidos en varios puntos. Si se cambia el código en BD o se traducen nombres, el código se rompe o queda inconsistente.

**Propuesta de refactorización:**

- En el modelo `TicketState` definir constantes de código (o un Enum PHP 8.1+):

```php
// app/Models/TicketState.php
class TicketState extends Model
{
    public const CODE_OPEN = 'abierto';
    public const CODE_IN_PROGRESS = 'en_progreso';
    public const CODE_CANCELED = 'cancelado';
    public const CODE_CLOSED = 'cerrado';
    public const CODE_RESOLVED = 'resuelto';

    public static function findIdByCode(string $code): ?int
    {
        return static::where('code', $code)->value('id');
    }
}
```

- En el controlador usar: `TicketState::findIdByCode(TicketState::CODE_CANCELED)` y eliminar el fallback por nombre o moverlo a un método `TicketState::getCancelStateId()` que encapsule la lógica.

---

### 2. Número mágico 72 (horas SLA) fuera del modelo

**Archivo:** `app/Http/Controllers/Api/TicketController.php`

**Fragmento problemático:**

```php
->where('created_at', '<=', now()->subHours(72))
// (línea 172, método summary())
```

**Archivo:** `app/Http/Controllers/Api/TicketAnalyticsController.php`

**Fragmento problemático:**

```php
->where('created_at', '<=', now()->subHours(72))
// (línea 67)
```

**Problema:** El valor `72` está duplicado. En `Ticket` ya existe `Ticket::SLA_LIMIT_HOURS = 72`, pero aquí se usa el literal.

**Propuesta de refactorización:**

- Reemplazar por: `now()->subHours(Ticket::SLA_LIMIT_HOURS)` en ambos archivos.
- Opcional: si el SLA debe ser configurable por entorno, usar `config('helpdesk.sla_hours', 72)` y definir la clave en `config/helpdesk.php`.

---

### 3. Mensajes de respuesta API en español hardcodeados

**Archivo:** `app/Http/Controllers/Api/TicketController.php`

**Fragmentos problemáticos (entre otros):**

```php
return response()->json(['message' => 'No autorizado'], 401);
return response()->json(['message' => 'Asigna tu área para acceder a tickets'], 403);
return response()->json(['message' => 'Indica la prioridad o el par Impacto y Urgencia para calcularla.'], 422);
return response()->json(['message' => 'No existe el estado Cancelado en el sistema'], 422);
return response()->json(['message' => 'Ticket cancelado por el solicitante'], ...);
// (múltiples líneas: 36, 42, 338, 893, 908, etc.)
```

**Problema:** Textos fijos en español imposibilitan i18n y mantenimiento centralizado.

**Propuesta de refactorización:**

- Usar archivos de idioma: `__('tickets.errors.unauthorized')`, `__('tickets.errors.area_required')`, etc.
- Crear `lang/es/tickets.php` (y `lang/en/tickets.php`) con claves como `errors.unauthorized`, `errors.no_cancel_state`, `history.created`, `history.canceled_by_requester`.
- En respuestas: `return response()->json(['message' => __('tickets.errors.unauthorized')], 401);`

---

### 4. Notificaciones con mensajes literales

**Archivo:** `app/Http/Controllers/Api/TicketController.php`

**Fragmentos problemáticos:**

```php
"Tu ticket #{$ticket->id} fue marcado como resuelto/cerrado."
// (línea 551)
"Se agregó un comentario al ticket #{$ticket->id}."
// (línea 567)
"Tu ticket #{$ticket->id} fue " . ($action === 'assigned' ? 'asignado' : 'reasignado');
// (línea 1128)
"Tu ticket #{$ticket->id} fue escalado"
// (línea 1162)
```

**Problema:** Textos de notificación en español y concatenados en el controlador.

**Propuesta de refactorización:**

- Mover los mensajes a las clases de notificación (o a `lang/`) y usar traducción con parámetros: `__('tickets.notifications.resolved', ['id' => $ticket->id])`.
- Las notificaciones deberían construir el mensaje en su método `toMail()`/`toArray()` usando `__()` para soportar idioma del usuario.

---

### 5. Nota de historial "Creación de ticket" y "Ticket cancelado por el solicitante"

**Archivos:** `app/Http/Controllers/Api/TicketController.php`, `app/Http/Controllers/Api/MyTicketsController.php`, `app/Services/RequesterTicketService.php`

**Fragmentos problemáticos:**

```php
'note' => 'Creación de ticket',
'note' => 'Ticket cancelado por el solicitante',
```

**Problema:** Strings fijos para notas de historial; no se traducen y se repiten en varios archivos.

**Propuesta de refactorización:**

- Definir constantes en el modelo `TicketHistory` o en un enum: `TicketHistory::NOTE_CREATED`, `TicketHistory::NOTE_CANCELED_BY_REQUESTER`.
- O usar traducción: `'note' => __('tickets.history.created')` y `__('tickets.history.canceled_by_requester')`.

---

### 6. FormRequest con mensajes de validación en español

**Archivo:** `app/Http/Requests/StoreTicketRequest.php` (y equivalente en `UpdateTicketRequest.php` si aplica)

**Fragmento problemático:**

```php
public function messages(): array
{
    return [
        'subject.required' => 'El asunto es obligatorio.',
        'area_origin_id.required' => 'El área de origen es obligatoria.',
        // ... todos los mensajes en español literal
    ];
}
```

**Problema:** Mensajes de validación hardcodeados; Laravel ya soporta `lang/validation.php` y atributos en `lang/`.

**Propuesta de refactorización:**

- Usar `lang/es/validation.php` con claves personalizadas (p. ej. `custom.ticket.subject.required`) o `lang/es/tickets.php` con sección `validation`.
- En el FormRequest: `'subject.required' => __('tickets.validation.subject_required')`, o definir en `validation.php` y usar atributos en `attributes` para reemplazos automáticos.

---

### 7. Servicio RequesterTicketService – mismo patrón de estado cancelado

**Archivo:** `app/Services/RequesterTicketService.php`

**Fragmento problemático:**

```php
$cancelStateId = TicketState::where('code', 'cancelado')->value('id')
    ?? TicketState::where('name', 'Cancelado')->value('id');
if (! $cancelStateId) {
    throw new \RuntimeException('No existe el estado Cancelado en el sistema');
}
'note' => 'Ticket cancelado por el solicitante',
```

**Problema:** Misma lógica y mismos literales que en el controlador; duplicación y sin uso de constantes/traducciones.

**Propuesta de refactorización:**

- Centralizar en `TicketState::getCancelStateId(): int` (con excepción si no existe) y usar constantes para el código.
- Mensaje de excepción y nota de historial vía `__('tickets...')`.

---

## Frontend (React)

### 1. Código de estado "abierto" para estado inicial

**Archivos:** `resources/js/Pages/TicketCreate.jsx`, `resources/js/Pages/Resolbeb/Create.jsx`, `resources/js/Pages/Resolbeb/Resolvev1/Create.jsx`, `resources/js/Pages/Resolbeb/Index.jsx`, `resources/js/Pages/Tickets.jsx`, `resources/js/Pages/Dashboard.jsx`

**Fragmento problemático (repetido en varios):**

```javascript
const openState = catalogs.ticket_states.find((s) => (s.code || "").toLowerCase() === "abierto") || catalogs.ticket_states[0];
```

**Problema:** El código `"abierto"` está quemado en varios componentes. Si el backend cambia el código o se usa otro idioma en catálogo, la lógica falla.

**Propuesta de refactorización:**

- Definir una constante en un módulo compartido, por ejemplo `constants/ticketStateCodes.js`: `export const TICKET_STATE_CODE_OPEN = 'abierto';`
- O que el API de catálogos devuelva un campo `default_initial_state_id` para nuevos tickets y el frontend use ese ID en lugar de buscar por código.
- Reemplazar en todos los archivos el literal por la constante (o por el ID devuelto por el backend).

---

### 2. Códigos de estado para estilos/iconos (badges)

**Archivo:** `resources/js/Pages/Tickets.jsx`

**Fragmento problemático:**

```javascript
if (["abierto", "en_progreso", "asignado"].includes(code)) {
    config = { icon: <Ticket />, styles: "bg-blue-500/10 ..." };
} else if (["resuelto", "cerrado"].includes(code)) {
    config = { icon: <CheckCircle2 />, styles: "bg-emerald-500/10 ..." };
} else if (code.includes("cancel") || code.includes("rechaz")) {
    config = { icon: <AlertCircle />, styles: "bg-red-500/10 ..." };
}
```

**Problema:** Listas de códigos y substrings (`"cancel"`, `"rechaz"`) hardcodeados. Cualquier cambio de código o nuevo estado requiere tocar este archivo y puede ser frágil.

**Propuesta de refactorización:**

- Mapeo por código en constante: `const STATE_STYLE_MAP = { abierto: { variant: 'open' }, en_progreso: { variant: 'open' }, ... };` y que el catálogo de estados (o el API) pueda indicar `ui_variant: 'open' | 'resolved' | 'canceled'` para no depender de nombres.
- O centralizar en un hook `useTicketStateStyle(code)` que use constantes de códigos y devuelva `icon` y `className`.

---

### 3. Títulos y etiquetas de UI en español

**Archivos:** `resources/js/Pages/TicketCreate.jsx`, `resources/js/Pages/TicketDetalle.jsx`, `resources/js/Pages/Tickets.jsx`, etc.

**Fragmentos problemáticos (ejemplos):**

```jsx
<Label>Impacto <span className="text-destructive">*</span></Label>
<Label>Urgencia <span className="text-destructive">*</span></Label>
<Label>Prioridad (calculada)</Label>
notify.error("Completa todos los campos obligatorios (incl. Impacto y Urgencia)");
title="Cancelados"
hint="Sin atención > 72h"
placeholder="Selecciona Impacto y Urgencia"
```

**Problema:** Textos de interfaz fijos en español; no hay i18n en el frontend.

**Propuesta de refactorización:**

- Introducir sistema de traducción (react-i18next, react-intl o el que use el proyecto) y archivos de idioma (p. ej. `es/tickets.json`).
- Reemplazar por claves: `t('tickets.labels.impact')`, `t('tickets.labels.priority_calculated')`, `t('tickets.messages.required_fields')`, `t('tickets.summary.canceled')`, `t('tickets.hints.sla_over_72h')`.
- Placeholders y mensajes de validación también desde traducciones.

---

### 4. Número 72 en hint de SLA

**Archivo:** `resources/js/Pages/Tickets.jsx`

**Fragmento problemático:**

```jsx
hint="Sin atención > 72h"
```

**Problema:** El valor 72 está quemado; debería alinearse con el SLA del backend (p. ej. `Ticket::SLA_LIMIT_HOURS`).

**Propuesta de refactorización:**

- Si el frontend tiene acceso a configuración (endpoint de config o env): usar ese valor, p. ej. `hint={t('tickets.hints.sla_over_hours', { hours: config.slaHours ?? 72 })}`.
- Si no hay API de config, al menos extraer a constante en el mismo archivo o en `constants/tickets.js`: `const SLA_HOURS = 72;` y usarla en el hint y en cualquier otro lugar que muestre “72h”.

---

### 5. URLs de API escritas como strings completos

**Archivos:** `resources/js/Pages/TicketCreate.jsx`, `resources/js/Pages/TicketDetalle.jsx`, `resources/js/Pages/Tickets.jsx`, `resources/js/Pages/Resolbeb/Detalle.jsx`, etc.

**Fragmentos problemáticos:**

```javascript
axios.post("/api/tickets", payload);
axios.get(`/api/tickets/${id}`);
axios.put(`/api/tickets/${id}`, ...);
axios.post(`/api/tickets/${id}/cancel`);
axios.get("/api/ticket-macros", { params: { active_only: 1 } });
```

**Problema:** Las rutas están repetidas como strings. Si la base URL de la API cambia (prefijo, versión), hay que tocar muchos archivos. El proyecto ya usa una instancia de Axios con `baseURL: "/"`, por lo que las rutas son relativas; el riesgo es menor pero la repetición sigue siendo deuda.

**Propuesta de refactorización:**

- Centralizar rutas en un módulo: `api/tickets.js` con funciones como `getTickets(params)`, `getTicket(id)`, `createTicket(payload)`, `updateTicket(id, payload)`, `cancelTicket(id)`, `getTicketMacros(params)`. Cada función usa `axios.get/post/put(...)` con la ruta definida una sola vez.
- Opcional: si en el futuro la API tuviera otro origen, usar `import.meta.env.VITE_API_BASE_URL` (o similar) y construir las rutas ahí.

---

### 6. Enlace de descarga de adjuntos con URL absoluta

**Archivo:** `resources/js/Pages/TicketDetalle.jsx`

**Fragmento problemático:**

```jsx
href={`/api/tickets/${id}/attachments/${a.id}/download`}
```

**Problema:** URL construida a mano; si la API cambia de prefijo o se usa otro dominio, el enlace queda roto.

**Propuesta de refactorización:**

- Usar la misma base que Axios (p. ej. una variable `API_BASE = ''` o desde env) y construir la URL con una función helper: `getAttachmentDownloadUrl(ticketId, attachmentId)`.
- O exponer la URL de descarga desde el API en el objeto del adjunto (`url` o `download_url`) y usar ese valor en el `href`.

---

## Resumen de prioridades sugeridas

| Prioridad | Área        | Acción principal                                                                 |
|----------|---------------------------------------------------------------------------------------------|
| Alta     | Backend     | Introducir constantes/enum de códigos de estado en `TicketState` y usarlas en controladores y servicios. |
| Alta     | Backend     | Sustituir el literal `72` por `Ticket::SLA_LIMIT_HOURS` en `TicketController` y `TicketAnalyticsController`. |
| Media    | Backend     | Extraer mensajes de API y notificaciones a `lang/` y usar `__()`.                            |
| Media    | Frontend    | Centralizar código de estado "abierto" (y similares) en constantes o en respuesta del API.  |
| Media    | Frontend    | Extraer textos de UI a i18n (títulos, labels, placeholders, mensajes de error).              |
| Baja     | Frontend    | Centralizar rutas de API en un módulo de servicios y usar constante/env para el hint de 72h. |

---

*Reporte generado por revisión de código del módulo de Tickets (Helpdesk).*
