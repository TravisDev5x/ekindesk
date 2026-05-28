<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SedeController extends Controller
{
    private function siteRules(bool $forUpdate = false, ?int $ignoreId = null): array
    {
        $nameRule = ['required', 'min:2', Rule::unique('sites', 'name')];
        $codeRule = ['nullable', 'max:20', Rule::unique('sites', 'code')];

        if ($forUpdate && $ignoreId !== null) {
            $nameRule = ['required', 'min:2', Rule::unique('sites', 'name')->ignore($ignoreId)];
            $codeRule = ['nullable', 'max:20', Rule::unique('sites', 'code')->ignore($ignoreId)];
        }

        return [
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'name' => $nameRule,
            'code' => $codeRule,
            'type' => ['required', Rule::in(['physical', 'virtual'])],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function index()
    {
        return Sede::with('cliente:id,name')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->siteRules());

        $sede = Sede::create($data);
        $sede->load('cliente:id,name');

        return response()->json($sede, 201);
    }

    public function update(Request $request, Sede $sede)
    {
        $data = $request->validate($this->siteRules(true, $sede->id));

        $sede->update($data);
        $sede->load('cliente:id,name');

        return response()->json($sede);
    }

    public function destroy(Sede $sede)
    {
        if ($sede->code === 'REMOTO') {
            return response()->json(['message' => 'La sede Remoto no puede eliminarse'], 422);
        }
        if ($sede->users()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay usuarios asignados'], 422);
        }
        $sede->delete();

        return response()->noContent();
    }
}
