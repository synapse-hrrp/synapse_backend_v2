<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            // Ajouter les totaux de vente et marge prévus
            $table->decimal('montant_total_vente', 15, 2)
                  ->nullable()
                  ->after('montant_total_ttc')
                  ->comment('Montant total de vente prévu (somme des lignes)');
            
            $table->decimal('marge_totale_prevue', 15, 2)
                  ->nullable()
                  ->after('montant_total_vente')
                  ->comment('Marge totale prévue (somme des marges des lignes)');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn(['montant_total_vente', 'marge_totale_prevue']);
        });
    }
};