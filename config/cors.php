<?php

return [

    /*
     * Rutas que reciben cabeceras CORS. HandleCors ya corre global (default
     * Laravel 12) — este archivo solo configura qué orígenes se permiten.
     *
     * Para el flujo Inertia.js (mismo subdominio) no hay cross-origin, así que
     * CORS solo aplica a herramientas externas o apps móviles que consuman la API.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Orígenes explícitos permitidos. Vacío = ningún origen externo.
     * IMPORTANTE: con supports_credentials=true el navegador rechaza '*'.
     * Siempre debe ser una lista concreta cuando se usan cookies de sesión.
     *
     * Prod: CORS_ALLOWED_ORIGINS=https://app.ekindesk.com,https://otro.com
     */
    'allowed_origins' => array_values(array_filter(
        array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '')))
    )),

    /*
     * Patrones regex para portales dinámicos (sin tener que listar cada slug).
     * Prod: CORS_ALLOWED_ORIGINS_PATTERNS=^https://[a-z0-9-]+\.ekindesk\.com$
     */
    'allowed_origins_patterns' => array_values(array_filter(
        array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS', '')))
    )),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * Debe ser true para que el navegador incluya la cookie de sesión Sanctum
     * en peticiones cross-origin. Requiere 'allowed_origins' explícito (no '*').
     */
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
