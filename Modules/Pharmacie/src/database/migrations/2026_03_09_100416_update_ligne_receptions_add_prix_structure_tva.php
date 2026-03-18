<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            // Renommer prix_achat en prix_achat_unitaire_ht
            $table->renameColumn('prix_achat', 'prix_achat_unitaire_ht');
        });

        Schema::table('ligne_receptions', function (Blueprint $table) {
            // ========================================
            // TVA CONGO 18%
            // ========================================
            $table->boolean('tva_applicable')->default(true)->after('prix_achat_unitaire_ht')
                ->comment('Si true, appliquer TVA 18%');
            
            $table->decimal('tva_pourcentage', 5, 2)->default(18)->after('tva_applicable')
                ->comment('% TVA appliqué (18% au Congo)');
            
            $table->decimal('prix_achat_unitaire_ttc', 10, 2)->nullable()->after('tva_pourcentage')
                ->comment('Calculé: si tva_applicable alors PUHT × 1.18 sinon PUHT');
            
            // ========================================
            // MARGE ET PRIX DE VENTE
            // ========================================
            $table->decimal('coefficient_marge', 5, 2)->default(1.40)->after('prix_achat_unitaire_ttc')
                ->comment('Multiplicateur pour prix de vente (1.40 = +40%)');
            
            $table->decimal('prix_vente_unitaire', 10, 2)->nullable()->after('coefficient_marge')
                ->comment('Calculé: PUTTC × coefficient_marge');
            
            // ========================================
            // MONTANTS TOTAUX
            // ========================================
            $table->decimal('montant_achat_ht', 10, 2)->nullable()->after('prix_vente_unitaire')
                ->comment('quantite × prix_achat_unitaire_ht');
            
            $table->decimal('montant_achat_ttc', 10, 2)->nullable()->after('montant_achat_ht')
                ->comment('quantite × prix_achat_unitaire_ttc');
            
            $table->decimal('montant_vente_total', 10, 2)->nullable()->after('montant_achat_ttc')
                ->comment('quantite × prix_vente_unitaire');
            
            // ========================================
            // TRAÇABILITÉ PRODUIT
            // ========================================
            $table->date('date_fabrication')->nullable()->after('date_peremption')
                ->comment('Date de fabrication du lot');
            
            $table->string('pays_origine', 100)->nullable()->after('date_fabrication')
                ->comment('Pays d\'origine du lot');
            
            // ========================================
            // CONDITIONNEMENT (utile pour gestion)
            // ========================================
            $table->enum('unite_vente', ['UNITE', 'BOITE', 'STRIP', 'FLACON'])->default('UNITE')->after('pays_origine')
                ->comment('Unité de vente pour cette réception');
            
            $table->integer('unites_par_boite')->nullable()->after('unite_vente')
                ->comment('Nombre d\'unités dans cette boîte/strip');
        });
    }

    public function down(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            $table->dropColumn([
                'tva_applicable',
                'tva_pourcentage',
                'prix_achat_unitaire_ttc',
                'coefficient_marge',
                'prix_vente_unitaire',
                'montant_achat_ht',
                'montant_achat_ttc',
                'montant_vente_total',
                'date_fabrication',
                'pays_origine',
                'unite_vente',
                'unites_par_boite'
            ]);
        });

        Schema::table('ligne_receptions', function (Blueprint $table) {
            $table->renameColumn('prix_achat_unitaire_ht', 'prix_achat');
        });
    }
};