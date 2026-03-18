<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->onDelete('restrict');
            $table->foreignId('depot_id')->constrained('depots')->onDelete('restrict');
            $table->string('numero_lot', 100);
            $table->date('date_peremption');
            $table->integer('quantite')->default(0);
            $table->decimal('prix_achat', 10, 2);
            $table->timestamps();
            
            // UNIQUE pour éviter doublons (depot + produit + lot + péremption)
            $table->unique(['depot_id', 'produit_id', 'numero_lot', 'date_peremption'], 'unique_stock');
            
            // Index FEFO : tri par péremption
            $table->index(['produit_id', 'depot_id', 'date_peremption']);
            $table->index('date_peremption');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};