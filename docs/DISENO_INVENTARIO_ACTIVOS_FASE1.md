# Propuesta de diseño — Fase 1: Módulo de Inventario de Activos

**Versión:** 1.0  
**Fecha:** 2026-03  
**Estado:** Solo diseño (sin implementación)

---

## A. Análisis del estado actual

### A.1 Catálogos y modelos existentes que se reutilizarán

| Recurso | Ubicación | Uso en Inventario de Activos |
|--------|-----------|------------------------------|
| **Ubicaciones** | `locations` (modelo `Ubicacion`), API `/api/ubicaciones` | Ubicación física del activo (FK `ubicacion_id` → `locations.id`). Catálogo ya incluye `sede_id`, `name`, `code`; se usa en usuarios y puede usarse para activos. |
| **Sedes** | `sites` (modelo `Sede`), API `/api/sedes` | Contexto de ubicación (ubicaciones pertenecen a sedes). Opcional en activos si se filtra por sede. |
| **Empleados RH** | `sigua_empleados_rh` (modelo `App\Models\Sigua\EmpleadoRh`), SIGUA | Asignación de activo a empleado: responsable del resguardo. Tabla con `num_empleado`, `nombre_completo`, `sede_id`, `campaign_id`, `area`, `puesto`, `estatus`. **No existe API REST genérica de empleados RH**; SIGUA expone listados por contexto. Se propone exponer catálogo `empleados_rh` (id, num_empleado, nombre_completo) para el módulo de activos o reutilizar endpoint existente de SIGUA si aplica. |
| **Usuarios** | `users` | Quién autoriza asignaciones y quién registra cambios (`user_id` en asignaciones y en `audit_logs`). No como “empleado” del activo; el empleado es RH. |
| **Auditoría** | `audit_logs` (polimórfico), trait `App\Traits\Auditable` | Trazabilidad de cambios en activos y asignaciones. El trait registra `created`/`updated`/`deleted` con `old_values`/`new_values`, `user_id`, `ip_address`, `user_agent`. |

**No reutilizados (no existen como catálogos centrales):** Departamentos como entidad separada — el proyecto tiene `areas` (áreas) y `sigua_empleados_rh.area` (string). Para “departamento” del activo se puede usar `areas` si se desea, o dejarlo para fase posterior.

### A.2 Convenciones de código detectadas

**Backend (Laravel)**  
- **Tablas:** `snake_case`; nombres en inglés en migraciones (`locations`, `sites`, `audit_logs`). Excepciones: `sigua_*` en español de negocio. Para el nuevo módulo se propone prefijo `asset_` (inglés) para tablas nuevas.  
- **Modelos:** PascalCase, `$table` explícito si la tabla no sigue convención (ej. `Ubicacion` → `locations`). Namespace por dominio cuando aplica (ej. `App\Models\Sigua\*`).  
- **Controladores:** `App\Http\Controllers\Api\*` para API; subcarpeta por dominio (ej. `Sigua\InventarioController`). Rutas API bajo `routes/api.php` o incluidas desde `routes/sigua.php` con prefijo.  
- **Validación:** Form Requests en `App\Http\Requests` (y `App\Http\Requests\Sigua\*` para SIGUA). `authorize()` con `$this->user()?->can('permiso')`. Reglas con `Rule::unique()`, `exists:table,col`.  
- **Respuestas:** Sin API Resources; respuestas con `response()->json($model)` o `->paginate()->through(fn)`. Paginación con `meta`: `current_page`, `last_page`, `per_page`, `total`.  
- **Permisos:** Middleware `perm:nombre.accion` (ej. `perm:sigua.cuentas.view`). Varios permisos con `|`. Permisos en tabla `permissions`, asignados a roles.  
- **Auditoría:** Modelos con `use Auditable;` y `bootAuditable()` (en trait). No modificar tablas existentes.

