# Reporte de Bugs y Deuda Técnica — Módulo SIGUA

**Alcance:** Análisis estático del ecosistema SIGUA (backend Laravel, frontend React/TypeScript, comandos).  
**Objetivo:** Detección de bugs críticos, excepciones no manejadas, desconexión frontend/backend, lógica de negocio y N+1.

---

## 🚨 Bugs Críticos (Backend)

### 1. Archivo temporal no eliminado si el servicio lanza antes de `unlink`

**Ubicación:** `app/Http/Controllers/Sigua/ImportacionController.php` (método `importar`).

**Problema:** Se guarda el archivo en `sigua/imports/temp`, se llama al servicio y solo al final se hace `@unlink($fullPath)`. Si `importarRH`, `importarBajasRH` o `importarSistema` lanzan una excepción (por ejemplo `SiguaException` por columnas faltantes), el `unlink` no se ejecuta y el archivo queda en disco.

**Fragmento:**

```php
$path = $file->store('sigua/imports/temp', ...);
$fullPath = Storage::disk('local')->path($path);
$import = match ($tipo) { ... };
@unlink($fullPath);  // solo si no hubo excepción
```

**Propuesta:** Usar `try/finally` para borrar el temporal siempre:

```php
try {
    $import = match ($tipo) { ... };
    return response()->json([...], 201);
} catch (\Throwable $e) {
    // ... log y mensaje
    return response()->json(['message' => '...'], 422);
} finally {
    if (isset($fullPath) && file_exists($fullPath)) {
        @unlink($fullPath);
    }
}
```

---

### 2. Preview no elimina archivo temporal en caso de excepción

**Ubicación:** `app/Http/Controllers/Sigua/ImportacionController.php` (método `preview`).

**Problema:** Si `$this->importacionService->preview($fullPath, ...)` lanza (por ejemplo sistema sin `campos_mapeo` o archivo corrupto), el `@unlink($fullPath)` no se ejecuta.

**Propuesta:** Igual que arriba: usar `finally` para garantizar el borrado del archivo temporal.

---

### 3. Excel corrupto o sin PhpSpreadsheet: excepción propagada sin mensaje amigable

**Ubicación:** `app/Services/Sigua/ImportacionService.php` — `leerArchivo` → `leerExcel`.

**Problema:** `\PhpOffice\PhpSpreadsheet\IOFactory::load($filePath)` puede lanzar excepciones de PhpSpreadsheet (archivo dañado, formato no soportado). El servicio no las captura; llegan al controlador, que sí hace catch y devuelve 422. Si el mensaje incluye rutas del servidor, ya se sanitiza en el controlador, pero mensajes técnicos de PhpSpreadsheet pueden ser poco útiles para el usuario.

**Propuesta:** En el controlador, para excepciones que no sean `SiguaException`, devolver un mensaje genérico tipo: "Error al procesar el archivo. Compruebe que sea un CSV o Excel válido (.xlsx, .xls)."

---

## ⚠️ Bugs de Frontend / TypeScript

### 1. Endpoint "comparar cruce": estructura de respuesta distinta al tipo y al uso en UI

**Ubicación:**  
- `resources/js/services/siguaApi.ts` — `compararCruce`  
- `resources/js/Pages/Sigua/SiguaCruces.tsx` — uso de `res.data?.resultados`

**Problema:**  
- El backend devuelve `data: { anomalias_nuevas, resueltas, sin_cambio, cruce_anterior_id }` (arrays de `CruceResultado` y un id).  
- El frontend tipa y espera `data: { cruce: Cruce; resultados: CruceResultado[] }`.  
- En `SiguaCruces.tsx` se hace `if (res.data?.resultados) setResultados(res.data.resultados)`, por lo que `resultados` queda siempre vacío y la vista "comparar" no muestra datos.

**Fragmento backend** (`CruceController::comparar`):

```php
$result = $this->cruceService->compararConCruceAnterior($cruce->id);
return response()->json(['data' => $result, 'message' => 'OK']);
// $result = ['anomalias_nuevas' => ..., 'resueltas' => ..., 'sin_cambio' => ..., 'cruce_anterior_id' => ...]
```

