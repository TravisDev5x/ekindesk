<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    private function withSedes(Cliente $cliente): Cliente
    {
        return $cliente->loadCount('sedes')->load([
            'sedes' => fn ($q) => $q->select('id', 'name', 'code', 'client_id', 'type', 'is_active', 'address', 'city', 'contact_name', 'contact_phone')->orderBy('name'),
        ]);
    }

    public function index()
    {
        return Cliente::withCount('sedes')
            ->with(['sedes' => fn ($q) => $q->select('id', 'name', 'code', 'client_id', 'type', 'is_active', 'address', 'city', 'contact_name', 'contact_phone')->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'min:2', 'unique:clients,name'],
            'code' => ['nullable', 'max:20', 'unique:clients,code'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        $cliente = Cliente::create($data);

        return response()->json($this->withSedes($cliente), 201);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'name' => ['required', 'min:2', Rule::unique('clients', 'name')->ignore($cliente->id)],
            'code' => ['nullable', 'max:20', Rule::unique('clients', 'code')->ignore($cliente->id)],
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
        if ($cliente->sedes()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay sedes asignadas a este cliente'], 422);
        }

        $cliente->delete();

        return response()->noContent();
    }
}
