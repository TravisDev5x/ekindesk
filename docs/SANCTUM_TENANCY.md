# Sanctum, sesión y subdominios (Laragon / producción)

Tikara usa **cookies de sesión** (no Bearer JWT) para la SPA Inertia + Axios.

## Variables clave

```env
APP_URL=http://tikara.test

# Dominio base de portales: https://{portal_slug}.tikara.test
TENANCY_BASE_DOMAIN=tikara.test
TENANCY_PORTAL_SCHEME=http

# Cookies de sesión (ver abajo)
SESSION_DRIVER=database
SESSION_DOMAIN=
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,tikara.test
```

## Laragon (desarrollo local)

### 1. Hosts (`C:\Windows\System32\drivers\etc\hosts`)

```
127.0.0.1 tikara.test
127.0.0.1 cliente-a.tikara.test
127.0.0.1 cliente-b.tikara.test
```

Añade un host por cada `clients.portal_slug` que pruebes.

### 2. Virtual host / document root

Apunta el vhost de Laragon a `public/` del proyecto con ServerName:

- `tikara.test` (consola MSP)
- `*.tikara.test` si tu stack lo soporta, o un vhost por slug

Alternativa rápida sin wildcard DNS:

```bash
php artisan serve --host=tikara.test --port=8000
```

(y hosts apuntando a 127.0.0.1)

### 3. `.env` recomendado (Laragon)

```env
APP_URL=http://tikara.test

TENANCY_BASE_DOMAIN=tikara.test
TENANCY_PORTAL_SCHEME=http
TENANCY_STRICT_CLIENT_PORTAL=true
TENANCY_ENFORCE_SUBDOMAIN=true

SESSION_DRIVER=database
SESSION_DOMAIN=.tikara.test
SANCTUM_STATEFUL_DOMAINS=tikara.test,localhost,127.0.0.1,cliente-a.tikara.test,cliente-b.tikara.test

# Vite (evitar CORS)
# VITE_DEV_SERVER_HOST=tikara.test
```

**`SESSION_DOMAIN=.tikara.test`** (punto inicial): la cookie de sesión es válida en `tikara.test` y en `*.tikara.test`.  
Si dejas `null`, cada subdominio tendrá su **propia** sesión (más aislado, pero el operador debe volver a iniciar sesión en cada portal).

### 4. Sanctum stateful

`config/sanctum.php` usa `SANCTUM_STATEFUL_DOMAINS`. Debe listar **cada host** desde el que el navegador llama a `/api/*` (sin esquema ni puerto, salvo que uses puerto en la URL).

Ejemplo con `php artisan serve` en puerto 8000:

```env
SANCTUM_STATEFUL_DOMAINS=tikara.test:8000,cliente-a.tikara.test:8000,localhost,127.0.0.1
```

### 5. CSRF

El frontend llama `GET /sanctum/csrf-cookie` antes de `POST /api/login`. Mismo dominio o `SESSION_DOMAIN` compartido.

### 6. HTTPS en Laragon (opcional)

Con certificado local:

```env
APP_URL=https://tikara.test
TENANCY_PORTAL_SCHEME=https
SESSION_SECURE_COOKIE=true
```

## Producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.tuempresa.com

TENANCY_BASE_DOMAIN=tuempresa.com
TENANCY_PORTAL_SCHEME=https
TENANCY_STRICT_CLIENT_PORTAL=true

SESSION_DRIVER=database
SESSION_DOMAIN=.tuempresa.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=app.tuempresa.com,cliente1.tuempresa.com,cliente2.tuempresa.com
```

### DNS

- Registro **A/AAAA** para `app.tuempresa.com` (MSP)  
- **Wildcard** `*.tuempresa.com` → mismo balanceador, o CNAME por cliente  

### Cookies entre MSP y portales

| `SESSION_DOMAIN` | Comportamiento |
|------------------|----------------|
| `.tuempresa.com` | Una sesión en app y subdominios (operador entra a portales sin re-login) |
| `null` / host exacto | Sesión **independiente** por subdominio (más aislamiento) |

### CORS / Vite

En producción no hay Vite dev server; assets compilados con `npm run build`.  
`APP_URL` debe coincidir con el host que ve el usuario.

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---------|--------|----------|
| 401 en `/api/*` tras login | Dominio no en `SANCTUM_STATEFUL_DOMAINS` | Añadir host exacto |
| CSRF 419 | Cookie no enviada | `withCredentials: true`, mismo sitio o `SESSION_DOMAIN` |
| Login OK pero 403 en app | Portal incorrecto | URL del `portal_slug` del usuario |
| Sesión no cruza subdominio | `SESSION_DOMAIN` vacío | Usar `.tikara.test` si lo deseas |

## Referencias

- `docs/CLIENT_PORTAL.md` — portal por empresa  
- `docs/DATABASE_TENANCY.md` — RLS PostgreSQL  
- `config/tenancy.php` — flags tenant  
