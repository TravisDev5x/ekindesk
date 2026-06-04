<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\IncidentSeverity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentSeverityController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return IncidentSeverity::class;
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
            'name' => $scope->uniqueNameRule($user, 'incident_severities'),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_severities'),
            'level' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $severity = IncidentSeverity::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($severity, 201);
    }

    public function update(Request $request, IncidentSeverity $incident_severity)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_severity);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'incident_severities', $incident_severity->id),
            'code' => $scope->requiredUniqueCodeRule($user, 'incident_severities', $incident_severity->id),
            'level' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
        ]);
        $incident_severity->update($data);

        return response()->json($incident_severity);
    }

    public function destroy(IncidentSeverity $incident_severity)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $incident_severity);
        if ($incident_severity->level === 1) {
            return response()->json(['message' => 'No se puede eliminar la severidad base'], 422);
        }
        $incident_severity->delete();

        return response()->noContent();
    }
}
