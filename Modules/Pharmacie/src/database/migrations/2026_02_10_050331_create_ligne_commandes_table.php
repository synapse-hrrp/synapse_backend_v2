<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('restrict');
            $table->integer('quantite_commandee');
            $table->integer('quantite_recue')->default(0);
            $table->decimal('prix_unitaire', 10, 2);
            $table->timestamps();
            
            $table->index(['commande_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_commandes');
    }
};