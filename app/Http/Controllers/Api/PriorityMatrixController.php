<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImpactLevel;
use App\Models\UrgencyLevel;
use App\Models\Priority;
use App\Models\PriorityMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PriorityMatrixController extends Controller
{
    /**
     * Retorna catálogos completos y la configuración actual de la matriz.
     */
    public function index()
    {
        $impactLevels = ImpactLevel::where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
        $urgencyLevels = UrgencyLevel::where('is_active', true)->orderBy('weight')->get(['id', 'name', 'weight']);
        $priorities = Priority::orderBy('level')->orderBy('name')->get(['id', 'name', 'level']);
        $matrix = PriorityMatrix::all(['impact_level_id', 'urgency_level_id', 'priority_id']);

        return response()->json([
            'impact_levels' => $impactLevels,
            'urgency_levels' => $urgencyLevels,
            'priorities' => $priorities,
            'matrix' => $matrix,
        ]);
    }

    /**
     * Actualiza masivamente la configuración de la matriz (reemplaza toda la tabla).
     */
    public function updateBulk(Request $request)
    {
        $validated = $request->validate([
            'matrix' => 'required|array',
            'matrix.*.impact_level_id' => ['required', 'integer', Rule::exists('impact_levels', 'id')],
            'matrix.*.urgency_level_id' => ['required', 'integer', Rule::exists('urgency_levels', 'id')],
            'matrix.*.priority_id' => ['required', 'integer', Rule::exists('priorities', 'id')],
        ]);

        DB::transaction(function () use ($validated) {
            PriorityMatrix::query()->delete();
            $rows = collect($validated['matrix'])->map(fn ($row) => [
                'impact_level_id' => (int) $row['impact_level_id'],
                'urgency_level_id' => (int) $row['urgency_level_id'],
                'priority_id' => (int) $row['priority_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();
            if (!empty($rows)) {
                PriorityMatrix::insert($rows);
            }
        });

        $matrix = PriorityMatrix::all(['impact_level_id', 'urgency_level_id', 'priority_id']);
        return response()->json(['matrix' => $matrix], 200);
    }
}
