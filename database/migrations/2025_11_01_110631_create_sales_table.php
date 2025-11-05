<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            
            // Type de vente : 'service' ou 'product'
            $table->enum('type', ['service', 'product']);
            
            // Relations optionnelles selon le type
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            
            // Détails financiers
            $table->decimal('amount', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2); // amount * quantity
            
            // Mode de paiement
            $table->enum('payment_method', ['cash', 'wave', 'orange_money', 'bank_transfer', 'other'])->default('cash');
            
            // Informations additionnelles
            $table->text('notes')->nullable();
            $table->date('sale_date');
            
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index(['user_id', 'sale_date']);
            $table->index(['type', 'sale_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};