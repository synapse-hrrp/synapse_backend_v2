<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_consommations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reactif_id')->constrained('reactifs')->onDelete('cascade');
            $table->unsignedBigInteger('examen_id');   // lien vers module Laboratoire
            $table->unsignedBigInteger('examen_type_id');
            $table->decimal('quantite_consommee', 10, 3);
            $table->decimal('stock_avant', 10, 3);
            $table->decimal('stock_apres', 10, 3);
            $table->foreignId('mouvement_id')
                  ->nullable()
                  ->constrained('reactif_stock_mouvements')
                  ->onDelete('set null');
            $table->timestamp('consomme_le')->useCurrent();
            $table->timestamps();

            $table->index(['reactif_id', 'consomme_le']);
            $table->index('examen_id');
            $table->index('examen_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_consommations');
    }
};