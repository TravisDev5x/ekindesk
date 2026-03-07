# Mejoras sugeridas para la versión móvil

Resumen de mejoras priorizadas para usar la app en móvil/tablet (testing masivo y uso en campo).

---

## Implementado (compatibilidad móvil)

- **Padding inferior:** Clase `.pb-content-mobile` en `app.css` (7rem en móvil, 2rem en md+). Aplicada en TicketDetalle, IncidentDetalle, TicketCreate, Profile, Settings, Resolvev1/Detalle.
- **Formularios:** Botones principales con `min-h-[44px] md:min-h-0` en detalle de ticket/incidencia y en TicketCreate; input asunto en TicketCreate con `min-h-[44px]`.
- **Detalle ticket/incidencia:** Barra de acciones fija en móvil (Comentar, Asignar, Estado) que hace scroll suave a la sección correspondiente; descripción con "Ver más" ya existente.
- **Navegación:** El menú lateral (Sheet) se cierra al elegir cualquier enlace (SidebarItem y GroupItem llaman `onNavigate`/`onToggle`). Indicador de notificaciones sin leer en el botón "Más" de la barra inferior (punto rojo cuando `unreadCount > 0`).
- **Safe area:** El contenido de `Dialog` usa `paddingBottom: max(1.5rem, env(safe-area-inset-bottom))` para no quedar cortado en dispositivos con notch/home indicator.

---

## 1. Tablas en listas (alta prioridad)

**Problema:** En páginas como Tickets, Incidents, Users, etc., la tabla tiene muchas columnas y en móvil se aprieta o se sale del viewport sin scroll horizontal claro.

**Sugerencias:**

- **Envolver la tabla en un contenedor con scroll horizontal** en viewports pequeños:
  ```jsx
  <div className="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
    <Table className="min-w-[800px]">...</Table>
  </div>
  ```
  Así en móvil se desliza horizontalmente sin romper el layout.

- **Vista alternativa en móvil (cards):** En pantallas &lt; 768px mostrar cada ítem como una **card** (folio, asunto, estado, prioridad, botón “Ver”) en lugar de fila de tabla. La tabla quedaría solo para `md:` y superior. Mejora mucho la lectura en pantalla pequeña.

- **Ocultar columnas secundarias en móvil:** Si se mantiene tabla, usar `hidden md:table-cell` en celdas menos críticas (ej. Ubicación, SLA) y dejar solo Folio, Asunto, Estado, Acción.

**Dónde aplicar:** `Tickets.jsx`, `Incidents.jsx`, `Users.jsx`, y cualquier página que use `DataTable` o `Table` con muchas columnas.

---

## 2. Padding inferior del contenido (media prioridad)

**Problema:** La barra inferior fija (`MobileBottomBar`) tapa el final del contenido. Ya existe `pb-32` en el `<main>` del `AppLayout`, pero algunas páginas internas usan solo `pb-8` o `pb-10` y pueden quedar cortadas.

**Sugerencia:** Revisar páginas con scroll largo (detalle de ticket, formularios, listas) y asegurar que el contenedor principal tenga en móvil al menos `pb-24 md:pb-8` (o usar una clase común tipo `content-padding-bottom`) para que el último botón o enlace no quede bajo la barra.

**Dónde revisar:** `TicketDetalle.jsx`, `IncidentDetalle.jsx`, `Users.jsx`, `Resolvev1/Detalle.jsx`, formularios de creación (TicketCreate, etc.).

---

## 3. Formularios y controles (media prioridad)

**Problema:** Algunos selects, botones o campos pueden ser pequeños para dedo en móvil.

**Sugerencias:**