**Fragmento frontend** (`siguaApi.ts`):

```ts
export async function compararCruce(cruceId: number): Promise<SiguaApiResult<{ cruce: Cruce; resultados: CruceResultado[] }>> {
  const response = await axios.get<{ data: { cruce: Cruce; resultados: CruceResultado[] }; ... }>(...);
```

**Propuesta:**  
- Definir tipo para la respuesta real de comparar, por ejemplo:

```ts
export interface CompararCruceData {
  anomalias_nuevas: CruceResultado[];
  resueltas: CruceResultado[];
  sin_cambio: CruceResultado[];
  cruce_anterior_id: number | null;
}
```

- Cambiar `compararCruce` para que devuelva `SiguaApiResult<CompararCruceData>`.  
- En `SiguaCruces.tsx`, usar `anomalias_nuevas`, `resueltas` y `sin_cambio` para rellenar la tabla/vista (p. ej. concatenar o mostrar por pestañas) en lugar de `resultados`.

---

### 2. Tipo `Importacion` sin `registros_sin_cambio`

**Ubicación:** `resources/js/types/sigua.ts` — interfaz `Importacion`.

**Problema:** El modelo Laravel y la API incluyen `registros_sin_cambio`; la interfaz TypeScript no. Quien muestre el historial de importaciones no tendrá ese campo tipado.

**Propuesta:** Añadir en `Importacion`:

```ts
registros_sin_cambio?: number;
```

---

### 3. `toResult` y respuestas sin wrapper `data`

**Ubicación:** `resources/js/services/siguaApi.ts` — `toResult`.

**Problema:** Se asume que la respuesta puede ser `{ data: T }` o `T` directo. Algunos endpoints (p. ej. reportes que devuelven Blob) no envuelven en `data`. Si en el futuro algún endpoint SIGUA devuelve el payload en la raíz, `toResult` puede devolver algo incorrecto. Para `compararCruce` el backend sí devuelve `data`, pero con forma distinta a la esperada (ya cubierta en el bug anterior).

**Propuesta:** Revisar cada endpoint en el backend y asegurar que todos respondan con `{ data: ... }` cuando sea JSON. En el frontend, mantener `toResult` pero alinear tipos con la forma real de cada respuesta.

---

## 🛠️ Mejoras de Lógica (ISO 27001 / Casos borde)

### 1. Cuenta genérica sin responsable asignado (CA-01)

**Ubicación:** `app/Services/Sigua/CruceService.php` — bloque "Genéricas activas sin CA-01 vigente".

**Estado actual:** Se contemplan cuentas genéricas activas sin `empleado_rh_id` y sin CA-01 vigente (`generico_sin_responsable`). La lógica está bien para el caso "genérica sin responsable".

**Mejora:** Documentar explícitamente en el código que, según el control, toda cuenta genérica activa debe tener al menos un CA-01 vigente o quedar en estado "requiere acción". Opcional: en el mismo cruce, considerar si una cuenta tiene `empleado_rh_id` pero el empleado está en "Baja" y marcar también como anomalía (hoy eso se cubre con empleados en baja con cuentas activas en otro bloque).

---

### 2. Empleado dado de baja con cuentas activas

**Ubicación:** `app/Services/Sigua/CruceService.php` — `empleadosBaja` y `evaluarEmpleadoBaja`.

**Estado actual:** Se obtienen empleados en "Baja" o "Baja probable" con cuentas activas y se genera resultado `cuenta_baja_pendiente` con acción sugerida "Dar de baja o reasignar cuenta(s) activa(s) del empleado dado de baja." Correcto para trazabilidad y cumplimiento.

**Mejora:** Asegurar que las alertas creadas en `ImportacionService::alertarCuentasActivasConEmpleadoBaja` (tipo `cuenta_sin_responsable`) se alineen con la misma política: no dejar cuentas activas vinculadas a empleados de baja sin revisión.

---

### 3. Cuenta sin responsable asignado (huérfana)

