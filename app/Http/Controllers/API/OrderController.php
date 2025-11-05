<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['client', 'product'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        $order = $request->user()->orders()->create($request->all());
        $order->load(['client', 'product']);

        return response()->json($order, 201);
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $order->load(['client', 'product']);
        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'status' => 'sometimes|in:pending,confirmed,delivered,cancelled',
            'quantity' => 'sometimes|integer|min:1',
            'total' => 'sometimes|numeric|min:0',
        ]);

        $order->update($request->all());
        $order->load(['client', 'product']);

        return response()->json($order);
    }

    public function destroy(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}