<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sede;
use App\Models\TicketState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClienteController extends Controller
{
    protected const INDUSTRIES = [
        'Tecnología', 'Retail', 'Manufactura', 'Salud',
        'Educación', 'Logística', 'Finanzas',
        'Gobierno', 'Servicios', 'Otro',
    ];

    private function canViewAllClients(): bool
    {
        $user = auth()->user();

        return $user->hasRole('super_admin') || $user->can('clients.view_all');
    }

    private function clientQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return Cliente::query();
        }

        if ($user->can('clients.view_all')) {
            return Cliente::query();
        }

        return Cliente::forOperator($user->id);
    }

    private function authorizeClient(Cliente $client): void
    {
        if ($this->canViewAllClients()) {
            return;
        }

        if ((int) $client->operator_user_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    public function index(): Response
    {
        $user = auth()->user();
        $showOperatorColumn = $user->hasRole('super_admin') || $user->can('clients.view_all');

        $query = $this->clientQuery()
            ->withCount(['sedes', 'tickets'])
            ->orderBy('name');

        if ($showOperatorColumn) {
            $query->with('operatorUser:id,name');
        }

        $clients = $query->paginate(15);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'total' => $this->clientQuery()->count(),
            'showOperatorColumn' => $showOperatorColumn,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Form', [
            'client' => null,
            'industries' => self::INDUSTRIES,
            'sites' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateClient($request);

        $cliente = DB::transaction(function () use ($request, $validated) {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store(
                    'client-logos/'.auth()->id(),
                    'public'
                );
            }

            $cliente = Cliente::create([
                'name' => $validated['business_name'],
                'industry' => $validated['industry'] ?? null,
                'tax_id' => $validated['rfc'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'logo_path' => $logoPath,
                'is_active' => $validated['is_active'] ?? true,
                'operator_user_id' => auth()->id(),
            ]);

            foreach ($validated['sites'] ?? [] as $site) {
                if (empty($site['name'])) {
                    continue;
                }
                Sede::create([
                    'client_id' => $cliente->id,
                    'name' => $site['name'],
                    'address' => $site['address'] ?? null,
                    'city' => $site['city'] ?? null,
                    'type' => 'physical',
                    'is_active' => true,
                ]);
            }

            return $cliente;
        });

        return redirect()
            ->route('clients.show', $cliente)
            ->with('success', 'Cliente creado correctamente');
    }

    public function show(Cliente $client): Response
    {
        $this->authorizeClient($client);
        $client->load('sedes');

        $finalStateIds = TicketState::where('is_final', true)->pluck('id');

        $ticketsSummary = [
            'open' => $client->tickets()->whereNotIn('ticket_state_id', $finalStateIds)->count(),
            'closed' => $client->tickets()->whereIn('ticket_state_id', $finalStateIds)->count(),
            'overdue' => $client->tickets()
                ->whereNotIn('ticket_state_id', $finalStateIds)
                ->where(function ($q) {
                    $q->whereNotNull('due_at')->where('due_at', '<', now())
                        ->orWhere(function ($q2) {
                            $q2->whereNull('due_at')
                                ->where('created_at', '<', now()->subHours(72));
                        });
                })
                ->count(),
        ];

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'tickets_summary' => $ticketsSummary,
            'sites' => $client->sedes,
            'industries' => self::INDUSTRIES,
        ]);
    }

    public function edit(Cliente $client): Response
    {
        $this->authorizeClient($client);

        return Inertia::render('Clients/Form', [
            'client' => $client->load('sedes'),
            'industries' => self::INDUSTRIES,
            'sites' => $client->sedes,
        ]);
    }

    public function update(Request $request, Cliente $client)
    {
        $this->authorizeClient($client);
        $validated = $this->validateClient($request, $client);

        DB::transaction(function () use ($request, $client, $validated) {
            if ($request->hasFile('logo')) {
                if ($client->logo_path) {
                    Storage::disk('public')->delete($client->logo_path);
                }
                $client->logo_path = $request->file('logo')->store(
                    'client-logos/'.auth()->id(),
                    'public'
                );
            }

            $client->update([
                'name' => $validated['business_name'],
                'industry' => $validated['industry'] ?? null,
                'tax_id' => $validated['rfc'] ?? null,
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $this->syncSites($client, $validated['sites'] ?? []);
        });

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Cliente actualizado');
    }

    public function destroy(Cliente $client)
    {
        $this->authorizeClient($client);

        $finalStateIds = TicketState::where('is_final', true)->pluck('id');
        $openTickets = $client->tickets()->whereNotIn('ticket_state_id', $finalStateIds)->count();

        if ($openTickets > 0) {
            return back()->withErrors([
                'client' => 'No se puede eliminar un cliente con tickets abiertos',
            ]);
        }

        if ($client->logo_path) {
            Storage::disk('public')->delete($client->logo_path);
        }

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente eliminado');
    }

    private function validateClient(Request $request, ?Cliente $client = null): array
    {
        $uniqueName = 'unique:clients,name';
        if ($client) {
            $uniqueName .= ','.$client->id;
        }

        return $request->validate([
            'business_name' => ['required', 'string', 'max:255', $uniqueName],
            'industry' => 'nullable|string|max:255',
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'phone' => 'nullable|string|size:10|regex:/^\d{10}$/',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
            'sites' => 'nullable|array|max:20',
            'sites.*.id' => 'nullable|integer|exists:sites,id',
            'sites.*.name' => 'required_with:sites|string|max:255',
            'sites.*.address' => 'nullable|string|max:500',
            'sites.*.city' => 'nullable|string|max:255',
        ]);
    }

    private function syncSites(Cliente $client, array $sites): void
    {
        $submittedIds = collect($sites)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $client->sedes()->whereNotIn('id', $submittedIds)->delete();

        foreach ($sites as $site) {
            if (empty($site['name'])) {
                continue;
            }
            if (! empty($site['id'])) {
                $client->sedes()->where('id', $site['id'])->update([
                    'name' => $site['name'],
                    'address' => $site['address'] ?? null,
                    'city' => $site['city'] ?? null,
                ]);
            } else {
                Sede::create([
                    'client_id' => $client->id,
                    'name' => $site['name'],
                    'address' => $site['address'] ?? null,
                    'city' => $site['city'] ?? null,
                    'type' => 'physical',
                    'is_active' => true,
                ]);
            }
        }
    }
}
