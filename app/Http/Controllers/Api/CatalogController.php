<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ClientScopeService;
use App\Services\OperatorCatalogScopeService;

class CatalogController extends Controller
{
    public function __construct(
        protected ClientScopeService $clientScope,
        protected OperatorCatalogScopeService $catalogScope
    ) {}

    /** TTL caché catálogos (segundos). */
    private const CATALOG_CACHE_TTL = 600; // 10 minutos

    /** Módulos permitidos para carga parcial. */
    private const ALLOWED_MODULES = ['core', 'tickets', 'incidents'];

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

    private function scopedCatalogTable(string $table)
    {
        return $this->catalogScope->apply(DB::table($table), Auth::user(), $table);
    }

    private function cacheKey($user, ?array $modules): string
    {
        $operatorKey = 'guest';
        if ($user) {
            $operatorKey = app(\App\Services\OperatorScopeService::class)->resolveOperatorUserId($user)
                ?? ('u'.$user->id);
        }

        $tenant = app(\App\Services\TenantContextService::class)->current();
        if ($tenant->isClientPortal() && $tenant->clientId) {
            $operatorKey .= '.portal'.$tenant->clientId;
            if ($this->catalogScope->usesPerClientCatalogInPortal()) {
                $operatorKey .= '.perClient';
            }
        }

        $base = 'catalogs.v4.'.$operatorKey.'.'.($user ? $user->id : 'guest');
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
            $this->getIncidentsCatalogs()
        );
    }

    /** Core: roles, areas, sedes, ubicaciones, campañas, puestos, area_users. */
    private function getCoreCatalogs($user): array
    {
        $guards = ['web', 'sanctum'];
        $areaUsers = collect();
        if ($user) {
            $areaUsers = $this->clientScope
                ->usersQueryForCatalog($user)
                ->orderBy('name')
                ->get(['id', 'name', 'area_id', 'position_id']);
        }

        $sedesQuery = $this->clientScope->sedesQueryForUser($user)->orderBy('name');
        $sedes = $sedesQuery->get(['id', 'name', 'type', 'client_id']);
        $sedeIds = $sedes->pluck('id');

        $ubicacionesQuery = DB::table('locations')
            ->join('sites', 'sites.id', '=', 'locations.sede_id')
            ->where('locations.is_active', true);
        if ($sedeIds->isNotEmpty()) {
            $ubicacionesQuery->whereIn('locations.sede_id', $sedeIds);
        } else {
            $ubicacionesQuery->whereRaw('0 = 1');
        }

        return [
            'clients' => $user ? $this->clientScope->clientsForCatalog($user) : [],
            'campaigns' => $this->scopedCatalogTable('campaigns')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => $this->scopedCatalogTable('areas')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => $this->scopedCatalogTable('positions')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sedes' => $sedes,
            'ubicaciones' => $ubicacionesQuery
                ->orderBy('sites.name')
                ->orderBy('locations.name')
                ->get([
                    'locations.id',
                    'locations.name',
                    'locations.code',
                    'locations.sede_id',
                    'sites.name as sede_name',
                    'sites.client_id',
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
            'area_users' => $areaUsers,
        ];
    }

    /** Tickets / Helpdesk: prioridades, ticket_states, ticket_types, impact_levels, urgency_levels, priority_matrix. */
    private function getTicketsCatalogs($user): array
    {
        $areaUsers = collect();
        if ($user) {
            $areaUsers = $this->clientScope
                ->usersQueryForCatalog($user)
                ->orderBy('name')
                ->get(['id', 'name', 'area_id', 'position_id']);
        }

        return [
            'priorities' => $this->scopedCatalogTable('priorities')->orderBy('level')->orderBy('name')->get(['id', 'name', 'level', 'is_active']),
            'impact_levels' => $this->getImpactLevels(),
            'urgency_levels' => $this->getUrgencyLevels(),
            'priority_matrix' => $this->getPriorityMatrix(),
            'ticket_states' => $this->scopedCatalogTable('ticket_states')->orderBy('name')->get(['id', 'name', 'code', 'is_active', 'is_final']),
            'ticket_types' => $this->scopedCatalogTable('ticket_types')->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
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
            'incident_types' => $this->scopedCatalogTable('incident_types')->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'incident_severities' => $this->scopedCatalogTable('incident_severities')->orderBy('level')->orderBy('name')->get(['id', 'name', 'code', 'level', 'is_active']),
            'incident_statuses' => $this->scopedCatalogTable('incident_statuses')->orderBy('name')->get(['id', 'name', 'code', 'is_active', 'is_final']),
        ];
    }

    private function getImpactLevels()
    {
        if (! Schema::hasTable('impact_levels')) {
            return collect();
        }
        return $this->scopedCatalogTable('impact_levels')->where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
    }

    private function getUrgencyLevels()
    {
        if (! Schema::hasTable('urgency_levels')) {
            return collect();
        }
        return $this->scopedCatalogTable('urgency_levels')->where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
    }

    private function getPriorityMatrix()
    {
        if (! Schema::hasTable('priority_matrix')) {
            return collect();
        }
        return DB::table('priority_matrix')->get(['impact_level_id', 'urgency_level_id', 'priority_id']);
    }
}