**Frontend (React)**  
- **Estructura:** Páginas en `resources/js/Pages/`; por módulo en subcarpeta (ej. `Sigua/`). Componentes en `resources/js/components/` (incl. `ui/` para shadcn).  
- **Rutas:** Lazy load en `Main.jsx`; rutas anidadas bajo path del módulo (ej. `/sigua/explorador`).  
- **Estado:** `useState`/`useEffect`; sin React Query ni Zustand en lo revisado.  
- **Formularios:** `react-hook-form` + `@hookform/resolvers/zod` + shadcn `Form`/`FormField`/`FormItem`/`FormLabel`/`FormControl`/`FormMessage`. Esquema Zod alineado con API.  
- **API:** `axios` desde `@/lib/axios`. Mensajes con `notify.success|error|warning|info` (`@/lib/notify`, Sileo). Errores con `getApiErrorMessage(err, fallback)` (`@/lib/apiErrors`).  
- **Catálogos:** `loadCatalogs(forceFresh?, modules?)` desde `@/lib/catalogCache`; respuesta en `sessionStorage`; `clearCatalogCache()` tras mutaciones que afecten catálogos.  
- **UI:** shadcn (Card, Table, Button, Input, Select, Dialog, Badge, etc.); iconos Lucide.

### A.3 Puntos de integración con módulos existentes

- **Catálogos:** Añadir módulo `assets` (o `inventario`) en `CatalogController::ALLOWED_MODULES` y método `getAssetsCatalogs()` (o similar) que devuelva categorías, subcategorías, tipos, marcas, modelos, proveedores, estados de activo, tipos de garantía. Sedes y ubicaciones ya vienen en `core`; empleados RH habría que exponer (nuevo endpoint o ampliar catálogo).  
- **Rutas API:** Incluir grupo de rutas del inventario de activos (prefijo ej. `/api/assets` o `/api/inventario`) con middleware `auth:sanctum`, `locale`, y permisos `assets.view` / `assets.manage`.  
- **Sidebar:** Nuevo ítem bajo MÓDULOS (ej. “Inventario” o “Activos”) con hijos: Listado, Nuevo activo, Catálogos (o enlaces a catálogos), Impresión de etiquetas, etc., condicionado a `perm:assets.view` o similar.  
- **Centro de auditoría:** Si existe vista global de `audit_logs`, los registros de tipo `Asset` y `AssetAssignment` serán visibles igual que Tickets/SIGUA (misma tabla y trait).  
- **Tabla existente `sigan_assets`:** El proyecto tiene una migración `create_sigan_assets_tables` con `sigan_assets`, `sigan_asset_components`, `sigan_maintenance`. Es un modelo reducido (tipo, subtipo, nombre, numero_serie, estado, ubicación string). **Esta propuesta no modifica esas tablas.** Se diseñan tablas nuevas `asset_*` para el módulo “Inventario de Activos” Fase 1. Si en el futuro se desea migrar datos desde `sigan_assets`, será una tarea aparte.

---

## B. Arquitectura de base de datos

### B.1 Diagrama ER (Mermaid)

```mermaid
erDiagram
    asset_categories ||--o{ asset_subcategories : has
    asset_subcategories ||--o{ asset_types : has
    asset_categories ||--o{ assets : category
    asset_subcategories ||--o{ assets : subcategory
    asset_types ||--o{ assets : type
    asset_brands ||--o{ assets : brand
    asset_models ||--o{ assets : model
    asset_providers ||--o{ assets : provider_purchase
    asset_providers ||--o{ assets : provider_warranty
    asset_asset_states ||--o{ assets : state
    asset_warranty_types ||--o{ assets : warranty_type
    locations ||--o{ assets : ubicacion
    assets ||--o{ asset_tag_aliases : has
    assets ||--o{ asset_assignments : has
    sigua_empleados_rh ||--o{ asset_assignments : assigned_to
    users ||--o{ asset_assignments : authorized_by
    users ||--o{ asset_assignments : returned_by

    asset_categories { id PK, name, code, is_active, timestamps }
    asset_subcategories { id PK, category_id FK, name, code, is_active, timestamps }
    asset_types { id PK, subcategory_id FK, name, code, is_active, timestamps }
    asset_brands { id PK, name, is_active, timestamps }
    asset_models { id PK, brand_id FK, name, is_active, timestamps }
    asset_providers { id PK, name, is_active, timestamps }
    asset_asset_states { id PK, name, code, is_active, timestamps }
    asset_warranty_types { id PK, name, is_active, timestamps }
    assets { id PK, tag, tag_custom, category_id FK, subcategory_id FK, type_id FK, brand_id FK, model_id FK, state_id FK, ubicacion_id FK, name, description, numero_serie, purchase_date, purchase_cost, provider_id FK, invoice_number, warranty_start, warranty_end, warranty_type_id FK, warranty_provider_id FK, photo_path, qr_path, etiqueta_impresa, assignment_status, created_at, updated_at }
    asset_tag_aliases { id PK, asset_id FK, tag_alias, timestamps }
    asset_assignments { id PK, asset_id FK, empleado_rh_id FK, assigned_at, assigned_by_user_id FK, observations, returned_at, returned_by_user_id FK, return_observations, timestamps }
    locations { id PK, sede_id, name, code, is_active }
    sigua_empleados_rh { id PK, num_empleado, nombre_completo, ... }
    users { id PK, name, ... }
```

