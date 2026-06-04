<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\OperatorScopeService;
use App\Services\TenantContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function __construct(
        protected OperatorScopeService $operatorScope
    ) {}

    private function withSedes(Cliente $cliente): Cliente
    {
        return $cliente->loadCount('sedes')->load([
            'sedes' => fn ($q) => $q->select('id', 'name', 'code', 'client_id', 'type', 'is_active', 'address', 'city', 'contact_name', 'contact_phone')->orderBy('name'),
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $query = $this->operatorScope->applyOnClients(
            Cliente::withCount('sedes')
                ->with(['sedes' => fn ($q) => $q->select('id', 'name', 'code', 'client_id', 'type', 'is_active', 'address', 'city', 'contact_name', 'contact_phone')->orderBy('name')]),
            $user
        );

        return $query->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $data = $request->validate([
            'name' => $this->operatorScope->nameRules($user),
            'code' => $this->operatorScope->codeRules($user),
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        $data['operator_user_id'] = $this->operatorScope->operatorUserIdForNewClient($user);
        if (empty($data['portal_slug'])) {
            $data['portal_slug'] = TenantContextService::generateUniquePortalSlug($data['name']);
        }

        $cliente = Cliente::create($data);

        return response()->json($this->withSedes($cliente), 201);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $this->operatorScope->authorizeClient($user, $cliente);

        $data = $request->validate([
            'name' => $this->operatorScope->nameRules($user, $cliente->id),
            'code' => $this->operatorScope->codeRules($user, $cliente->id),
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        $cliente->update($data);

        return response()->json($this->withSedes($cliente));
    }

    public function destroy(Cliente $cliente)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $this->operatorScope->authorizeClient($user, $cliente);

        if ($cliente->sedes()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay sedes asignadas a este cliente'], 422);
        }

        $cliente->delete();

        return response()->noContent();
    }
}
