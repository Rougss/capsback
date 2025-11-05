<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\SaleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées (authentification requise)
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Clients
    Route::apiResource('clients', ClientController::class);

    // Services
    Route::apiResource('services', ServiceController::class);

    // Rendez-vous
    Route::apiResource('appointments', AppointmentController::class);

    // Produits
    Route::apiResource('products', ProductController::class);

    // Messages
     Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/messages/{id}/send', [MessageController::class, 'send']);
    Route::put('/messages/{id}', [MessageController::class, 'update']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);
    // Commandes
    Route::apiResource('orders', OrderController::class);

    // Dans le groupe middleware('auth:sanctum')
Route::apiResource('sales', SaleController::class);
Route::get('/sales/stats/summary', [SaleController::class, 'stats']);
});