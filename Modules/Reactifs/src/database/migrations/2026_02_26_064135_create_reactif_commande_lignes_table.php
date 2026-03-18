<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')
                  ->constrained('reactif_commandes')
                  ->onDelete('cascade');
            $table->foreignId('reactif_id')
                  ->constrained('reactifs')
                  ->onDelete('restrict');
            $table->decimal('quantite_commandee', 10, 3);
            $table->decimal('quantite_recue', 10, 3)->default(0);
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->decimal('montant_ligne', 12, 2)->default(0);
            $table->date('date_peremption')->nullable(); // péremption du lot reçu
            $table->string('numero_lot')->nullable();
            $table->enum('statut', [
                'en_attente',
                'partiellement_recue',
                'recue',
                'annulee',
            ])->default('en_attente');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['commande_id', 'reactif_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_commande_lignes');
    }
};