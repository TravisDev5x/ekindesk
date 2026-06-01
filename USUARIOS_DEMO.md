# Usuarios de prueba (seeders)

## Instalación estándar (`php artisan migrate:fresh --seed`)

| Campo | Valor |
|--------|--------|
| **Correo** | `admin@helpdesk.local` |
| **Nº empleado** | `ADMIN001` |
| **Contraseña** | `AdminHelpdesk2025!` |
| **Rol** | `admin` (todos los permisos vía `FakerFullSeeder`) |

Tras el login la app redirige a **`/home`** (dashboard por rol).

## Solo admin (`php artisan db:seed --class=AdminUserSeeder`)

Mismas credenciales que arriba.

## Demo con roles (`php artisan db:seed --class=TicketDemoSeeder`)

Contraseña común: **`Password123!`**

| Rol | Correo |
|-----|--------|
| Admin | `admin@demo.com` |
| Usuario | `ana@demo.com`, `luis@demo.com` |
| Agente soporte | `soporte@demo.com` |
| Supervisor | `supervisor@demo.com` |
| Infra / redes / apps / seguridad | `infra@demo.com`, `redes@demo.com`, `apps@demo.com`, `seguridad@demo.com` |

## Notas

- El login acepta **correo** o **número de empleado**.
- Rutas principales: `/home` (inicio), `/resolbeb` (dashboard operativo), `/resolbeb/tickets`, `/manual` (público).
