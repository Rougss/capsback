<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Récupérer toutes les ventes
     */
    public function index(Request $request)
    {
        $query = $request->user()->sales()->with(['client', 'service', 'product']);

        // Filtres optionnels
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('payment_method')) {
            $query->byPaymentMethod($request->payment_method);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inPeriod($request->start_date, $request->end_date);
        }

        $sales = $query->latest('sale_date')->paginate(50);

        return response()->json($sales);
    }

    /**
     * Créer une nouvelle vente
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:service,product',
            'client_id' => 'nullable|exists:clients,id',
            'service_id' => 'required_if:type,service|exists:services,id',
            'product_id' => 'required_if:type,product|exists:products,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'amount' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,wave,orange_money,bank_transfer,other',
            'notes' => 'nullable|string',
            'sale_date' => 'required|date',
        ]);

        // Calculer le total
        $total = $request->amount * $request->quantity;

        // Si c'est une vente de produit, vérifier et réduire le stock
        if ($request->type === 'product') {
            $product = Product::findOrFail($request->product_id);
            
            if ($product->stock < $request->quantity) {
                return response()->json([
                    'message' => 'Stock insuffisant. Stock disponible: ' . $product->stock
                ], 400);
            }

            // Réduire le stock
            $product->decrement('stock', $request->quantity);
        }

        // Créer la vente
        $sale = $request->user()->sales()->create([
            'client_id' => $request->client_id,
            'type' => $request->type,
            'service_id' => $request->service_id,
            'product_id' => $request->product_id,
            'appointment_id' => $request->appointment_id,
            'amount' => $request->amount,
            'quantity' => $request->quantity,
            'total' => $total,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'sale_date' => $request->sale_date,
        ]);

        // Charger les relations
        $sale->load(['client', 'service', 'product']);

        return response()->json([
            'message' => 'Vente enregistrée avec succès',
            'sale' => $sale
        ], 201);
    }

    /**
     * Afficher une vente
     */
    public function show(Request $request, Sale $sale)
    {
        if ($sale->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $sale->load(['client', 'service', 'product', 'appointment']);

        return response()->json($sale);
    }

    /**
     * Mettre à jour une vente
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'payment_method' => 'sometimes|in:cash,wave,orange_money,bank_transfer,other',
            'notes' => 'nullable|string',
        ]);

        $sale->update($request->only(['payment_method', 'notes']));

        return response()->json([
            'message' => 'Vente mise à jour',
            'sale' => $sale
        ]);
    }

    /**
     * Supprimer une vente
     */
    public function destroy(Request $request, Sale $sale)
    {
        if ($sale->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        // Si c'est une vente de produit, restaurer le stock
        if ($sale->type === 'product' && $sale->product) {
            $sale->product->increment('stock', $sale->quantity);
        }

        $sale->delete();

        return response()->json(['message' => 'Vente supprimée']);
    }

    /**
     * Statistiques des ventes
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        // Revenus aujourd'hui
        $todayRevenue = $user->sales()->today()->sum('total');

        // Revenus ce mois
        $monthRevenue = $user->sales()->thisMonth()->sum('total');

        // Ventes par mode de paiement (ce mois)
        $paymentMethods = $user->sales()->thisMonth()
            ->selectRaw('payment_method, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();

        // Top produits vendus (ce mois)
        $topProducts = $user->sales()
            ->thisMonth()
            ->byType('product')
            ->with('product')
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        // Top services (ce mois)
        $topServices = $user->sales()
            ->thisMonth()
            ->byType('service')
            ->with('service')
            ->selectRaw('service_id, COUNT(*) as total_sales, SUM(total) as total_revenue')
            ->groupBy('service_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        return response()->json([
            'today_revenue' => $todayRevenue,
            'month_revenue' => $monthRevenue,
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
            'top_services' => $topServices,
        ]);
    }
}