<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Campaign;
use App\Models\Position;
use App\Models\Role;
use App\Models\Site;
use App\Services\ClientScopeService;
use App\Services\UserListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserListingService $listing,
        protected ClientScopeService $clientScope
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('users.manage'), 403);

        $actor = $request->user();
        $users = $this->listing->paginate($request, $actor);

        $siteIds = $this->clientScope->sitesQueryForUser($actor)->pluck('id');

        $locationsQuery = DB::table('locations')
            ->join('sites', 'sites.id', '=', 'locations.site_id')
            ->where('locations.is_active', true);
        if ($siteIds->isNotEmpty()) {
            $locationsQuery->whereIn('locations.site_id', $siteIds);
        } else {
            $locationsQuery->whereRaw('0 = 1');
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'catalogs' => [
                'roles' => Role::whereNull('deleted_at')
                    ->whereIn('guard_name', ['web', 'sanctum'])
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'campaigns' => Campaign::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'positions' => Position::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'sites' => Site::query()
                    ->when(! $this->clientScope->bypassesClientScope($actor), function ($q) use ($actor) {
                        $clientId = $this->clientScope->resolveUserClientId($actor);
                        if ($clientId) {
                            $q->where('client_id', $clientId);
                        } else {
                            $q->whereRaw('0 = 1');
                        }
                    })
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'locations' => $locationsQuery
                    ->orderBy('sites.name')
                    ->orderBy('locations.name')
                    ->get([
                        'locations.id',
                        'locations.name',
                        'locations.site_id',
                        'sites.name as site_name',
                    ]),
            ],
            'filters' => $request->only([
                'search',
                'user_status',
                'blacklist',
                'role_id',
                'campaign',
                'area',
                'site',
                'location',
                'status',
                'per_page',
            ]),
        ]);
    }
}
