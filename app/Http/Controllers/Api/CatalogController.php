<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogController extends Controller
{
    /** TTL caché catálogos (segundos). */
    private const CATALOG_CACHE_TTL = 600; // 10 minutos

    /** Módulos permitidos para carga parcial. */
    private const ALLOWED_MODULES = ['core', 'tickets', 'incidents', 'timedesk', 'sigua'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $skipCache = $request->boolean('nocache');
        $requestedModules = $this->parseModules($request->input('modules'));

        $cacheKey = $this->cacheKey($user, $requestedModules);

        if ($skipCache) {
            $data = $this->buildCatalogs($user, $requestedModules);
            return response()->json($data);
        }

        $data = Cache::remember($cacheKey, self::CATALOG_CACHE_TTL, fn () => $this->buildCatalogs($user, $requestedModules));
        return response()->json($data);
    }

    /**
     * Parsea y valida el parámetro modules (ej. ?modules=core,tickets).
     * Retorna null si no se envía o está vacío (carga completa); array de strings válidos en caso contrario.
     */
    private function parseModules(?string $modulesParam): ?array
    {
        if ($modulesParam === null || $modulesParam === '') {
            return null;
        }
        $parts = array_map('trim', explode(',', $modulesParam));
        $filtered = array_values(array_intersect($parts, self::ALLOWED_MODULES));
        return $filtered === [] ? null : array_unique($filtered);
    }

    private function cacheKey($user, ?array $modules): string
    {
        $base = 'catalogs.v2.' . ($user ? $user->id : 'guest');
        if ($modules === null || $modules === []) {
            return $base . '.full';
        }
        sort($modules);
        return $base . '.' . implode(',', $modules);
    }

    /**
     * Construye el payload de catálogos. Si $requestedModules es null, devuelve todo (compatibilidad hacia atrás).
     */
    private function buildCatalogs($user, ?array $requestedModules): array
    {
        if ($requestedModules === null || $requestedModules === []) {
            return $this->buildFullCatalogs($user);
        }

        $data = [];
        foreach ($requestedModules as $module) {
            $chunk = match ($module) {
                'core' => $this->getCoreCatalogs($user),
                'tickets' => $this->getTicketsCatalogs($user),
                'incidents' => $this->getIncidentsCatalogs(),
                'timedesk' => $this->getTimeDeskCatalogs(),
                'sigua' => $this->getSiguaCatalogs($user),
                default => [],
            };
            $data = array_merge($data, $chunk);
        }
        return $data;
    }

    /** Catálogo completo (comportamiento legacy). */
    private function buildFullCatalogs($user): array
    {
        return array_merge(
            $this->getCoreCatalogs($user),
            $this->getTicketsCatalogs($user),
            $this->getIncidentsCatalogs(),
            $this->getTimeDeskCatalogs(),
            $this->getSiguaCatalogs($user)
        );
    }

    /** Core: roles, areas, sedes, ubicaciones, campañas, puestos, schedules, area_users. */
    private function getCoreCatalogs($user): array
    {
        $guards = ['web', 'sanctum'];
        $areaUsers = collect();
        if ($user) {
            $canViewAllUsers = $user->can('tickets.manage_all') || $user->can('incidents.manage_all');
            $canViewAreaUsers = $user->can('tickets.view_area') || $user->can('incidents.view_area');
            if ($canViewAllUsers) {
                $areaUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get(['id', 'name', 'area_id', 'position_id']);
            } elseif ($canViewAreaUsers && $user->area_id) {
                $areaUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('area_id', $user->area_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'area_id', 'position_id']);
            }
        }

        $schedules = collect();
        if (Schema::hasTable('schedules')) {
            $schedules = DB::table('schedules')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }

        return [
            'campaigns' => DB::table('campaigns')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => DB::table('areas')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => DB::table('positions')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sedes' => DB::table('sites')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']),
            'ubicaciones' => DB::table('locations')
                ->join('sites', 'sites.id', '=', 'locations.sede_id')
                ->where('locations.is_active', true)
                ->orderBy('sites.name')
                ->orderBy('locations.name')
                ->get([
                    'locations.id',
                    'locations.name',
                    'locations.code',
                    'locations.sede_id',
                    'sites.name as sede_name',
                ]),
            'roles' => DB::table('roles')
                ->whereNull('deleted_at')
                ->whereIn('guard_name', $guards)
                ->orderBy('name')
                ->get(['id', 'name']),
            'permissions' => DB::table('permissions')
                ->whereIn('guard_name', $guards)
                ->orderBy('name')
                ->get(['id', 'name']),
            'schedules' => $schedules,
            'area_users' => $areaUsers,
        ];
    }

    /** Tickets / Helpdesk: prioridades, ticket_states, ticket_types, impact_levels, urgency_levels, priority_matrix. */
    private function getTicketsCatalogs($user): array
    {
        $areaUsers = collect();
        if ($user) {
            $canViewAllUsers = $user->can('tickets.manage_all') || $user->can('incidents.manage_all');
            $canViewAreaUsers = $user->can('tickets.view_area') || $user->can('incidents.view_area');
            if ($canViewAllUsers) {
                $areaUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get(['id', 'name', 'area_id', 'position_id']);
            } elseif ($canViewAreaUsers && $user->area_id) {
                $areaUsers = DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('area_id', $user->area_id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'area_id', 'position_id']);
            }
        }

        return [
            'priorities' => DB::table('priorities')->orderBy('level')->orderBy('name')->get(['id', 'name', 'level', 'is_active']),
            'impact_levels' => $this->getImpactLevels(),
            'urgency_levels' => $this->getUrgencyLevels(),
            'priority_matrix' => $this->getPriorityMatrix(),
            'ticket_states' => DB::table('ticket_states')->orderBy('name')->get(['id', 'name', 'code', 'is_active', 'is_final']),
            'ticket_types' => DB::table('ticket_types')->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'area_users' => $areaUsers,
        ];
    }

    /** Incidencias: incident_types, incident_severities, incident_statuses. */
    private function getIncidentsCatalogs(): array
    {
        if (! Schema::hasTable('incident_types')) {
            return [
                'incident_types' => collect(),
                'incident_severities' => collect(),
                'incident_statuses' => collect(),
            ];
        }
        return [
            'incident_types' => DB::table('incident_types')->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'incident_severities' => DB::table('incident_severities')->orderBy('level')->orderBy('name')->get(['id', 'name', 'code', 'level', 'is_active']),
            'incident_statuses' => DB::table('incident_statuses')->orderBy('name')->get(['id', 'name', 'code', 'is_active', 'is_final']),
        ];
    }

    /** TimeDesk: termination_reasons, employee_statuses, hire_types, recruitment_sources. */
    private function getTimeDeskCatalogs(): array
    {
        $out = [
            'termination_reasons' => collect(),
            'employee_statuses' => collect(),
            'hire_types' => collect(),
            'recruitment_sources' => collect(),
        ];
        if (Schema::hasTable('termination_reasons')) {
            $out['termination_reasons'] = DB::table('termination_reasons')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'description']);
        }
        if (Schema::hasTable('employee_statuses')) {
            $out['employee_statuses'] = DB::table('employee_statuses')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        if (Schema::hasTable('hire_types')) {
            $out['hire_types'] = DB::table('hire_types')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        if (Schema::hasTable('recruitment_sources')) {
            $out['recruitment_sources'] = DB::table('recruitment_sources')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        return $out;
    }

    /** SIGUA: sistemas (sigua_systems). Solo si el usuario tiene permiso y la tabla existe. */
    private function getSiguaCatalogs($user): array
    {
        if (! Schema::hasTable('sigua_systems')) {
            return ['sistemas' => collect()];
        }
        if ($user && ! $user->can('sigua.dashboard') && ! $user->can('sigua.cuentas.view')) {
            return ['sistemas' => collect()];
        }
        $columns = ['id', 'name', 'slug'];
        if (Schema::hasColumn('sigua_systems', 'activo')) {
            $sistemas = DB::table('sigua_systems')->where('activo', true)->orderBy('orden')->orderBy('name')->get($columns);
        } else {
            $sistemas = DB::table('sigua_systems')->orderBy('name')->get($columns);
        }
        return ['sistemas' => $sistemas];
    }

    private function getImpactLevels()
    {
        if (! Schema::hasTable('impact_levels')) {
            return collect();
        }
        return DB::table('impact_levels')->where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
    }

    private function getUrgencyLevels()
    {
        if (! Schema::hasTable('urgency_levels')) {
            return collect();
        }
        return DB::table('urgency_levels')->where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
    }

    private function getPriorityMatrix()
    {
        if (! Schema::hasTable('priority_matrix')) {
            return collect();
        }
        return DB::table('priority_matrix')->get(['impact_level_id', 'urgency_level_id', 'priority_id']);
    }
}
