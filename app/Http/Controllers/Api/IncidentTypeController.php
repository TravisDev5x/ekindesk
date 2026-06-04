<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentTypeController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return IncidentType::class;
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
            'name' => $scope->uniqueNameRule($user, 'incident_types'),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_types'),
            'is_active' => 'boolean',
        ]);
        $type = IncidentType::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($type, 201);
    }

    public function update(Request $request, IncidentType $incident_type)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_type);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'incident_types', $incident_type->id),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_types', $incident_type->id),
            'is_active' => 'boolean',
        ]);
        $incident_type->update($data);

        return response()->json($incident_type);
    }

    public function destroy(IncidentType $incident_type)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_type);
        $incident_type->delete();

        return response()->noContent();
    }
}
