# Migraciones – Tikara

## Conjunto consolidado (2026-07-09)

Las 73 migraciones incrementales originales (2026-02 a 2026-07) fueron reescritas como un
conjunto limpio de 27 archivos — sin ambiente prod/staging desplegado todavía, era la única
ventana segura para hacerlo antes de perder esa oportunidad. Todas las columnas nacen ya en su
forma final (sin ALTERs posteriores) y en inglés donde antes había español.

| Archivo | Descripción |
|---------|-------------|
| `..._000001_create_cache_table.php` | cache, cache_locks |
| `..._000002_create_jobs_table.php` | jobs, job_batches, failed_jobs |
| `..._000003_create_permission_tables.php` | Spatie: permissions, roles, pivotes |
| `..._000004_create_lookup_catalogs_table.php` | campaigns, areas, positions (sin scope de operador todavía) |
| `..._000005_create_plans_table.php` | plans |
| `..._000006_create_clients_table.php` | clients (operator_user_id sin FK aún — ver nota circular) |
| `..._000007_create_sites_table.php` | sites (antes "sedes") + seed de sitio "Remoto" |
| `..._000008_create_locations_table.php` | locations (antes "ubicaciones") |
| `..._000009_create_users_table.php` | users, password_reset_tokens, sessions, blacklist_logs + FK pendiente de clients.operator_user_id |
| `..._000010_create_personal_access_tokens_table.php` | Sanctum |
| `..._000011_create_email_verification_tokens_table.php` | Verificación de email |
| `..._000012_add_operator_and_client_scope_to_early_catalogs.php` | operator_user_id/client_id en campaigns/areas/positions |
| `..._000013_seed_permissions_and_roles.php` | Rol admin + todos los permisos (core, tickets, incidents, clients, company) |
| `..._000014_create_user_invitations_table.php` | user_invitations |
| `..._000015_create_operator_profiles_table.php` | operator_profiles |
| `..._000016_create_ticket_catalogs_table.php` | priorities, ticket_states, ticket_types, area_ticket_type, impact_levels, urgency_levels, priority_matrix |
| `..._000017_create_tickets_table.php` | tickets (client_id nullable — ver enforcement al final) |
| `..._000018_create_ticket_activity_tables.php` | ticket_histories, ticket_area_access, ticket_attachments, ticket_alerts, ticket_macros |
| `..._000019_create_incident_tables.php` | incident_types/severities/statuses, incidents, incident_attachments, incident_histories |
| `..._000020_create_notification_tables.php` | notifications, admin_notifications |
| `..._000021_create_audit_logs_table.php` | audit_logs (polimórfica; sustituye al legacy `ticket_audit_logs`, que nunca llegó a producción) |
| `..._000022_create_ticket_classification_rules_table.php` | ticket_classification_rules |
| `..._000023_create_ticket_sequences_table.php` | ticket_sequences (folio por cliente) |
| `..._000024_create_email_domains_table.php` | email_domains |
| `..._000025_create_sigan_assets_tables.php` | assets, asset_components, asset_maintenance |
| `..._000026_enable_pgsql_rls_tickets_incidents_sites.php` | RLS (solo PostgreSQL, `TENANCY_PGSQL_RLS=true`) |
| `..._000027_enforce_tenant_client_id_not_null.php` | Backfill + `client_id NOT NULL` en tickets/incidents |

## Qué se eliminó (no se recrea)

- Módulo **TimeDesk/Attendance** completo (schedules, schedule_days, schedule_assignments,
  attendances, employee_profiles, employee_statuses, hire_types, recruitment_sources,
  termination_reasons): se creó y se eliminó por completo meses atrás sin llegar a usarse.
- **`ticket_audit_logs`**: tabla creada y eliminada el mismo día, sustituida de inmediato por
  `audit_logs` (polimórfica). Nunca llegó a producción.

## Dependencia circular clients ↔ users

`clients.operator_user_id` referencia a `users.id`, pero `users.client_id` referencia a
`clients.id`. Se resuelve creando `clients` primero sin el FK de `operator_user_id` (columna
simple), y añadiendo esa constraint al final de `..._000009_create_users_table.php`, una vez
que `users` ya existe.

## Tablas en inglés

- **sites** (antes "sedes"), **locations** (antes "ubicaciones"). Modelos `App\Models\Site` /
  `App\Models\Location` (antes `Sede`/`Ubicacion`); columnas `site_id`/`location_id` en `users`,
  `tickets` e `incidents` en inglés en toda la base.
- **assets / asset_components / asset_maintenance** (antes `sigan_assets` / `sigan_asset_components`
  / `sigan_maintenance`): columnas y valores enum también traducidos (`tipo`→`type`, `estado`→`status`,
  `activo`→`active`, `interno`→`internal`, etc.). El nombre del archivo de migración conserva
  `sigan` por continuidad histórica del nombre de archivo, no del esquema.

## Uso

**Instalación nueva:**

```bash
php artisan migrate
```

**Recrear base de datos:**

```bash
php artisan migrate:fresh --seed
```
