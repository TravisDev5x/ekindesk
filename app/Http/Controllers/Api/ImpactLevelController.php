<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\ImpactLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpactLevelController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return ImpactLevel::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()->orderBy('weight')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'impact_levels'),
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $item = ImpactLevel::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($item, 201);
    }

    public function update(Request $request, ImpactLevel $impact_level)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $impact_level);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'impact_levels', $impact_level->id),
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $impact_level->update($data);

        return response()->json($impact_level);
    }

    public function destroy(ImpactLevel $impact_level)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $impact_level);
        $impact_level->delete();

        return response()->noContent();
    }
}