- **Área táctil mínima ~44px:** Botones y enlaces importantes en móvil con `min-h-[44px] min-w-[44px]` o equivalente. Ya lo usas en `MobileBottomBar` y en algunos switches (ej. `IncidentSeveridades`); extender a botones primarios de formularios (Guardar, Enviar, Asignar).
- **Inputs y selects:** En móvil usar `min-h-[44px]` en `Input`, `SelectTrigger` y `Textarea` donde sea el foco principal (ej. búsqueda, descripción del ticket). Evita zoom forzado en iOS.
- **Filtros en listas:** En Tickets/Incidents los filtros son muchos en una sola fila. En móvil ya usas `flex-col`; opcional: agrupar filtros en un **Drawer/Sheet** “Filtros” que se abra con un botón, para ganar espacio y claridad.

---

## 4. Navegación y menú (baja prioridad)

**Estado actual:** Barra inferior con Home, Mis tickets, Resolbeb, Crear ticket/incidencia, Más; menú lateral con swipe para cerrar. Está bien resuelto.

**Sugerencias menores:**

- **Cerrar menú al navegar:** Si no está ya, cerrar el Sheet del menú al hacer clic en un enlace (evita tener que cerrar a mano).
- **Indicador de “Más”:** Si hay notificaciones sin leer, mostrar un punto o badge en el ítem “Más” de la barra inferior para llevar al usuario al menú (donde están notificaciones).

---

## 5. Detalle de ticket / incidencia (media prioridad)

**Problema:** Muchas cards y secciones; en móvil hay que hacer mucho scroll y a veces los botones de acción (Asignar, Macro, Comentar) quedan dispersos.

**Sugerencias:**

- **Barra de acciones fija en móvil:** En la parte inferior (encima de la safe area y de la barra de navegación), una barra con 2–3 acciones principales (ej. “Comentar”, “Asignar”, “Estado”) siempre visibles. El resto puede quedarse en un menú “⋮” o en la parte superior.
- **Acordeón para secciones largas:** Descripción larga, historial, comentarios: mostrar resumen y “Ver más” que expanda in-place o en un sheet, para reducir scroll inicial.
- **Botón “Volver”:** Ya tienes el botón atrás en el header; asegurar que sea siempre visible (sticky o fijo) en pantallas muy pequeñas si el contenido es largo.

---

## 6. Rendimiento y percepción en móvil (baja prioridad)

- **Skeleton y estados de carga:** Mantener skeletons en listas y detalle (ya los usas) para que no se vea “blanco” mientras carga.
- **Evitar scroll largo innecesario:** En listas con muchos ítems, la paginación actual está bien; opcional a futuro: infinite scroll solo si se prioriza uso móvil intensivo.
- **Toques y feedback:** En botones primarios, considerar `active:scale-[0.98]` o un estado visual claro al tocar (ya suele dar feedback el navegador).

---

## 7. Safe area y notches (ya cubierto)

- **Header:** `safe-area-header` con `padding-top: env(safe-area-inset-top)` en móvil.
- **Barra inferior:** `bottom: max(1rem, env(safe-area-inset-bottom))`.
- **Contenido:** El main con `pb-32` da espacio para la barra; revisar que los modales/dialogs en móvil también respeten `env(safe-area-inset-bottom)` al abrir en pantalla completa.

---

## Resumen de prioridades

| Prioridad | Mejora | Esfuerzo |
|-----------|--------|----------|
| Alta | Scroll horizontal en tablas (wrapper `overflow-x-auto` + `min-w`) | Bajo |
| Alta | Vista cards en móvil para listas (Tickets, Incidents) | Medio |
| Media | Padding inferior consistente en páginas largas | Bajo |
| Media | Área táctil 44px en botones/inputs clave de formularios | Bajo |
| Media | Barra de acciones fija en detalle ticket/incidencia | Medio |
| Baja | Filtros en Sheet en listas; cerrar menú al navegar | Bajo |

Si quieres, el siguiente paso puede ser implementar solo el **scroll horizontal en la tabla de Tickets** y el **padding inferior** en 2–3 páginas clave; con eso la versión móvil gana mucho para el testing masivo.