### B.2 Tablas nuevas (campos, tipos, índices, relaciones)

**1. `asset_categories`**  
- `id` bigint PK, auto increment  
- `name` string(100) not null  
- `code` string(20) nullable, unique (ej. TI, MOB)  
- `is_active` boolean default true  
- `timestamps`  
- Índice: `code` unique.

**2. `asset_subcategories`**  
- `id` bigint PK  
- `category_id` bigint FK → `asset_categories.id` cascade on delete  
- `name` string(100) not null  
- `code` string(20) nullable (ej. LAP, PC)  
- `is_active` boolean default true  
- `timestamps`  
- Índice: `category_id`; unique (`category_id`, `code`) si code no null.

**3. `asset_types`**  
- `id` bigint PK  
- `subcategory_id` bigint FK → `asset_subcategories.id` cascade on delete  
- `name` string(100) not null  
- `code` string(20) nullable  
- `is_active` boolean default true  
- `timestamps`  
- Índice: `subcategory_id`.

**4. `asset_brands`**  
- `id` bigint PK  
- `name` string(100) not null  
- `is_active` boolean default true  
- `timestamps`  

**5. `asset_models`**  
- `id` bigint PK  
- `brand_id` bigint FK → `asset_brands.id` null on delete set null (o restrict si se prefiere)  
- `name` string(100) not null  
- `is_active` boolean default true  
- `timestamps`  
- Índice: `brand_id`.

**6. `asset_providers`**  
- `id` bigint PK  
- `name` string(255) not null  
- `is_active` boolean default true  
- `timestamps`  

**7. `asset_asset_states`** (estados del activo)  
- `id` bigint PK  
- `name` string(60) not null  
- `code` string(30) not null unique (activo, en_almacen, en_reparacion, dado_de_baja, canibalizado, parcialmente_canibalizado)  
- `is_active` boolean default true  
- `timestamps`  

**8. `asset_warranty_types`**  
- `id` bigint PK  
- `name` string(60) not null  
- `is_active` boolean default true  
- `timestamps`  

**9. `assets`**  
- `id` bigint PK  
- `tag` string(50) not null unique — identificador principal (ECD-CAT-SUB-0001 o personalizado)  
- `tag_custom` boolean default false — true si el tag fue personalizado manualmente  
- `category_id` bigint FK → `asset_categories.id` (required)  
- `subcategory_id` bigint FK → `asset_subcategories.id` (required)  
- `type_id` bigint nullable FK → `asset_types.id`  
- `brand_id` bigint nullable FK → `asset_brands.id`  
- `model_id` bigint nullable FK → `asset_models.id`  
- `state_id` bigint FK → `asset_asset_states.id` (required, default “activo”)  
- `ubicacion_id` bigint nullable FK → `locations.id`  
- `name` string(255) not null — nombre corto del activo  
- `description` text nullable  
- `numero_serie` string(100) nullable  
- `purchase_date` date nullable  
- `purchase_cost` decimal(12,2) nullable  
- `provider_id` bigint nullable FK → `asset_providers.id` (compra)  
- `invoice_number` string(80) nullable  
- `warranty_start` date nullable  
- `warranty_end` date nullable  
- `warranty_type_id` bigint nullable FK → `asset_warranty_types.id`  
- `warranty_provider_id` bigint nullable FK → `asset_providers.id`  
- `photo_path` string(500) nullable — path relativo de la imagen (storage)  
- `qr_path` string(500) nullable — path del QR generado (opcional, o generado on-the-fly)  
- `etiqueta_impresa` boolean default false  
- `assignment_status` enum('disponible','asignado','en_prestamo','en_transito') default 'disponible'  
- `timestamps`  
- Índices: `tag` unique; `category_id`, `subcategory_id`, `state_id`, `ubicacion_id`, `assignment_status`; índice compuesto para generación de consecutivo (category_id, subcategory_id).

