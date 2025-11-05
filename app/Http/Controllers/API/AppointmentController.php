<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = $request->user()
            ->appointments()
            ->with(['client', 'service'])
            ->latest('appointment_date')
            ->get();

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $appointment = $request->user()->appointments()->create($request->all());
        $appointment->load(['client', 'service']);

        return response()->json($appointment, 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $appointment->load(['client', 'service']);
        return response()->json($appointment);
    }

    public function update(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'service_id' => 'sometimes|exists:services,id',
            'appointment_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($request->all());
        $appointment->load(['client', 'service']);

        return response()->json($appointment);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $appointment->delete();
        return response()->json(['message' => 'Rendez-vous supprimé avec succès']);
    }
}