<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consommations_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('semaine');
            $table->unsignedTinyInteger('mois');
            
            $table->integer('quantite_vendue')->default(0);
            $table->integer('quantite_gratuite')->default(0);
            $table->integer('quantite_totale')->default(0);
            
            $table->decimal('cmh_4_semaines', 10, 2)->nullable();
            $table->decimal('cmm', 10, 2)->nullable();
            
            $table->integer('nb_ventes')->default(0);
            
            $table->timestamps();
            
            $table->unique(['produit_id', 'depot_id', 'annee', 'semaine'], 'unique_conso_periode');
            $table->index(['produit_id', 'depot_id']);
            $table->index(['annee', 'semaine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consommations_produits');
    }
};