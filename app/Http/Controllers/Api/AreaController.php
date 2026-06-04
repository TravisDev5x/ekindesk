<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return Area::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'areas'),
            'is_active' => 'boolean',
        ]);

        $area = Area::create(array_merge([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ], $scope->operatorAttributesForCreate($user)));

        return response()->json($area, 201);
    }

    public function update(Request $request, Area $area)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $area);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'areas', $area->id),
            'is_active' => 'boolean',
        ]);

        $area->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $area->is_active,
        ]);

        return response()->json($area);
    }

    public function destroy(Area $area)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $area);
        $area->delete();

        return response()->noContent();
    }
}
