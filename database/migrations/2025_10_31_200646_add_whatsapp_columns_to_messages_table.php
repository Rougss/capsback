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
        Schema::table('messages', function (Blueprint $table) {
            // Modifier l'enum status pour ajouter 'failed'
            $table->enum('status', ['draft', 'scheduled', 'sent', 'failed'])
                ->default('draft')
                ->change();
            
            // Ajouter les nouvelles colonnes
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            $table->string('whatsapp_message_id')->nullable()->after('sent_at');
            $table->text('error_message')->nullable()->after('whatsapp_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'whatsapp_message_id', 'error_message']);
            
            // Remettre l'ancien enum
            $table->enum('status', ['draft', 'scheduled', 'sent'])
                ->default('draft')
                ->change();
        });
    }
};