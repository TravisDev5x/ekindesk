<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketTypeController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return TicketType::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()
            ->with('areas:id,name')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'ticket_types'),
            'code' => $scope->requiredUniqueCodeRule($user, 'ticket_types'),
            'area_ids' => 'array',
            'area_ids.*' => 'exists:areas,id',
            'is_active' => 'boolean',
        ]);
        $areaIds = $data['area_ids'] ?? [];
        unset($data['area_ids']);
        $type = TicketType::create(array_merge($data, $scope->operatorAttributesForCreate($user)));
        if ($areaIds !== []) {
            $type->areas()->sync($areaIds);
        }

        return response()->json($type->load('areas:id,name'), 201);
    }

    public function update(Request $request, TicketType $ticket_type)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $ticket_type);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'ticket_types', $ticket_type->id),
            'code' => $scope->requiredUniqueCodeRule($user, 'ticket_types', $ticket_type->id),
            'area_ids' => 'array',
            'area_ids.*' => 'exists:areas,id',
            'is_active' => 'boolean',
        ]);
        $areaIds = $data['area_ids'] ?? null;
        unset($data['area_ids']);
        $ticket_type->update($data);
        if ($areaIds !== null) {
            $ticket_type->areas()->sync($areaIds);
        }

        return response()->json($ticket_type->load('areas:id,name'));
    }

    public function destroy(TicketType $ticket_type)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $ticket_type);
        $ticket_type->areas()->detach();
        $ticket_type->delete();

        return response()->noContent();
    }
}
