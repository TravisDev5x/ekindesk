<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $siteId = $request->query('site_id');
        $query = Location::with('site:id,name,type');
        if ($siteId) {
            $query->where('site_id', $siteId);
        }
        return $query->orderBy('site_id')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'name' => ['required', 'min:2'],
            'code' => ['nullable', 'max:20', 'unique:locations,code'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $data['is_active'] ?? true;

        $siteId = $data['site_id'];
        // unique per site
        $request->validate([
            'name' => Rule::unique('locations', 'name')->where('site_id', $siteId),
        ]);

        $location = Location::create($data);
        return response()->json($location, 201);
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'site_id' => ['required', 'exists:sites,id'],
            'name' => ['required', 'min:2'],
            'code' => ['nullable', 'max:20', Rule::unique('locations', 'code')->ignore($location->id)],
            'is_active' => ['boolean'],
        ]);
        $request->validate([
            'name' => Rule::unique('locations', 'name')
                ->where('site_id', $data['site_id'])
                ->ignore($location->id),
        ]);

        $location->update($data);
        return response()->json($location);
    }

    public function destroy(Location $location)
    {
        if ($location->users()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay usuarios asignados'], 422);
        }
        $location->delete();
        return response()->noContent();
    }
}
