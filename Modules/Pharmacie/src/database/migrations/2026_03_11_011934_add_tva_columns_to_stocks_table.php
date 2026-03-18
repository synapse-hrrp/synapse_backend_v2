<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajouter les colonnes TVA à la table stocks
     */
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Colonnes TVA après prix_achat
            $table->decimal('prix_achat_unitaire_ht', 10, 2)->nullable()->after('prix_achat')
                ->comment('Prix achat unitaire HT');
            
            $table->decimal('prix_achat_unitaire_ttc', 10, 2)->nullable()->after('prix_achat_unitaire_ht')
                ->comment('Prix achat unitaire TTC');
            
            $table->decimal('taux_tva', 5, 2)->default(18.9)->after('prix_achat_unitaire_ttc')
                ->comment('Taux de TVA en pourcentage (18.9% au Congo)');
            
            $table->decimal('montant_tva_unitaire', 10, 2)->nullable()->after('taux_tva')
                ->comment('Montant TVA unitaire calculé');
        });

        // Migrer les données existantes
        // Pour chaque stock ayant un prix_achat, calculer HT/TTC/TVA
        DB::statement("
            UPDATE stocks 
            SET 
                prix_achat_unitaire_ht = prix_achat,
                taux_tva = 18.9,
                montant_tva_unitaire = ROUND(prix_achat * 0.189, 2),
                prix_achat_unitaire_ttc = ROUND(prix_achat * 1.189, 2)
            WHERE prix_achat IS NOT NULL 
            AND prix_achat > 0
            AND prix_achat_unitaire_ht IS NULL
        ");
    }

    /**
     * Retour arrière : supprimer les colonnes TVA
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'prix_achat_unitaire_ht',
                'prix_achat_unitaire_ttc',
                'taux_tva',
                'montant_tva_unitaire'
            ]);
        });
    }
};