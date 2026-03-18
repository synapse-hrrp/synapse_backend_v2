<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Étendre temporairement l'ENUM pour accepter ancienne + nouvelle valeur
        DB::statement("
            ALTER TABLE commandes
            MODIFY COLUMN statut ENUM(
                'EN_ATTENTE',
                'BROUILLON',
                'EN_ATTENTE_VALIDATION',
                'VALIDEE',
                'ENVOYEE',
                'LIVREE_PARTIELLE',
                'LIVREE',
                'ANNULEE'
            ) DEFAULT 'EN_ATTENTE'
        ");

        // 2) Convertir les anciennes données
        DB::statement("
            UPDATE commandes
            SET statut = 'EN_ATTENTE_VALIDATION'
            WHERE statut = 'EN_ATTENTE'
        ");

        // 3) Ajouter les nouvelles colonnes si elles n'existent pas
        Schema::table('commandes', function (Blueprint $table) {
            if (!Schema::hasColumn('commandes', 'type')) {
                $table->enum('type', ['MANUELLE', 'AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE'])
                    ->default('MANUELLE')
                    ->after('numero')
                    ->comment('Type commande: manuelle ou auto déclenchée');
            }

            if (!Schema::hasColumn('commandes', 'declencheur')) {
                $table->string('declencheur', 255)
                    ->nullable()
                    ->after('type')
                    ->comment('Détail déclenchement auto (ex: Stock < seuil_min)');
            }

            if (!Schema::hasColumn('commandes', 'priorite')) {
                $table->enum('priorite', ['NORMALE', 'URGENTE', 'CRITIQUE'])
                    ->default('NORMALE')
                    ->after('declencheur')
                    ->comment('Priorité traitement commande');
            }

            if (!Schema::hasColumn('commandes', 'stock_actuel_declenchement')) {
                $table->integer('stock_actuel_declenchement')
                    ->nullable()
                    ->after('priorite')
                    ->comment('Stock au moment déclenchement auto');
            }

            if (!Schema::hasColumn('commandes', 'cmh_au_declenchement')) {
                $table->decimal('cmh_au_declenchement', 10, 2)
                    ->nullable()
                    ->after('stock_actuel_declenchement')
                    ->comment('CMH au moment déclenchement auto');
            }

            if (!Schema::hasColumn('commandes', 'validee_par_user_id')) {
                $table->foreignId('validee_par_user_id')
                    ->nullable()
                    ->after('statut')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Utilisateur ayant validé la commande');
            }

            if (!Schema::hasColumn('commandes', 'validee_at')) {
                $table->timestamp('validee_at')
                    ->nullable()
                    ->after('validee_par_user_id')
                    ->comment('Date validation commande');
            }

            if (!Schema::hasColumn('commandes', 'envoyee_at')) {
                $table->timestamp('envoyee_at')
                    ->nullable()
                    ->after('validee_at')
                    ->comment('Date envoi au fournisseur');
            }

            if (!Schema::hasColumn('commandes', 'date_livraison_prevue')) {
                $table->date('date_livraison_prevue')
                    ->nullable()
                    ->after('date_commande')
                    ->comment('Date livraison prévue');
            }

            if (!Schema::hasColumn('commandes', 'montant_total')) {
                $table->decimal('montant_total', 15, 2)
                    ->default(0)
                    ->after('observations')
                    ->comment('Somme (quantite_commandee × prix_unitaire) des lignes');
            }

            if (!Schema::hasColumn('commandes', 'fichier_export_path')) {
                $table->string('fichier_export_path', 255)
                    ->nullable()
                    ->after('montant_total')
                    ->comment('Chemin fichier exporté (Excel/CSV/PDF)');
            }

            if (!Schema::hasColumn('commandes', 'format_export')) {
                $table->enum('format_export', ['EXCEL', 'CSV', 'PDF'])
                    ->nullable()
                    ->after('fichier_export_path')
                    ->comment('Format dernier export');
            }

            if (!Schema::hasColumn('commandes', 'exporte_at')) {
                $table->timestamp('exporte_at')
                    ->nullable()
                    ->after('format_export')
                    ->comment('Date dernier export');
            }

            if (!Schema::hasColumn('commandes', 'exporte_par_user_id')) {
                $table->foreignId('exporte_par_user_id')
                    ->nullable()
                    ->after('exporte_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Utilisateur ayant exporté');
            }
        });

        // 4) Mettre l'ENUM final
        DB::statement("
            ALTER TABLE commandes
            MODIFY COLUMN statut ENUM(
                'BROUILLON',
                'EN_ATTENTE_VALIDATION',
                'VALIDEE',
                'ENVOYEE',
                'LIVREE_PARTIELLE',
                'LIVREE',
                'ANNULEE'
            ) DEFAULT 'BROUILLON'
        ");

        // 5) Index seulement s'ils n'existent pas
        $indexes = collect(DB::select('SHOW INDEX FROM commandes'))->pluck('Key_name')->toArray();

        Schema::table('commandes', function (Blueprint $table) use ($indexes) {
            if (!in_array('commandes_type_statut_index', $indexes)) {
                $table->index(['type', 'statut']);
            }

            if (!in_array('commandes_priorite_index', $indexes)) {
                $table->index('priorite');
            }

            if (!in_array('commandes_validee_at_index', $indexes)) {
                $table->index('validee_at');
            }
        });

        DB::statement("UPDATE commandes SET type = 'MANUELLE' WHERE type IS NULL");
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM commandes'))->pluck('Key_name')->toArray();

        Schema::table('commandes', function (Blueprint $table) use ($indexes) {
            if (in_array('commandes_type_statut_index', $indexes)) {
                $table->dropIndex('commandes_type_statut_index');
            }

            if (in_array('commandes_priorite_index', $indexes)) {
                $table->dropIndex('commandes_priorite_index');
            }

            if (in_array('commandes_validee_at_index', $indexes)) {
                $table->dropIndex('commandes_validee_at_index');
            }

            if (Schema::hasColumn('commandes', 'validee_par_user_id')) {
                $table->dropForeign(['validee_par_user_id']);
            }

            if (Schema::hasColumn('commandes', 'exporte_par_user_id')) {
                $table->dropForeign(['exporte_par_user_id']);
            }
        });

        DB::statement("
            ALTER TABLE commandes
            MODIFY COLUMN statut ENUM('EN_ATTENTE', 'VALIDEE', 'ANNULEE')
            DEFAULT 'EN_ATTENTE'
        ");

        $columns = [
            'type',
            'declencheur',
            'priorite',
            'stock_actuel_declenchement',
            'cmh_au_declenchement',
            'validee_par_user_id',
            'validee_at',
            'envoyee_at',
            'date_livraison_prevue',
            'montant_total',
            'fichier_export_path',
            'format_export',
            'exporte_at',
            'exporte_par_user_id',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('commandes', $column)) {
                Schema::table('commandes', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};