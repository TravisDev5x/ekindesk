<?php

return [

    /*
    | Dominio base (ej. ekindesk.test). Subdominio = clients.portal_slug
    | URL portal: https://{portal_slug}.{base_domain}/login
    */
    'base_domain' => env('TENANCY_BASE_DOMAIN'),

    'portal_scheme' => env('TENANCY_PORTAL_SCHEME', 'http'),

    /*
    | true: en subdominio de cliente solo datos de ese client_id (cero visibilidad entre empresas).
    | false: subdominio solo branding; scopes de permiso como antes.
    */
    'strict_client_portal' => env('TENANCY_STRICT_CLIENT_PORTAL', true),

    /*
    | false (default): catálogos por plataforma + operador MSP, no por client_id.
    | true: en portal estricto cada empresa ve solo filas con su client_id (modo alternativo).
    */
    'catalog_per_client' => env('TENANCY_CATALOG_PER_CLIENT', false),

    /*
    | Requiere subdominio válido (portal_slug) para rutas autenticadas en producción.
    */
    'enforce_subdomain' => env('TENANCY_ENFORCE_SUBDOMAIN', true),

    'reserved_subdomains' => ['www', 'app', 'api', 'admin', 'mail', 'localhost'],

    'pgsql_rls_enabled' => env('TENANCY_PGSQL_RLS', false),

];
