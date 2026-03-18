<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('restrict');
            $table->foreignId('depot_id')->constrained('depots')->onDelete('restrict');
            $table->integer('quantite');
            $table->string('numero_lot', 100);
            $table->date('date_peremption');
            $table->decimal('prix_achat', 10, 2);
            $table->timestamps();
            
            $table->index(['reception_id', 'produit_id']);
            $table->index('numero_lot');
            $table->index('date_peremption');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_receptions');
    }
};