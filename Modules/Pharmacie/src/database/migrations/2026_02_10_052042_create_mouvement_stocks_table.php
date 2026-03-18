<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvement_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('restrict');
            $table->enum('type', ['ENTREE', 'SORTIE_VENTE', 'SORTIE_GRATUITE', 'ANNULATION_VENTE']);
            $table->integer('quantite');
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->onDelete('set null');
            $table->foreignId('reception_id')->nullable()->constrained('receptions')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observations')->nullable();
            $table->timestamps();
            
            $table->index(['stock_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_stocks');
    }
};