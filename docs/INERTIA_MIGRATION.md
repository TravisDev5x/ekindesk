# Migración frontend — SPA legacy → Inertia único

**Épica:** consolidar UI en `resources/js/inertia.jsx` + `resources/js/Inertia/Pages/`.  
**Estado:** lista para PR separado (antes de seguir épica multi-tenant).

## Arquitectura actual

| Antes | Ahora |
|--------|--------|
| `resources/js/app.jsx` + `resources/js/Pages/**` | Eliminado |
| `resources/views/app.blade.php` | Eliminado |
| Entrada Vite | `resources/js/inertia.jsx` |
| Plantilla Blade | `resources/views/inertia.blade.php` |
| Rutas web | `Inertia::render('Module/Page')` en `routes/web.php` |
| Páginas | `resources/js/Inertia/Pages/**/*.jsx` |
| Layout autenticado | `Inertia/Layouts/AuthenticatedLayout.jsx` |
| API | Sin cambios (`routes/api.php`, Axios + Sanctum) |

## Compatibilidad (bookmarks y enlaces viejos)

### Servidor — `routes/inertia_legacy.php`

Redirects 302 para URLs históricas (auth + públicas). Incluye:

- `/dashboard`, `/app` → `/home`
- `/tickets*`, `/resolvev1/*`, `/resolbeb/resolvev1/*` → `/resolbeb/*`
- `/ticket-states`, `/ticket-estados` → `/resolbeb/estados`
- `/incidentes` → `/incidents`
- `/audit-command-center` → `/audit-command`
- `/clientes` → `/clients` (en `web.php`)
- `/invitation/accept` → `/register/accept` (conserva query `token`)

### Cliente — `resources/js/lib/legacyRoutes.js`

`normalizeLegacyAppPath()` reescribe rutas en navegación SPA (Sidebar, `shouldUseInertiaLink`) para que un `<Link href="/tickets">` visite `/resolbeb/tickets` sin recarga completa.

## Checklist PR

- [ ] `npm run build` sin errores
- [ ] `php artisan test` verde
- [ ] Login → `/home` carga dashboard
- [ ] Bookmark `/tickets/123` redirige a `/resolbeb/tickets/123`
- [ ] Bookmark `/resolvev1/tickets` redirige a `/resolbeb/tickets`
- [ ] API sigue en `/api/*` (sin cambio de contrato)
- [ ] Landing `/` y auth (`/login`, `/register/accept`) OK

## Fuera de alcance de este PR

- Multi-tenant (fases 1.5–2.x en `docs/MULTITENANT_ROADMAP.md`)
- Renombrar “Resolvev1” en copy interno de componentes (solo rutas unificadas)
- Eliminar `routes/inertia_legacy.php` (mantener al menos 1 release)

## Añadir página Inertia nueva

1. Crear `resources/js/Inertia/Pages/Modulo/Page.jsx`
2. Ruta en `routes/web.php`: `Inertia::render('Modulo/Page')`
3. Añadir prefijo en `resources/js/lib/inertiaNavigation.js` si el menú usa `shouldUseInertiaLink`
4. Opcional: redirect legacy en `inertia_legacy.php` si sustituye URL antigua
