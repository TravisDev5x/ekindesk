<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImpactLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImpactLevelController extends Controller
{
    public function index()
    {
        return ImpactLevel::orderBy('weight')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|min:2|unique:impact_levels,name',
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $item = ImpactLevel::create($data);
        return response()->json($item, 201);
    }

    public function update(Request $request, ImpactLevel $impact_level)
    {
        $data = $request->validate([
            'name' => ['required', 'min:2', Rule::unique('impact_levels', 'name')->ignore($impact_level->id)],
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $impact_level->update($data);
        return response()->json($impact_level);
    }

    public function destroy(ImpactLevel $impact_level)
    {
        $impact_level->delete();
        return response()->noContent();
    }
}
