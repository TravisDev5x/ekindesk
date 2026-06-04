# Sanctum, sesión y subdominios (Laragon / producción)

EkinDesk usa **cookies de sesión** (no Bearer JWT) para la SPA Inertia + Axios.

## Variables clave

```env
APP_URL=http://ekindesk.test

# Dominio base de portales: https://{portal_slug}.ekindesk.test
TENANCY_BASE_DOMAIN=ekindesk.test
TENANCY_PORTAL_SCHEME=http

# Cookies de sesión (ver abajo)
SESSION_DRIVER=database
SESSION_DOMAIN=
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,ekindesk.test
```

## Laragon (desarrollo local)

### 1. Hosts (`C:\Windows\System32\drivers\etc\hosts`)

```
127.0.0.1 ekindesk.test
127.0.0.1 cliente-a.ekindesk.test
127.0.0.1 cliente-b.ekindesk.test
```

Añade un host por cada `clients.portal_slug` que pruebes.

### 2. Virtual host / document root

Apunta el vhost de Laragon a `public/` del proyecto con ServerName:

- `ekindesk.test` (consola MSP)
- `*.ekindesk.test` si tu stack lo soporta, o un vhost por slug

Alternativa rápida sin wildcard DNS:

```bash
php artisan serve --host=ekindesk.test --port=8000
```

(y hosts apuntando a 127.0.0.1)

### 3. `.env` recomendado (Laragon)

```env
APP_URL=http://ekindesk.test

TENANCY_BASE_DOMAIN=ekindesk.test
TENANCY_PORTAL_SCHEME=http
TENANCY_STRICT_CLIENT_PORTAL=true
TENANCY_ENFORCE_SUBDOMAIN=true

SESSION_DRIVER=database
SESSION_DOMAIN=.ekindesk.test
SANCTUM_STATEFUL_DOMAINS=ekindesk.test,localhost,127.0.0.1,cliente-a.ekindesk.test,cliente-b.ekindesk.test

# Vite (evitar CORS)
# VITE_DEV_SERVER_HOST=ekindesk.test
```

**`SESSION_DOMAIN=.ekindesk.test`** (punto inicial): la cookie de sesión es válida en `ekindesk.test` y en `*.ekindesk.test`.  
Si dejas `null`, cada subdominio tendrá su **propia** sesión (más aislado, pero el operador debe volver a iniciar sesión en cada portal).

### 4. Sanctum stateful

`config/sanctum.php` usa `SANCTUM_STATEFUL_DOMAINS`. Debe listar **cada host** desde el que el navegador llama a `/api/*` (sin esquema ni puerto, salvo que uses puerto en la URL).

Ejemplo con `php artisan serve` en puerto 8000:

```env
SANCTUM_STATEFUL_DOMAINS=ekindesk.test:8000,cliente-a.ekindesk.test:8000,localhost,127.0.0.1
```

### 5. CSRF

El frontend llama `GET /sanctum/csrf-cookie` antes de `POST /api/login`. Mismo dominio o `SESSION_DOMAIN` compartido.

### 6. HTTPS en Laragon (opcional)

Con certificado local:

```env
APP_URL=https://ekindesk.test
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
| Sesión no cruza subdominio | `SESSION_DOMAIN` vacío | Usar `.ekindesk.test` si lo deseas |

## Referencias

- `docs/CLIENT_PORTAL.md` — portal por empresa  
- `docs/DATABASE_TENANCY.md` — RLS PostgreSQL  
- `config/tenancy.php` — flags tenant  
