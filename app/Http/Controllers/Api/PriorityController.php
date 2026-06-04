<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\Priority;
use App\Services\OperatorCatalogScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriorityController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return Priority::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()->orderBy('level')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'priorities'),
            'level' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $priority = Priority::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($priority, 201);
    }

    public function update(Request $request, Priority $priority)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $priority);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'priorities', $priority->id),
            'level' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $priority->update($data);

        return response()->json($priority);
    }

    public function destroy(Priority $priority)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $priority);
        if ($priority->level === 1) {
            return response()->json(['message' => 'No se puede eliminar prioridad base'], 422);
        }
        $priority->delete();

        return response()->noContent();
    }
}