**10. `asset_tag_aliases`** (tags secundarios / legacy)  
- `id` bigint PK  
- `asset_id` bigint FK → `assets.id` cascade on delete  
- `tag_alias` string(50) not null  
- `timestamps`  
- Índice: (`asset_id`, `tag_alias`); unique `tag_alias` para que cada alias sea único globalmente.

**11. `asset_assignments`** (historial de asignaciones / resguardos)  
- `id` bigint PK  
- `asset_id` bigint FK → `assets.id` cascade on delete  
- `empleado_rh_id` bigint FK → `sigua_empleados_rh.id` null on delete set null  
- `assigned_at` datetime not null  
- `assigned_by_user_id` bigint nullable FK → `users.id` null on delete set null  
- `observations` text nullable  
- `returned_at` datetime nullable  
- `returned_by_user_id` bigint nullable FK → `users.id` null on delete set null  
- `return_observations` text nullable  
- `timestamps`  
- Índices: `asset_id`, `empleado_rh_id`; índice para “asignación vigente” (returned_at null).

### B.3 Orden de migraciones propuesto

1. `create_asset_categories_table`  
2. `create_asset_subcategories_table`  
3. `create_asset_types_table`  
4. `create_asset_brands_table`  
5. `create_asset_models_table`  
6. `create_asset_providers_table`  
7. `create_asset_asset_states_table`  
8. `create_asset_warranty_types_table`  
9. `create_assets_table`  
10. `create_asset_tag_aliases_table`  
11. `create_asset_assignments_table`  

Todas con `Schema::create(...)` y `down()` con `Schema::dropIfExists` en orden inverso.

### B.4 Seeders para catálogos iniciales

- **AssetStateSeeder:** insertar códigos: activo, en_almacen, en_reparacion, dado_de_baja, canibalizado, parcialmente_canibalizado.  
- **AssetCategorySeeder (opcional):** ej. TI, Mobiliario con subcategorías y tipos iniciales (LAP, PC, Silla, etc.) para no dejar vacíos los catálogos.  
- **AssetWarrantyTypeSeeder (opcional):** ej. “Fábrica”, “Extendida”, “Sin garantía”.  

Invocar desde `DatabaseSeeder` o desde una migración con `DB::table(...)->insert()` si se prefiere datos mínimos en migración.

---

## C. Arquitectura backend (Laravel)

### C.1 Modelos Eloquent y relaciones

- **AssetCategory:** hasMany AssetSubcategory; hasMany Asset.  
- **AssetSubcategory:** belongsTo AssetCategory; hasMany AssetType; hasMany Asset.  
- **AssetType:** belongsTo AssetSubcategory; hasMany Asset.  
- **AssetBrand:** hasMany AssetModel; hasMany Asset.  
- **AssetModel:** belongsTo AssetBrand; hasMany Asset.  
- **AssetProvider:** hasMany Asset (provider_id, warranty_provider_id).  
- **AssetState:** hasMany Asset.  
- **AssetWarrantyType:** hasMany Asset.  
- **Asset:** belongsTo Category, Subcategory, Type?, Brand?, Model?, State, Ubicacion?, Provider?, WarrantyType?, WarrantyProvider?; hasMany AssetTagAlias; hasMany AssetAssignment. **Traits:** Auditable (y SoftDeletes si se desea borrado lógico).  
- **AssetTagAlias:** belongsTo Asset.  
- **AssetAssignment:** belongsTo Asset, EmpleadoRh (empleado_rh_id), User (assigned_by_user_id, returned_by_user_id). **Trait:** Auditable.  

