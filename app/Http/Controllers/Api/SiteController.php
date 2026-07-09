<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\OperatorScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function __construct(
        protected OperatorScopeService $operatorScope
    ) {}

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
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $query = $this->operatorScope->applyOnSites(
            Site::with('client:id,name'),
            $user
        );

        return $query->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $data = $request->validate($this->siteRules());
        if (! empty($data['client_id']) && ! $this->operatorScope->assertClientIdInScope($user, (int) $data['client_id'])) {
            return response()->json(['message' => 'Cliente no válido para tu organización'], 422);
        }

        $site = Site::create($data);
        $site->load('client:id,name');

        return response()->json($site, 201);
    }

    public function update(Request $request, Site $site)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $this->operatorScope->authorizeSite($user, $site);

        $data = $request->validate($this->siteRules(true, $site->id));
        if (! empty($data['client_id']) && ! $this->operatorScope->assertClientIdInScope($user, (int) $data['client_id'])) {
            return response()->json(['message' => 'Cliente no válido para tu organización'], 422);
        }

        $site->update($data);
        $site->load('client:id,name');

        return response()->json($site);
    }

    public function destroy(Site $site)
    {
        if ($site->code === 'REMOTO') {
            return response()->json(['message' => 'La sede Remoto no puede eliminarse'], 422);
        }
        if ($site->users()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay usuarios asignados'], 422);
        }
        $site->delete();

        return response()->noContent();
    }
}
