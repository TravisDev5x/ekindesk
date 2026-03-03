<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UrgencyLevelController extends Controller
{
    public function index()
    {
        return UrgencyLevel::orderBy('weight')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|min:2|unique:urgency_levels,name',
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $item = UrgencyLevel::create($data);
        return response()->json($item, 201);
    }

    public function update(Request $request, UrgencyLevel $urgency_level)
    {
        $data = $request->validate([
            'name' => ['required', 'min:2', Rule::unique('urgency_levels', 'name')->ignore($urgency_level->id)],
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $urgency_level->update($data);
        return response()->json($urgency_level);
    }

    public function destroy(UrgencyLevel $urgency_level)
    {
        $urgency_level->delete();
        return response()->noContent();
    }
}
