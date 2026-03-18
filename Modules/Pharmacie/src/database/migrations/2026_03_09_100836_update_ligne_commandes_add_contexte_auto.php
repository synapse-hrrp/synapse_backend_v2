<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_commandes', function (Blueprint $table) {
            // Contexte commande auto (pour traçabilité)
            $table->integer('stock_actuel')->nullable()->after('quantite_recue')
                ->comment('Stock au moment de la commande');
            $table->decimal('cmh', 10, 2)->nullable()->after('stock_actuel')
                ->comment('CMH au moment de la commande');
            $table->integer('seuil_max')->nullable()->after('cmh')
                ->comment('Seuil max utilisé pour le calcul');
            $table->integer('seuil_min')->nullable()->after('seuil_max')
                ->comment('Seuil min utilisé pour le calcul');
            $table->string('raison_commande', 255)->nullable()->after('seuil_min')
                ->comment('Explication calcul quantité (ex: seuil_max - stock_actuel)');
            
            // Index
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::table('ligne_commandes', function (Blueprint $table) {
            $table->dropColumn([
                'stock_actuel',
                'cmh',
                'seuil_max',
                'seuil_min',
                'raison_commande'
            ]);
        });
    }
};