Ubicación del modelo: `App\Models\Asset`, `App\Models\AssetCategory`, etc. (o namespace `App\Models\Assets\` si se prefiere agrupar).

### C.2 Controladores y métodos (RESTful)

- **AssetCategoryController:** index, store, update, destroy (CRUD catálogo).  
- **AssetSubcategoryController:** index (filtro por category_id), store, update, destroy.  
- **AssetTypeController:** index (filtro por subcategory_id), store, update, destroy.  
- **AssetBrandController, AssetModelController, AssetProviderController, AssetStateController, AssetWarrantyTypeController:** index, store, update, destroy (solo donde aplique; estados/garantía pueden ser solo lectura si se seedean).  
- **AssetController:**  
  - index: listado paginado con filtros (search, category_id, subcategory_id, state_id, assignment_status, ubicacion_id), orden, per_page.  
  - store: crear activo (generar tag automático o aceptar tag personalizado), subir foto, generar QR.  
  - show: detalle de un activo con relaciones (category, subcategory, type, brand, model, state, ubicacion, assignments).  
  - update: actualizar activo (y regenerar QR si cambia algo que afecte la URL).  
  - destroy: soft delete o hard según criterio del proyecto.  
- **AssetAssignmentController:**  
  - index: por asset_id (historial) o por empleado_rh_id (activos bajo resguardo del empleado).  
  - store: registrar asignación (actualizar asset.assignment_status, crear registro con returned_at null).  
  - update: registrar devolución (returned_at, return_observations, returned_by_user_id).  
- **AssetLabelController (o método en AssetController):**  
  - show: generar PDF de etiqueta individual (logo ECD, tag, QR, nombre corto).  
  - store o método `printBatch`: recibir array de asset ids, generar PDF con cuadrícula de etiquetas (~5cm×2.5cm).  
- **AssetQrController (opcional):** get(asset): devolver imagen QR o URL del activo (para mostrar en ficha).

Mantener convención: inyección de `Request`, validación vía Form Request donde sea complejo, respuestas `response()->json(...)` y paginación con `meta` como en el resto del proyecto.

### C.3 Form Requests

- **StoreAssetRequest / UpdateAssetRequest:** reglas para tag (unique en store, ignore en update), category_id, subcategory_id, name, state_id, ubicacion_id, fechas, costos, proveedores, warranty_*, photo (file, image, max size), tag_custom, etc.  
- **StoreAssetAssignmentRequest:** asset_id, empleado_rh_id, assigned_at, observations; opcional assigned_by_user_id (rellenar con auth()->id()).  
- **ReturnAssetAssignmentRequest:** return_observations; returned_at puede ser now() en backend.  
- **StoreAssetCategoryRequest** (y análogos para subcategoría, tipo, marca, modelo, proveedor): name, code, is_active, parent id cuando aplique.

Todos con `authorize(): bool` usando `$this->user()?->can('assets.manage')` o el permiso que se defina.

### C.4 API Resources

El proyecto actual no utiliza API Resources; se devuelve modelo o array mapeado. Se mantiene el mismo criterio: no introducir Resource classes; transformación en controlador con `->through()` o `->map()` si hace falta (ej. listado con nombres de categoría/subcategoría en lugar de solo IDs).

### C.5 Rutas propuestas (api.php o archivo incluido)

Prefijo sugerido: `/api/assets` (o `/api/inventario`).

- `GET    /api/assets` → AssetController@index  
- `POST   /api/assets` → AssetController@store  
- `GET    /api/assets/{asset}` → AssetController@show  
- `PUT    /api/assets/{asset}` → AssetController@update  
- `DELETE /api/assets/{asset}` → AssetController@destroy  
- `GET    /api/assets/categories` → AssetCategoryController@index  
- `POST   /api/assets/categories` → AssetCategoryController@store  
- `PUT    /api/assets/categories/{category}` → AssetCategoryController@update  
- `DELETE /api/assets/categories/{category}` → AssetCategoryController@destroy  
- (Análogo para subcategories, types, brands, models, providers, states, warranty-types según se expongan.)  
- `GET    /api/assets/{asset}/assignments` → AssetAssignmentController@index (por asset)  
- `POST   /api/assets/{asset}/assignments` → AssetAssignmentController@store  
- `PUT    /api/assets/assignments/{assignment}` → AssetAssignmentController@update (devolución)  
- `GET    /api/assets/assignments?empleado_rh_id=` → AssetAssignmentController@index (por empleado)  
- `GET    /api/assets/{asset}/label` → etiqueta PDF individual  
- `POST   /api/assets/labels/batch` → PDF lote (body: { asset_ids: [] })  
- `GET    /api/assets/{asset}/qr` → imagen o URL del QR (opcional)  

Middleware: `auth:sanctum`, `locale`, y permisos por ruta (ej. `perm:assets.view` para GET, `perm:assets.manage` para mutaciones).

### C.6 Políticas de autorización

- **AssetPolicy:** view → assets.view; create/update/delete → assets.manage. Si se desea restricción por sede/área, se puede añadir lógica en la política.  
- **AssetAssignmentPolicy:** ver asignaciones → assets.view; crear/devolver → assets.manage.  

Registro en `AuthServiceProvider`: `Asset::class => AssetPolicy::class`, etc.

### C.7 Servicios auxiliares

- **AssetTagService:**  
  - `generateTag(AssetCategory $cat, AssetSubcategory $sub): string` — formato ECD-{CODE_CAT}-{CODE_SUB}-{CONSECUTIVO} (ej. ECD-TI-LAP-0001). Obtener último consecutivo por (category_id, subcategory_id) y formatear.  
  - `validateUniqueTag(string $tag, ?int $excludeAssetId = null): bool` — comprobar que tag (y tag_aliases) no existan.  
- **AssetQrService:**  
  - `generateQrForAsset(Asset $asset): string` — generar imagen QR que codifique la URL de la ficha del activo (ej. `config('app.front_url')/assets/{id}` o ruta SPA). Guardar path en `asset.qr_path` o generar on-the-fly según decisión.  
- **AssetLabelPdfService:**  
  - `generateSingleLabel(Asset $asset): \Illuminate\Http\Response` (o BinaryFileResponse) — PDF una etiqueta con logo ECD, tag, QR, nombre corto, tamaño ~5cm×2.5cm.  
  - `generateBatchLabels(array $assetIds): \Illuminate\Http\Response` — PDF con cuadrícula de etiquetas para los activos dados.  
- **AssetPhotoService (opcional):** guardar upload en `storage/app/public/assets/photos`, devolver path relativo; validar tipo y tamaño en Request.

---

## D. Arquitectura frontend (React)

### D.1 Estructura de páginas y componentes nuevos

- **Pages:**  
  - `Assets/Index.jsx` (o `Inventario/Index.jsx`) — listado de activos con filtros, búsqueda, paginación, columnas (tag, nombre, categoría, estado, ubicación, asignación), acciones (ver, editar, etiqueta, historial).  
  - `Assets/AssetForm.jsx` — formulario crear/editar activo (todos los campos de la ficha, subida de foto, tag manual opcional).  
  - `Assets/AssetDetail.jsx` — ficha del activo: datos generales, adquisición, garantía, ubicación, foto, QR, historial de asignaciones, botones (editar, imprimir etiqueta, asignar/devolver).  
  - `Assets/Assignments.jsx` o sección dentro de AssetDetail — listado de asignaciones del activo; formulario “Asignar” y “Devolver”.  
  - `Assets/LabelPrint.jsx` — vista para seleccionar activos y generar PDF de etiquetas en lote.  
  - `Assets/Resguardo.jsx` — vista “resguardo digital” por empleado: listar activos asignados a un empleado_rh_id (documento imprimible o PDF).  
- **Componentes (ejemplos):**  
  - `AssetTable.jsx` — tabla reutilizable con columnas configurables (o usar DataTable existente si hay uno en el proyecto).  
  - `AssetFilters.jsx` — filtros (categoría, subcategoría, estado, ubicación, estado de asignación).  
  - `AssetQrDisplay.jsx` — mostrar QR del activo (img desde API o data URL).  
  - `AssetPhotoUpload.jsx` — input file + preview para foto del activo.  

Rutas en `Main.jsx`: ej. `/assets`, `/assets/new`, `/assets/:id`, `/assets/:id/edit`, `/assets/labels`, `/assets/resguardo/:empleadoRhId` (o por query).

### D.2 Listado de vistas

| Vista | Ruta | Descripción |
|-------|------|-------------|
| Índice de activos | `/assets` | Tabla paginada, filtros, búsqueda, acciones por fila. |
| Nuevo activo | `/assets/new` | Formulario alta; generación de tag automático o manual. |
| Editar activo | `/assets/:id/edit` | Mismo formulario en modo edición. |
| Detalle de activo | `/assets/:id` | Ficha completa, foto, QR, historial asignaciones, asignar/devolver. |
| Impresión etiquetas | `/assets/labels` | Selección múltiple de activos, botón “Generar PDF” (cuadrícula). |
| Resguardo por empleado | `/assets/resguardo?empleado_rh_id=` o `/assets/resguardo/:id` | Listado de activos asignados al empleado; opción imprimir/PDF. |

### D.3 Hooks personalizados

- **useAssets(filters, pagination):** fetch listado paginado, estado loading/error, refetch.  
- **useAsset(id):** fetch detalle de un activo (y asignaciones si se incluyen en el mismo endpoint o en otro).  
- **useAssetCatalogs():** cargar catálogos del módulo (categories, subcategories, types, brands, models, providers, states, warranty_types) — puede usar `loadCatalogs` con módulo `assets` o llamar a endpoints específicos.  
- **useAssetAssignments(assetId):** fetch historial de asignaciones del activo.  
- **useEmpleadosRh():** listado de empleados RH para selector de asignación (si no viene en catálogos globales).

### D.4 Integración con shadcn/ui

- Usar Card, Table, Button, Input, Select, Label, Dialog, Badge, Form (react-hook-form + zod) como en SiguaCuentaForm y Ubicaciones.  
- DataTable si ya existe en el proyecto para listado con ordenación.  
- Para subida de foto: Input type file o componente de upload existente; preview con `<img>` o Avatar.  

### D.5 Flujo de navegación

- Sidebar → Módulo “Inventario” (o “Activos”) → Listado → Ver/Editar/Nuevo/Etiquetas/Resguardo.  
- Desde detalle de activo → Editar, Imprimir etiqueta, Asignar/Devolver.  
- Desde listado → “Imprimir etiquetas” → selección múltiple → Generar PDF.  
- Resguardo: entrada desde listado (por empleado) o desde detalle de empleado si en el futuro hay ficha de empleado RH en SIGUA.

---

## E. Plan de implementación

### E.1 Orden sugerido

1. **Migraciones y seeders** — Crear tablas en el orden de B.3; seedear estados y opcionalmente categorías/subcategorías/tipos y tipos de garantía.  
2. **Modelos y relaciones** — Eloquent con relaciones y trait Auditable en Asset y AssetAssignment.  
3. **Servicios** — AssetTagService, AssetQrService, AssetLabelPdfService (y photo si aplica).  
4. **Permisos** — Registrar permisos `assets.view`, `assets.manage` y asignar a rol(es) en seeder o migración.  
5. **API catálogos** — Endpoints CRUD de categorías, subcategorías, tipos, marcas, modelos, proveedores, estados, warranty_types; e integración en CatalogController (módulo assets).  
6. **API activos** — AssetController (index, store, show, update, destroy); upload de foto; generación de tag y QR en store.  
7. **API asignaciones** — AssetAssignmentController; actualización de assignment_status en Asset.  
8. **API etiquetas** — Generación de PDF individual y lote.  
9. **Frontend: catálogos y listado** — Página índice con filtros y tabla paginada.  
10. **Frontend: formulario activo** — Alta y edición con todos los campos y foto.  
11. **Frontend: detalle y asignaciones** — Ficha, historial, asignar/devolver.  
12. **Frontend: etiquetas y resguardo** — Vista impresión lote; vista resguardo por empleado.

### E.2 Dependencias entre componentes

- Frontend listado y formulario dependen de API activos y de catálogos.  
- Formulario de asignación depende de listado de empleados RH (API o catálogo).  
- PDF de etiquetas depende de AssetQrService (o QR ya guardado) y de AssetLabelPdfService.  
- Auditoría funciona en cuanto los modelos usen Auditable y exista audit_logs.

### E.3 Complejidad estimada

- **Baja:** Migraciones, modelos, CRUD de catálogos, listado simple y formulario básico.  
- **Media:** Generación de tag automático, integración de QR, upload de foto, filtros y paginación en listado, historial de asignaciones.  
- **Alta:** Generación de PDF con cuadrícula de etiquetas, vista de resguardo imprimible, reglas de negocio (no permitir asignar si ya está asignado sin devolución previa, etc.).

### E.4 Riesgos y decisiones a confirmar

- **Tabla `sigan_assets` existente:** No se modifica. Si se desea unificar con “Inventario de Activos”, definir si habrá migración de datos posterior o si son módulos distintos.  
- **Empleados RH:** Definir si el listado para asignación vendrá de SIGUA (empleados ya importados) o de otro origen; y si se filtrará por sede/campaña.  
- **URL del QR:** Debe coincidir con la ruta SPA real (ej. `https://app.ecd.com/assets/123`). Confirmar dominio y base path.  
- **Formato de etiqueta:** Tamaño exacto (~5cm×2.5cm) y número de etiquetas por hoja para la cuadrícula (ej. 8 por hoja A4).  
- **Soft delete en activos:** Si los activos se borran con soft delete, listados y asignaciones deben filtrar por no eliminados.  
- **Permisos:** Decidir si se necesita solo `assets.view` y `assets.manage` o permisos más granulares (ej. `assets.labels`, `assets.assign`).

---

## F. Preguntas para el desarrollador

1. **Prefijo de rutas API:** ¿Prefieren `/api/assets` o `/api/inventario` (o otro)?  
2. **Nombre del módulo en sidebar:** ¿“Inventario”, “Activos” o “Inventario de Activos”?  
3. **Relación con `sigan_assets`:** ¿Mantenemos ese módulo separado y este nuevo es el estándar para la ficha completa, o hay plan de migrar datos de sigan_assets a las nuevas tablas?  
4. **Origen de empleados para asignación:** ¿Siempre desde `sigua_empleados_rh` (necesitamos endpoint o catálogo que los exponga) o también se asignará a usuarios del sistema en algún caso?  
5. **Almacenamiento de foto y QR:** ¿En `storage/app/public` con symlink, o en disco/S3 externo? ¿Tamaño máximo de foto y formatos permitidos?  
6. **Etiqueta impresa:** ¿El PDF de lote debe incluir solo activos que aún no tienen `etiqueta_impresa = true`, o se permite reimprimir y solo se marca como impresa al confirmar algo en la UI?  
7. **Resguardo digital:** ¿Solo vista en pantalla con botón “Imprimir”, o también endpoint que devuelva PDF del resguardo del empleado?  
8. **Consecutivo del tag:** ¿El consecutivo (ECD-TI-LAP-0001, 0002…) es global por (categoría, subcategoría) sin reinicio anual, o se desea reinicio por año/periodo?  
9. **Campos obligatorios mínimos en alta de activo:** ¿Solo categoría, subcategoría, nombre y estado, o también ubicación/numero_serie obligatorios desde Fase 1?  
10. **Permisos:** ¿Un solo rol “gestor de inventario” con assets.view y assets.manage, o se necesitan roles separados (solo consulta vs. alta/baja/asignaciones)?

---

*Documento generado a partir del análisis del código existente. No incluye implementación; cualquier cambio en convenciones del proyecto debe reflejarse en este diseño antes de codificar.*
