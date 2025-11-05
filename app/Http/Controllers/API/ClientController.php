<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = $request->user()
            ->clients()
            ->with('appointments.service')
            ->latest()
            ->get();

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $client = $request->user()->clients()->create($request->all());

        return response()->json($client, 201);
    }

    public function show(Request $request, Client $client)
    {
        // Vérifier que le client appartient à l'utilisateur
        if ($client->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $client->load(['appointments.service', 'messages', 'orders.product']);

        return response()->json($client);
    }

    public function update(Request $request, Client $client)
    {
        if ($client->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'sometimes|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $client->update($request->all());

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client)
    {
        if ($client->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $client->delete();

        return response()->json(['message' => 'Cliente supprimée avec succès']);
    }
}
