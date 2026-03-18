<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            // Prix de vente (suggéré à la réception)
            $table->decimal('prix_vente_unitaire_ht', 10, 2)->nullable()
                ->after('montant_achat_ttc')
                ->comment('Prix de vente unitaire HT suggéré');
            
            $table->decimal('prix_vente_unitaire_ttc', 10, 2)->nullable()
                ->after('prix_vente_unitaire_ht')
                ->comment('Prix de vente unitaire TTC suggéré');
            
            $table->decimal('marge_prevue_ht', 10, 2)->nullable()
                ->after('prix_vente_unitaire_ttc')
                ->comment('Marge prévue HT (prix_vente_ht - prix_achat_ht)');
            
            $table->decimal('marge_prevue_ttc', 10, 2)->nullable()
                ->after('marge_prevue_ht')
                ->comment('Marge prévue TTC (prix_vente_ttc - prix_achat_ttc)');
            
            $table->decimal('taux_marge_prevu', 5, 2)->nullable()
                ->after('marge_prevue_ttc')
                ->comment('Taux de marge prévu en %');
        });
    }

    public function down(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            $table->dropColumn([
                'prix_vente_unitaire_ht',
                'prix_vente_unitaire_ttc',
                'marge_prevue_ht',
                'marge_prevue_ttc',
                'taux_marge_prevu'
            ]);
        });
    }
};