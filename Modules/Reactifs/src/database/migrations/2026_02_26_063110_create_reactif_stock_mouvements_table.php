<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reactif_id')->constrained('reactifs')->onDelete('cascade');
            $table->enum('type', [
                'entree',       // réception commande
                'sortie',       // consommation manuelle
                'consommation', // consommation automatique examen
                'ajustement',   // correction inventaire
                'perte',        // péremption, casse
            ]);
            $table->decimal('quantite', 10, 3);
            $table->decimal('stock_avant', 10, 3);
            $table->decimal('stock_apres', 10, 3);
            $table->string('reference')->nullable(); // n° commande, n° examen...
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('motif')->nullable();
            $table->timestamp('date_mouvement')->useCurrent();
            $table->timestamps();

            $table->index(['reactif_id', 'date_mouvement']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_stock_mouvements');
    }
};