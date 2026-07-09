<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Campaign;
use App\Models\Site;
use App\Models\Location;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserListingService
{
    public function __construct(
        protected ClientScopeService $clientScope
    ) {}

    public function paginate(Request $request, ?User $actor = null): LengthAwarePaginator
    {
        $query = User::with(['campaign', 'area', 'position', 'site', 'location', 'roles']);

        if ($actor) {
            $this->clientScope->applyUserScope($query, $actor);
        }

        $this->applyFilters($query, $request);

        $sortable = ['id', 'name', 'employee_number', 'email', 'status', 'created_at'];
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, $sortable, true)) {
            $sort = 'id';
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        return $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'employee_number' => $user->employee_number,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'paternal_last_name' => $user->paternal_last_name,
                'maternal_last_name' => $user->maternal_last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'campaign' => $user->campaign->name ?? 'Sin Asignar',
                'area' => $user->area->name ?? 'Sin Asignar',
                'position' => $user->position->name ?? 'Sin Asignar',
                'sede' => $user->site->name ?? 'Sin Asignar',
                'ubicacion' => $user->location->name ?? null,
                'status' => $user->status,
                'is_blacklisted' => (bool) $user->is_blacklisted,
                'roles' => $user->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()->all(),
                'deleted_at' => $user->deleted_at,
            ]);
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($request->input('status') === 'only') {
            $query->onlyTrashed();
        } else {
            if ($request->filled('user_status') && in_array($request->input('user_status'), ['active', 'pending_admin', 'pending_email', 'blocked'], true)) {
                $query->where('status', $request->input('user_status'));
            }
            if ($request->filled('blacklist')) {
                if (in_array($request->input('blacklist'), ['1', 'yes'], true)) {
                    $query->where('is_blacklisted', true);
                }
                if (in_array($request->input('blacklist'), ['0', 'no'], true)) {
                    $query->where('is_blacklisted', false);
                }
            }
        }

        if ($search = $request->input('search')) {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('paternal_last_name', 'like', $term)
                    ->orWhere('maternal_last_name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        if ($request->filled('campaign')) {
            $campaignId = Campaign::where('name', $request->input('campaign'))->value('id');
            if ($campaignId !== null) {
                $query->where('campaign_id', $campaignId);
            }
        }
        if ($request->filled('area')) {
            $areaId = Area::where('name', $request->input('area'))->value('id');
            if ($areaId !== null) {
                $query->where('area_id', $areaId);
            }
        }
        if ($request->filled('sede')) {
            $sedeId = Site::where('name', $request->input('sede'))->value('id');
            if ($sedeId !== null) {
                $query->where('site_id', $sedeId);
            }
        }
        if ($request->filled('ubicacion')) {
            $ubicacionId = Location::where('name', $request->input('ubicacion'))->value('id');
            if ($ubicacionId !== null) {
                $query->where('location_id', $ubicacionId);
            }
        }
        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            if ($roleId > 0) {
                $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
            }
        }
    }
}
