<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Priority;
use App\Models\TicketState;
use App\Models\TicketType;
use App\Services\ClientScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResolbebIndexController extends Controller
{
    public function __construct(
        protected ClientScopeService $clientScope
    ) {}

    public function index(Request $request): Response
    {
        return $this->renderIndex($request, 'tickets');
    }

    public function misTickets(Request $request): Response
    {
        return $this->renderIndex($request, 'mis-tickets');
    }

    private function renderIndex(Request $request, string $mode): Response
    {
        return Inertia::render('Resolbeb/Index', [
            'mode' => $mode,
            'catalogs' => $this->catalogsForFilters($request->user()),
        ]);
    }

    /**
     * Catálogos para filtros y TicketCreateDialog (equivalente a loadCatalogs core+tickets).
     *
     * @return array<string, mixed>
     */
    private function catalogsForFilters($user): array
    {
        $areaUsers = collect();
        if ($user) {
            $areaUsers = $this->clientScope
                ->usersQueryForCatalog($user)
                ->orderBy('name')
                ->get(['id', 'name', 'area_id', 'position_id']);
        }

        $sedes = $this->clientScope->sedesQueryForUser($user)->orderBy('name')->get(['id', 'name', 'type', 'client_id']);
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
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sedes' => $sedes,
            'ubicaciones' => $ubicacionesQuery
                ->orderBy('sites.name')
                ->orderBy('locations.name')
                ->get([
                    'locations.id',
                    'locations.name',
                    'locations.sede_id',
                ]),
            'priorities' => Priority::orderBy('level')->orderBy('name')->get(['id', 'name', 'level', 'is_active']),
            'ticket_states' => TicketState::orderBy('name')->get(['id', 'name', 'code', 'is_active', 'is_final']),
            'ticket_types' => TicketType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'area_users' => $areaUsers,
        ];
    }
}
