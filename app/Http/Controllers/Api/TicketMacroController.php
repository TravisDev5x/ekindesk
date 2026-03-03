<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketMacro;
use Illuminate\Http\Request;

class TicketMacroController extends Controller
{
    /**
     * Listado: con ?active_only=1 solo activas (para dropdown en tickets);
     * sin parámetro devuelve todas (para catálogo admin).
     */
    public function index(Request $request)
    {
        $query = TicketMacro::query()->orderBy('category')->orderBy('name');
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
            return $query->get(['id', 'name', 'content', 'category']);
        }
        return $query->get();
    }

    public function show(TicketMacro $ticket_macro)
    {
        return $ticket_macro;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $data['is_active'] ?? true;
        $macro = TicketMacro::create($data);
        return response()->json($macro, 201);
    }

    public function update(Request $request, TicketMacro $ticket_macro)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);
        $ticket_macro->update($data);
        return response()->json($ticket_macro);
    }

    public function destroy(TicketMacro $ticket_macro)
    {
        $ticket_macro->delete();
        return response()->noContent();
    }
}
