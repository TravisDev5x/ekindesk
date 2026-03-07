# Mejoras de experiencia en dispositivos móviles

## Ya implementado
- Barra inferior híbrida (Inicio, Mis tickets, Resolbeb, Más) en móvil.
- Menú lateral (Sheet) con cierre al navegar.
- Opción "Vista tablet/móvil" en el header para probar sin redimensionar.
- Viewport meta correcto (`width=device-width, initial-scale=1`).
- Inputs con `text-base` en móvil (evita zoom al enfocar en iOS).
- Padding inferior del contenido para no quedar bajo la barra flotante.
- Safe area en la barra inferior (`env(safe-area-inset-bottom)`).

---

## Recomendaciones pendientes (por prioridad)

### 1. Tablas con scroll horizontal (alto impacto)
En listados (Sedes, Áreas, Catálogos, etc.) las tablas se desbordan en pantallas estrechas.  
**Acción:** Envolver cada `<Table>` en un contenedor con scroll horizontal:

```jsx
<div className="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
  <Table>...</Table>
</div>
```

O usar un componente reutilizable `<TableScrollContainer>` que aplique lo anterior. Aplicar en todas las páginas que usan tabla (Sedes, Areas, Campaigns, Positions, Ubicaciones, Prioridades, ImpactLevels, UrgencyLevels, TicketEstados, TicketTipos, TicketMacros, IncidentTipos, IncidentSeveridades, IncidentEstados, Resolbeb Estados/Tipos).

### 2. Diálogos y modales en móvil (alto impacto)
- Que los `DialogContent` en móvil ocupen mejor la pantalla y permitan scroll si el contenido es largo (max-height + overflow-y-auto).  
- Opcional: en pantallas muy pequeñas, mostrar el diálogo como bottom sheet (Sheet desde abajo) en lugar de centrado.

### 3. Header en móvil (medio impacto)
- Reducir o ocultar la línea de breadcrumb (ej. "PANEL >") en móvil para ganar espacio.
- Asegurar que el título no se corte de forma rara (ya hay `truncate`).
- Añadir `padding-top: env(safe-area-inset-top)` al header en dispositivos con notch.

### 4. Áreas de toque (medio impacto)
- Botones e iconos clicables con mínimo ~44x44 px de área táctil (especialmente en listados: Editar, Eliminar, switches).
- En la barra inferior los ítems ya son razonables; revisar botones pequeños en tablas (por ejemplo `size="sm"` con `min-h-[44px]` o padding generoso en móvil).

### 5. Dropdowns (notificaciones, usuario, tema) (medio impacto)
- En móvil, que los menús desplegables tengan ancho útil (por ejemplo `w-[min(320px,calc(100vw-2rem))]` o similar) y no se salgan de la pantalla.
- Comprobar que el menú de usuario y notificaciones se posicionen bien (align end + side bottom) en viewport pequeño.

### 6. Formularios largos (medio impacto)
- En páginas como "Crear ticket", agrupar campos en pasos o acordeones en móvil para reducir scroll.
- Botón principal (Enviar/Guardar) fijo abajo o muy visible al final del formulario (evitar que quede oculto bajo la barra de navegación).

### 7. Paginación en móvil (bajo impacto)
- El componente de paginación puede ser denso; en móvil mostrar solo "Anterior", "Siguiente" y opcionalmente el número de página actual, en lugar de muchos números.

### 8. Pull-to-refresh (bajo impacto, opcional)
- En listados (mis tickets, tickets, incidencias), considerar pull-to-refresh para actualizar sin buscar un botón.

### 9. Gestos (opcional)
- Swipe entre pestañas o entre "Mis tickets" y "Todos" si en el futuro hay tabs; no prioritario.

---

## Resumen de tareas sugeridas
| Prioridad | Tarea |
|-----------|--------|
| Alta      | Envolver tablas en `overflow-x-auto` en todas las páginas de catálogos/listados. |
| Alta      | Diálogos: max-height + overflow-y en móvil (y opcional bottom sheet). |
| Media     | Header: safe-area top, simplificar breadcrumb en móvil. |
| Media     | Revisar tamaño táctil de botones en listados. |
| Media     | Dropdowns con ancho máximo en móvil. |
| Media     | Formularios largos: agrupar o fijar CTA. |
| Baja      | Paginación más compacta en móvil; pull-to-refresh opcional. |
