<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return Position::class;
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
            'name' => $scope->uniqueNameRule($user, 'positions'),
            'is_active' => 'boolean',
        ]);

        $position = Position::create(array_merge([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ], $scope->operatorAttributesForCreate($user)));

        return response()->json($position, 201);
    }

    public function update(Request $request, Position $position)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $position);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'positions', $position->id),
            'is_active' => 'boolean',
        ]);

        $position->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $position->is_active,
        ]);

        return response()->json($position);
    }

    public function destroy(Position $position)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $position);
        $position->delete();

        return response()->noContent();
    }
}