**Ubicación:** `app/Services/Sigua/CruceService.php` — `resultadoCuentaSinRh` y bloque de cuentas huérfanas.

**Estado actual:** Se listan cuentas activas sin `empleado_rh_id` y que no son generica/servicio/prueba, y se categorizan como `cuenta_sin_rh` con acción "Verificar con RH y vincular empleado o marcar como genérica/servicio."

**Mejora:** Opcional: para cuentas en `pendiente_revision` (por ejemplo las que deja la importación cuando la cuenta ya no viene en el archivo), considerar incluirlas en el cruce o en un reporte aparte para no perderlas en la revisión.

---

### 4. N+1 en genericas sin responsable

**Ubicación:** `app/Services/Sigua/CruceService.php` — consulta `$genericasSinCa01` y uso en `resultadoGenericoSinResponsable`.

**Problema:** La consulta hace `with(['sistema', 'sede', 'campaign'])` pero no `empleadoRh`. En `resultadoGenericoSinResponsable` se usa `$cuenta->empleadoRh?->num_empleado`. Para las cuentas de esta query, `empleado_rh_id` es siempre `null`, por lo que no se dispara lazy load. No hay N+1 en la práctica.

**Recomendación:** Si en el futuro se amplía la consulta a cuentas que sí tengan `empleado_rh_id`, añadir `with('empleadoRh')` para evitar N+1.

---

### 5. Validación de columnas en CSV (encabezado vs datos)

**Ubicación:** `app/Services/Sigua/ImportacionService.php` — `leerCsv` y `obtenerEncabezado`.

**Estado actual:** La primera fila se guarda como fila con índices numéricos. `obtenerEncabezado` devuelve `array_keys($filas[0]) !== range(...) ? $filas[0] : $filas[0]`, es decir, la primera fila; para CSV eso son los nombres de columna. Las filas siguientes se construyen como asociativas con esos nombres. La validación de columnas mapeadas y el mapeo de filas son coherentes.

**Mejora:** Añadir un comentario en código aclarando que la primera fila del CSV es el encabezado y que las filas de datos son asociativas con esas claves, para evitar regresiones.

---

## Resumen de controladores (N+1)

| Controlador              | Método index / listado                         | Eager loading                                      | Valoración   |
|--------------------------|-------------------------------------------------|----------------------------------------------------|-------------|
| CuentaGenericaController | index                                           | `with(['sistema', 'sede', 'campaign', 'ca01Vigente'])` | Correcto    |
| BitacoraController       | index, porSede, hoy, sinUso                     | `with(['account', 'supervisor', 'sede'])` etc.     | Correcto    |
| CA01Controller           | index                                           | `with(['gerente', 'sede', 'sistema', 'campaign', 'cuentas'])` | Correcto    |
| CruceController          | historial, detalle                              | `with(['ejecutadoPor', 'importacion', 'resultados'])` | Correcto    |
| ImportacionController    | historial                                       | `with('importadoPor')`                             | Correcto    |

No se detectaron N+1 en los listados revisados.

---

## Resumen de acciones recomendadas

1. **Backend:** Usar `finally` en ImportacionController para borrar el archivo temporal en `importar` y `preview`.  
2. **Backend:** Confirmar scope `Sistema::activos()` y unificar con `where('activo', true)` si hace falta.  
3. **Backend:** Mensaje de error genérico en importación cuando la excepción no sea `SiguaException`.  
4. **Frontend:** Alinear tipo y uso de `compararCruce` con la respuesta real (`anomalias_nuevas`, `resueltas`, `sin_cambio`, `cruce_anterior_id`) y actualizar SiguaCruces.tsx.  
5. **Frontend:** Añadir `registros_sin_cambio` al tipo `Importacion`.  
6. **Documentación:** Documentar en código los criterios de cruce para cuentas genéricas, empleados de baja y cuentas huérfanas (ISO 27001).

---

*Reporte generado por análisis estático del código en el alcance indicado. Se recomienda pruebas de integración y E2E para cubrir flujos de importación, cruce y comparación.*
