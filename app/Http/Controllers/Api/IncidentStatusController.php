<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\IncidentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentStatusController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return IncidentStatus::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()->orderBy('is_final')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'incident_statuses'),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_statuses'),
            'is_active' => 'boolean',
            'is_final' => 'boolean',
        ]);
        $status = IncidentStatus::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($status, 201);
    }

    public function update(Request $request, IncidentStatus $incident_status)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_status);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'incident_statuses', $incident_status->id),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_statuses', $incident_status->id),
            'is_active' => 'boolean',
            'is_final' => 'boolean',
        ]);
        $incident_status->update($data);

        return response()->json($incident_status);
    }

    public function destroy(IncidentStatus $incident_status)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_status);
        if ($incident_status->is_final) {
            return response()->json(['message' => 'No se puede eliminar un estado final'], 422);
        }
        $incident_status->delete();

        return response()->noContent();
    }
}
