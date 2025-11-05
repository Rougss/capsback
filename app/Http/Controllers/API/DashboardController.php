<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardController extends Controller
{
   public function stats(Request $request)
{
    $user = $request->user();

    // Total des clients
    $totalClients = $user->clients()->count();

    // Rendez-vous du mois
    $appointmentsThisMonth = $user->appointments()
        ->whereMonth('appointment_date', now()->month)
        ->count();

    // Revenus du mois - TOUTES LES VENTES
    $revenueThisMonth = $user->sales()
        ->whereMonth('sale_date', now()->month)
        ->whereYear('sale_date', now()->year)
        ->sum('total');

    // Produits vendus ce mois
    $productsThisMonth = $user->sales()
        ->byType('product')
        ->whereMonth('sale_date', now()->month)
        ->whereYear('sale_date', now()->year)
        ->sum('quantity');

    // Activité récente (dernières ventes ou RDV)
    $recentAppointments = $user->appointments()
        ->with(['client', 'service'])
        ->latest('appointment_date')
        ->limit(5)
        ->get();

    // Prochains rendez-vous
    $upcomingAppointments = $user->appointments()
        ->with(['client', 'service'])
        ->where('appointment_date', '>=', now())
        ->where('status', '!=', 'cancelled')
        ->orderBy('appointment_date')
        ->limit(5)
        ->get();

    // Top produits vendus
    $topProducts = $user->sales()
        ->byType('product')
        ->whereMonth('sale_date', now()->month)
        ->whereYear('sale_date', now()->year)
        ->with('product')
        ->select('product_id', DB::raw('SUM(quantity) as sold'), DB::raw('SUM(total) as revenue'))
        ->groupBy('product_id')
        ->orderByDesc('sold')
        ->limit(5)
        ->get()
        ->map(function($sale) {
            return [
                'name' => $sale->product->name ?? 'Produit inconnu',
                'sold' => $sale->sold,
                'revenue' => $sale->revenue,
            ];
        });

    return response()->json([
        'totalClients' => $totalClients,
        'appointmentsThisMonth' => $appointmentsThisMonth,
        'revenue' => $revenueThisMonth, // ← Changé de revenueThisMonth à revenue
        'productsSold' => $productsThisMonth,
        'recentAppointments' => $recentAppointments,
        'upcomingAppointments' => $upcomingAppointments,
        'topProducts' => $topProducts,
    ]);
}
}