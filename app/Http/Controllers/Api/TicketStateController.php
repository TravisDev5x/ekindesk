<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\TicketState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketStateController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return TicketState::class;
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
            'name' => $scope->uniqueNameRule($user, 'ticket_states'),
            'code' => $scope->requiredUniqueCodeRule($user, 'ticket_states'),
            'is_active' => 'boolean',
            'is_final' => 'boolean',
        ]);
        $state = TicketState::create(array_merge($data, $scope->operatorAttributesForCreate($user)));

        return response()->json($state, 201);
    }

    public function update(Request $request, TicketState $ticket_state)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $ticket_state);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'ticket_states', $ticket_state->id),
            'code' => $scope->requiredUniqueCodeRule($user, 'ticket_states', $ticket_state->id),
            'is_active' => 'boolean',
            'is_final' => 'boolean',
        ]);
        $ticket_state->update($data);

        return response()->json($ticket_state);
    }

    public function destroy(TicketState $ticket_state)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $ticket_state);
        if ($ticket_state->is_final) {
            return response()->json(['message' => 'No se puede eliminar un estado final'], 422);
        }
        $ticket_state->delete();

        return response()->noContent();
    }
}
