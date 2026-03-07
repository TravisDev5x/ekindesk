# Probar la app en otro dispositivo de tu red local

Para que otro equipo (móvil, tablet, otra PC) acceda a la app dentro de la misma red WiFi/LAN.

---

## Testing masivo: local y otro dispositivo al mismo tiempo

**Una sola configuración** para usar la app en tu PC (helpdeskreact.test) y en otros dispositivos (por IP) sin cambiar el `.env`.

### Requisitos en `.env`

- `SESSION_DOMAIN=null`
- `SANCTUM_STATEFUL_DOMAINS=helpdeskreact.test,localhost,127.0.0.1,<TU_IP>,<TU_IP>:8000` (ej. `192.168.3.1,192.168.3.1:8000`)
- **No definir** `VITE_DEV_SERVER_HOST` ni `VITE_HMR_HOST` → Laravel usa el host de cada petición y los scripts se sirven del origen correcto.

### En la PC (3 procesos)

1. **Laragon** encendido (para `helpdeskreact.test`).
2. **Laravel en red:**  
   `php artisan serve --host=0.0.0.0 --port=8000`
3. **Vite:**  
   `npm run dev`  
   (en `vite.config.js` ya está `host: true`, así que escucha en toda la red en el puerto 5173).

### URLs

| Quién        | URL                        |
|-------------|----------------------------|
| **Tú (local)** | `http://helpdeskreact.test` |
| **Otro dispositivo (misma WiFi)** | `http://<TU_IP>:8000` (ej. `http://192.168.3.1:8000`) |

Mismo `.env`, sin tocar nada al alternar entre local y otro dispositivo.

---

## 1. Obtener la IP de tu PC en la red

En la **máquina donde corre el proyecto**:

- **Windows (PowerShell):** `ipconfig` y busca "Dirección IPv4" del adaptador WiFi o Ethernet (ej. `192.168.1.100`).
- **Mac/Linux:** `ip addr` o `ifconfig` y usa la IP de tu interfaz (ej. `192.168.1.100`).

Usa esa IP en los pasos siguientes (ejemplo: `192.168.1.100`).

---

## 2. Ajustar el archivo `.env`

En la raíz del proyecto, en tu `.env`:

```env
APP_URL=http://192.168.1.100:8000

SESSION_DOMAIN=null

SANCTUM_STATEFUL_DOMAINS=192.168.1.100,192.168.1.100:8000,localhost,127.0.0.1
```

Sustituye `192.168.1.100` por tu IP real. Así Laravel y Sanctum aceptan peticiones desde ese host y las cookies de sesión funcionan desde el otro dispositivo.

---

## 3. Levantar Laravel escuchando en toda la red

En la PC del proyecto:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Con `--host=0.0.0.0` la app responde en todas las interfaces (incluida la IP de tu red), no solo en `localhost`.

---

## 4. Levantar Vite para que sea accesible desde la red

En **otra terminal** del mismo proyecto:

```bash
set VITE_HMR_HOST=192.168.1.100
npm run dev
```

En PowerShell también puedes usar:

```powershell
$env:VITE_HMR_HOST="192.168.1.100"; npm run dev
```

Sustituye de nuevo `192.168.1.100` por tu IP. Así el servidor de Vite (y el HMR) usan tu IP y el otro dispositivo puede cargar los assets y, si aplica, el hot reload.

---

## 5. Abrir la app desde el otro dispositivo

1. Asegúrate de que el otro dispositivo está en la **misma red WiFi/LAN** que la PC.
2. En el navegador del otro dispositivo abre:

   **`http://192.168.1.100:8000`**

   (con tu IP real).

3. Deberías ver la app (login, etc.). Inicia sesión y prueba con normalidad.

---

## Resumen rápido

| Dónde        | Qué hacer |
|-------------|-----------|
| **PC (terminal 1)** | `php artisan serve --host=0.0.0.0 --port=8000` |
| **PC (terminal 2)** | `set VITE_HMR_HOST=192.168.1.100` y luego `npm run dev` (o en PowerShell: `$env:VITE_HMR_HOST="192.168.1.100"; npm run dev`) |
| **.env**    | `APP_URL=http://192.168.1.100:8000`, `SESSION_DOMAIN=null`, `SANCTUM_STATEFUL_DOMAINS=192.168.1.100,...` |
| **Otro dispositivo** | Abrir `http://192.168.1.100:8000` en el navegador |

---

## Si usas Laragon (Apache/Nginx)

Si normalmente entras por `http://helpdeskreact.test`:

- Para probar en red lo más simple es usar **solo** `php artisan serve --host=0.0.0.0` como en los pasos anteriores (no hace falta tocar Apache/Nginx).
- Si quieres seguir usando el virtual host de Laragon desde otro dispositivo, tendrías que:
  - Añadir tu IP como ServerAlias en el virtual host, o
  - Poner esa IP en el archivo `hosts` del otro dispositivo apuntando al nombre del sitio.

Para pruebas rápidas en la red, usar `php artisan serve --host=0.0.0.0` es suficiente.

---

## Firewall

Si el otro dispositivo no puede conectar:

- **Windows:** permite en el firewall las conexiones entrantes en los puertos **8000** (Laravel) y **5173** (Vite), o desactiva temporalmente el firewall para probar.
- **Mac:** en Preferencias del Sistema → Seguridad y privacidad → Firewall, permitir PHP o el puerto 8000 si te lo pide.

---

## Error "Solicitud de origen cruzado bloqueada" (CORS) con `http://[::]:5173`

Si en la consola del navegador ves que los scripts de Vite se cargan desde `http://[::]:5173` y el navegador los bloquea por CORS:

- **Causa:** Con `host: true`, Vite se enlaza a todas las interfaces y a veces escribe `[::]:5173` en el archivo `public/hot`. Ese origen no coincide con el de la página (tu IP o `helpdeskreact.test`).
- **Solución en este proyecto:** En `AppServiceProvider` se reemplaza automáticamente esa URL por el **host de la petición** (desde qué URL abriste la app). Así, si entras desde `http://192.168.1.100`, los scripts pasan a cargarse desde `http://192.168.1.100:5173`.
- **Opcional:** Si quieres forzar siempre un host concreto (por ejemplo tu IP) en lugar del host de la petición, define en `.env`:
  ```env
  VITE_DEV_SERVER_HOST=192.168.1.100
  ```
  (sustituye por tu IP). Así la URL de Vite será siempre esa, aunque abras la app desde `helpdeskreact.test`.

Asegúrate de que Vite esté levantado con `npm run dev` (y si usas otro dispositivo, con `VITE_HMR_HOST=tu_ip`) y que el puerto **5173** esté permitido en el firewall.

---

## Notas

- Si cambias de red (otra WiFi), tu IP puede cambiar; vuelve a poner la nueva IP en `.env` y en `VITE_HMR_HOST`.
- Las cookies de Sanctum son por dominio; al usar la IP, `SESSION_DOMAIN=null` hace que la cookie sea para esa IP y todo funcione desde el otro dispositivo.
