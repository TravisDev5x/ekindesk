<div align="center">

> **Rama experimental** — Esta rama incluye módulos y cambios en evaluación. No está pensada para producción; usa `main` para releases estables.

# EkinDesk

**Plataforma de mesa de ayuda y gestión operativa para equipos de soporte TI**  
Modelo **MSP → empresas cliente** con aislamiento por tenant, portales por subdominio y control de acceso granular.

<br />

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9333EA?style=for-the-badge&logo=inertia&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-7-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)

<br />

![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Radix UI](https://img.shields.io/badge/Radix_UI-Components-161618?style=for-the-badge&logo=radixui&logoColor=white)
![Zod](https://img.shields.io/badge/Zod-Validation-3E63DD?style=for-the-badge&logo=zod&logoColor=white)

</div>

---

EkinDesk centraliza tickets, incidencias, catálogos, usuarios y permisos en una sola aplicación. Está pensado para MSPs y equipos internos que necesitan trazabilidad, auditoría y portales dedicados por organización.

---

## Características principales

| Área | Descripción |
|------|-------------|
| **Helpdesk / Resolbeb** | Ciclo completo de tickets: creación, asignación, prioridades, estados, comentarios, adjuntos, macros, wallboard y auditoría. |
| **Incidencias** | Flujo paralelo con tipos, severidades, estados y políticas de visibilidad por área o cliente. |
| **Multi-tenant MSP** | Operadores MSP con varios clientes; portales por subdominio (`portal_slug`); scopes en API y RLS opcional en PostgreSQL. |
| **RBAC** | Roles y permisos con [Spatie Laravel Permission](https://github.com/spatie/laravel-permission); políticas por ticket e incidencia. |
| **Invitaciones** | Flujo por correo: el invitado activa su cuenta y un administrador asigna el rol; soporte opcional de Google OAuth. |
| **Catálogos** | Prioridades, estados, tipos, áreas, sedes, campañas, matriz de prioridad y más — scope plataforma + operador MSP. |
| **Notificaciones y auditoría** | Alertas in-app, exportación de auditoría de tickets, monitor de sesiones y trazabilidad de cambios. |

---

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.2+, **Laravel 12**, Sanctum 4, Spatie Permission, Socialite (Google OAuth) |
| Frontend | **React 19**, **Inertia.js 3**, Vite 7, Tailwind CSS 4 |
| UI / datos | Radix UI, React Hook Form + Zod, Recharts, TanStack Table, Lucide Icons |
| Datos | MySQL / MariaDB, SQLite (dev/tests), PostgreSQL + RLS (staging/prod opcional) |
| Auth | Sesión stateful Sanctum (cookies), Google OAuth vía Socialite (opcional) |

---

## Arquitectura en una imagen

```
Navegador
   │
   ├─► Rutas web (Inertia)     →  inertia.jsx  →  Inertia/Pages/
   │         login, /home, /resolbeb/*, catálogos, incidencias…
   │
   └─► API REST /api/*         →  Axios + cookie Sanctum
             tickets, users, catalogs, notifications…
```

- **Un solo frontend:** entrada Vite `resources/js/inertia.jsx`; páginas en `resources/js/Inertia/Pages/`.
- **API desacoplada:** mutaciones y listados JSON bajo `/api/*` con middleware `auth:sanctum` y permisos `perm:*`.
- **Sesión:** `GET /check-auth` (web) valida la cookie; la API no sustituye ese handshake.

Detalle completo: [`ARCHITECTURE.md`](ARCHITECTURE.md).

---

## Requisitos

- PHP 8.2+
- Composer 2.x
- Node.js 20 LTS (o 18+)
- MySQL 8+ / MariaDB, o SQLite para desarrollo local
- Extensiones PHP: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` (y `zip` recomendado para Composer)

---

## Instalación rápida

```bash
git clone https://github.com/TravisDev5x/ekindesk.git
cd ekindesk

composer install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env` (SQLite o MySQL) y ejecuta:

```bash
php artisan migrate:fresh --seed
npm install
npm run build
```

### Desarrollo

```bash
composer dev
```

Arranca servidor Laravel, cola, logs (Pail) y Vite en paralelo.

Acceso local: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### Credenciales demo

Ver [`USUARIOS_DEMO.md`](USUARIOS_DEMO.md). Ejemplo tras seed completo:

| Campo | Valor |
|-------|--------|
| Correo | `admin@helpdesk.local` |
| Contraseña | `AdminHelpdesk2025!` |

El login acepta **correo** o **número de empleado**.

---

## Multi-tenant y portales (resumen)

Variables relevantes en `.env` (ver [`docs/SANCTUM_TENANCY.md`](docs/SANCTUM_TENANCY.md)):

```env
TENANCY_BASE_DOMAIN=ekindesk.test
TENANCY_STRICT_CLIENT_PORTAL=true
SESSION_DOMAIN=.ekindesk.test
SANCTUM_STATEFUL_DOMAINS=ekindesk.test,cliente-a.ekindesk.test,localhost
```

Cada empresa cliente puede tener un portal en `https://{portal_slug}.{base_domain}/login` con branding y aislamiento de datos.

Documentación:

| Documento | Contenido |
|-----------|-----------|
| [`docs/CLIENT_PORTAL.md`](docs/CLIENT_PORTAL.md) | Subdominios y portal |
| [`docs/DATABASE_TENANCY.md`](docs/DATABASE_TENANCY.md) | Modelo BD y scopes |
| [`docs/MULTITENANT_ROADMAP.md`](docs/MULTITENANT_ROADMAP.md) | Plan de trabajo |
| [`docs/INERTIA_MIGRATION.md`](docs/INERTIA_MIGRATION.md) | Frontend Inertia y URLs legacy |

---

## Comandos útiles

```bash
# Tests
php artisan test

# Estilo PHP
./vendor/bin/pint

# Verificar integridad client_id en tickets/sedes
php artisan tenant:client-id verify

# Sembrar catálogos al operador MSP (idempotente)
php artisan tenant:seed-catalogs {operator_user_id}

# Build producción frontend
npm run build
```

---

## Estructura del repositorio

```
app/                    Modelos, controladores API, servicios, políticas, middleware tenant
resources/js/
  inertia.jsx           Entrada Vite + Inertia
  Inertia/Pages/        Páginas por ruta web
  components/           UI compartida (sidebar, dashboards, formularios)
routes/
  web.php               Vistas Inertia + redirects legacy
  api.php               REST API
  inertia_legacy.php    Compatibilidad URLs antiguas
database/               Migraciones y seeders
docs/                   Tenancy, Sanctum, auditoría API, migración Inertia
tests/Feature/          Tests de tenant, catálogos, invitaciones, tickets
```

---

## Documentación complementaria

| Archivo | Descripción |
|---------|-------------|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Arquitectura multi-módulo |
| [`API_CONTRACT.md`](API_CONTRACT.md) | Contrato de endpoints API |
| [`INSTALACION.md`](INSTALACION.md) | Guía de instalación detallada |
| [`CLAUDE.md`](CLAUDE.md) | Convenciones para desarrollo asistido |
| [`USUARIOS_DEMO.md`](USUARIOS_DEMO.md) | Usuarios y contraseñas de prueba |

---

## Roadmap

Consulta el estado actual en [`docs/MULTITENANT_ROADMAP.md`](docs/MULTITENANT_ROADMAP.md). Líneas generales:

- Cierre de fugas tenant en API y CI con PostgreSQL + RLS
- Branding por portal y seed de catálogos por operador MSP
- Notificaciones en tiempo real y reportes avanzados
- Base de conocimiento (Knowledge Base)

---

## Licencia

Proyecto **Source Available**: el código es público para consulta y referencia. No está permitido copiarlo, redistribuirlo ni usarlo como base de otros productos sin autorización. Ver [`LICENSE`](LICENSE).

---

## Contacto

**Eric Rafael Pérez Hernández** — [github.com/TravisDev5x](https://github.com/TravisDev5x)

Consultas: eric_perez96@outlook.com
