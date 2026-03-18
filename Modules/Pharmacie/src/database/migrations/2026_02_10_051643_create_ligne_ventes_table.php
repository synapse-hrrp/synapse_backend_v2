<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('restrict');
            $table->integer('quantite');
            $table->decimal('prix_unitaire_ttc', 10, 2); // PA × 1.40 avec taxes
            $table->decimal('montant_ligne_ttc', 10, 2);
            $table->timestamps();
            
            $table->index(['vente_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_ventes');
    }
};