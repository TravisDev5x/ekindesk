# Usuario administrador y datos demo (Faker)

El seed por defecto crea **un usuario administrador con todos los permisos**, catálogos base y, con **FakerFullSeeder**, **al menos 50 usuarios**, **50 tickets** y **50 incidencias** de ejemplo.

## Credenciales del administrador (ve todo)

| Campo | Valor |
|-------|--------|
| **Correo** | `admin@helpdesk.local` |
| **Número de empleado** | `ADMIN001` |
| **Contraseña** | `AdminHelpdesk2025!` |

Este usuario tiene **todos los permisos** (helpdesk, SIGUA, asistencias) y puede ver y gestionar todo.

## Roles creados (4)

| Rol | Descripción |
|-----|-------------|
| **admin** | Todos los permisos (helpdesk + SIGUA + asistencias). |
| **soporte** | Ver área, comentar, cambiar estado, asignar tickets, filtrar por sede. |
| **usuario** | Crear tickets y ver los propios. |
| **consultor** | Ver por área y ver propios. |

## Sedes (3)

- **Tlalpan** (código: TLALPAN)
- **Vallejo** (código: VALLEJO)
- **Toledo** (código: TOLEDO)

## Cómo ejecutar los seeders

**Vaciar base, migrar y sembrar todo (recomendado):**

```bash
php artisan migrate:fresh --seed
```

**Solo datos Faker (catálogos y admin ya existen):**

```bash
php artisan db:seed --class=FakerFullSeeder
```

**Solo usuario admin (mínimo):**

```bash
php artisan db:seed --class=FullDemoSeeder
```

## Qué genera el seeder por defecto

- **Catálogos**: campaña General, áreas, puestos, 3 sedes, prioridades y estados de ticket, tipos de ticket, catálogos de incidencias, ubicaciones por sede.
- **Roles y permisos**: admin (todos los permisos), soporte, usuario, consultor; permisos de SIGUA y asistencias.
- **1 admin**: `admin@helpdesk.local` / `ADMIN001` con **todos los permisos**.
- **FakerFullSeeder**: al menos **50 usuarios** (Faker), **50 tickets** (Faker), **50 incidencias** (Faker).
