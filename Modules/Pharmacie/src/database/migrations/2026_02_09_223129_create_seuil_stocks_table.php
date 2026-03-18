<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seuil_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->foreignId('depot_id')->constrained('depots')->onDelete('cascade');
            $table->integer('seuil_min')->default(2);
            $table->integer('seuil_max')->default(6);
            $table->timestamps();
            
            $table->unique(['produit_id', 'depot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seuil_stocks');
    }
};