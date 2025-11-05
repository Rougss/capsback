<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = $request->user()->services()->latest()->get();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        $service = $request->user()->services()->create($request->all());
        return response()->json($service, 201);
    }

    public function show(Request $request, Service $service)
    {
        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json($service);
    }

    public function update(Request $request, Service $service)
    {
        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'duration' => 'sometimes|integer|min:1',
        ]);

        $service->update($request->all());
        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        if ($service->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $service->delete();
        return response()->json(['message' => 'Service supprimé avec succès']);
    }
}
