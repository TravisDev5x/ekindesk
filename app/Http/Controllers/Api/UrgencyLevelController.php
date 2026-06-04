<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\UrgencyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UrgencyLevelController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return UrgencyLevel::class;
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
            'name' => $scope->uniqueNameRule($user, 'urgency_levels'),
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $item = UrgencyLevel::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($item, 201);
    }

    public function update(Request $request, UrgencyLevel $urgency_level)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $urgency_level);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'urgency_levels', $urgency_level->id),
            'weight' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $urgency_level->update($data);

        return response()->json($urgency_level);
    }

    public function destroy(UrgencyLevel $urgency_level)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $urgency_level);
        $urgency_level->delete();

        return response()->noContent();
    }
}
