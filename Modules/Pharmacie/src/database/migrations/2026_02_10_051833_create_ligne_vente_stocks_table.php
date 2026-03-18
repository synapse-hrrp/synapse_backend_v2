<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_vente_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_vente_id')->constrained('ligne_ventes')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('restrict');
            $table->integer('quantite');
            $table->timestamps();
            
            $table->index(['ligne_vente_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_vente_stocks');
    }
};