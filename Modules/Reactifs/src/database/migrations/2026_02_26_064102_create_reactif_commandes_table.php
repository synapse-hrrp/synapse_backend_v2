<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('fournisseur_id')
                  ->constrained('reactif_fournisseurs')
                  ->onDelete('restrict');
            $table->enum('statut', [
                'brouillon',
                'envoyee',
                'partiellement_recue',
                'recue',
                'annulee',
            ])->default('brouillon');
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();
            $table->date('date_livraison_reelle')->nullable();
            $table->decimal('montant_total', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'date_commande']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_commandes');
    }
};