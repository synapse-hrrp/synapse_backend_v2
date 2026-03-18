<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            // Vérifier si les colonnes existent déjà avant d'ajouter
            if (!Schema::hasColumn('ligne_receptions', 'date_fabrication')) {
                $table->date('date_fabrication')->nullable()->after('date_peremption');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'pays_origine')) {
                $table->string('pays_origine', 100)->nullable()->after('date_fabrication');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'prix_achat_unitaire_ht')) {
                $table->decimal('prix_achat_unitaire_ht', 10, 2)
                    ->nullable()
                    ->after('pays_origine')
                    ->comment('Prix achat HT unitaire');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'tva_applicable')) {
                $table->boolean('tva_applicable')->default(true)->after('prix_achat_unitaire_ht');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'tva_pourcentage')) {
                $table->decimal('tva_pourcentage', 5, 2)
                    ->nullable()
                    ->after('tva_applicable')
                    ->comment('Taux TVA en %');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'prix_achat_unitaire_ttc')) {
                $table->decimal('prix_achat_unitaire_ttc', 10, 2)
                    ->nullable()
                    ->after('tva_pourcentage')
                    ->comment('Prix achat TTC unitaire');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'coefficient_marge')) {
                $table->decimal('coefficient_marge', 5, 2)
                    ->nullable()
                    ->after('prix_achat_unitaire_ttc')
                    ->comment('Coefficient multiplicateur');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'prix_vente_unitaire')) {
                $table->decimal('prix_vente_unitaire', 10, 2)
                    ->nullable()
                    ->after('coefficient_marge')
                    ->comment('Prix vente unitaire (ancien champ)');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'montant_achat_ht')) {
                $table->decimal('montant_achat_ht', 10, 2)
                    ->nullable()
                    ->after('prix_vente_unitaire')
                    ->comment('Montant total achat HT');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'montant_achat_ttc')) {
                $table->decimal('montant_achat_ttc', 10, 2)
                    ->nullable()
                    ->after('montant_achat_ht')
                    ->comment('Montant total achat TTC');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'montant_vente_total')) {
                $table->decimal('montant_vente_total', 10, 2)
                    ->nullable()
                    ->after('montant_achat_ttc')
                    ->comment('Montant total vente');
            }
            
            // ✅ NOUVEAUX CHAMPS : Prix de vente détaillés et marges
            if (!Schema::hasColumn('ligne_receptions', 'prix_vente_unitaire_ht')) {
                $table->decimal('prix_vente_unitaire_ht', 10, 2)
                    ->nullable()
                    ->after('montant_vente_total')
                    ->comment('Prix vente unitaire HT suggéré');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'prix_vente_unitaire_ttc')) {
                $table->decimal('prix_vente_unitaire_ttc', 10, 2)
                    ->nullable()
                    ->after('prix_vente_unitaire_ht')
                    ->comment('Prix vente unitaire TTC suggéré');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'marge_prevue_ht')) {
                $table->decimal('marge_prevue_ht', 10, 2)
                    ->nullable()
                    ->after('prix_vente_unitaire_ttc')
                    ->comment('Marge prévue HT');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'marge_prevue_ttc')) {
                $table->decimal('marge_prevue_ttc', 10, 2)
                    ->nullable()
                    ->after('marge_prevue_ht')
                    ->comment('Marge prévue TTC');
            }
            
            if (!Schema::hasColumn('ligne_receptions', 'taux_marge_prevu')) {
                $table->decimal('taux_marge_prevu', 5, 2)
                    ->nullable()
                    ->after('marge_prevue_ttc')
                    ->comment('Taux de marge prévu en %');
            }
        });
        
        // ✅ SUPPRIMÉ : Pas de migration de données car 'prix_achat' n'existe pas
    }

    public function down(): void
    {
        Schema::table('ligne_receptions', function (Blueprint $table) {
            $columns = [
                'date_fabrication',
                'pays_origine',
                'prix_achat_unitaire_ht',
                'tva_applicable',
                'tva_pourcentage',
                'prix_achat_unitaire_ttc',
                'coefficient_marge',
                'prix_vente_unitaire',
                'montant_achat_ht',
                'montant_achat_ttc',
                'montant_vente_total',
                'prix_vente_unitaire_ht',
                'prix_vente_unitaire_ttc',
                'marge_prevue_ht',
                'marge_prevue_ttc',
                'taux_marge_prevu',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('ligne_receptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};