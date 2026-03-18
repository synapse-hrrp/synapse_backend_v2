<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Prix de vente (actif pour ce stock)
            $table->decimal('prix_vente_unitaire_ht', 10, 2)->nullable()
                ->after('montant_tva_unitaire')
                ->comment('Prix de vente unitaire HT actuel');
            
            $table->decimal('prix_vente_unitaire_ttc', 10, 2)->nullable()
                ->after('prix_vente_unitaire_ht')
                ->comment('Prix de vente unitaire TTC actuel');
            
            $table->decimal('marge_unitaire_ht', 10, 2)->nullable()
                ->after('prix_vente_unitaire_ttc')
                ->comment('Marge unitaire HT');
            
            $table->decimal('marge_unitaire_ttc', 10, 2)->nullable()
                ->after('marge_unitaire_ht')
                ->comment('Marge unitaire TTC');
            
            $table->decimal('taux_marge', 5, 2)->nullable()
                ->after('marge_unitaire_ttc')
                ->comment('Taux de marge en %');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn([
                'prix_vente_unitaire_ht',
                'prix_vente_unitaire_ttc',
                'marge_unitaire_ht',
                'marge_unitaire_ttc',
                'taux_marge'
            ]);
        });
    }
};