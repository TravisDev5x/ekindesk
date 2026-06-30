<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    // TODO: Sprint 3
    // Panel Master de DDMA — acceso modo dios a métricas agregadas de la plataforma.
    //
    // Restricciones:
    //   - Solo accesible desde IPs en config('tikara.super_admin_ips')
    //   - Requiere rol super_admin + permiso platform.super_dashboard
    //   - Zero-knowledge: métricas agregadas únicamente, sin contenido de tenants
    //
    // index(): métricas globales (tenants activos, tickets/día, uptime)
    // clients(): lista de todos los clientes con estado de suscripción
    // health(): estado de colas, jobs fallidos, storage, cache
}
