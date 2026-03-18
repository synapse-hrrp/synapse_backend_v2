<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('receptions', 'validee_par_user_id')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->foreignId('validee_par_user_id')
                    ->nullable()
                    ->after('statut')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Utilisateur ayant validé la réception');
            });
        }

        if (!Schema::hasColumn('receptions', 'validee_at')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->timestamp('validee_at')
                    ->nullable()
                    ->after('validee_par_user_id')
                    ->comment('Date validation réception');
            });
        }

        if (!Schema::hasColumn('receptions', 'date_livraison_prevue')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->date('date_livraison_prevue')
                    ->nullable()
                    ->after('date_reception')
                    ->comment('Date livraison prévue par le fournisseur');
            });
        }

        if (!Schema::hasColumn('receptions', 'date_livraison_reelle')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->date('date_livraison_reelle')
                    ->nullable()
                    ->after('date_livraison_prevue')
                    ->comment('Date livraison effective');
            });
        }

        if (!Schema::hasColumn('receptions', 'bon_livraison')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->string('bon_livraison', 100)
                    ->nullable()
                    ->after('fournisseur_id')
                    ->comment('Numéro bon de livraison fournisseur');
            });
        }

        if (!Schema::hasColumn('receptions', 'facture_fournisseur')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->string('facture_fournisseur', 100)
                    ->nullable()
                    ->after('bon_livraison')
                    ->comment('Numéro facture fournisseur');
            });
        }

        if (!Schema::hasColumn('receptions', 'montant_total_ht')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->decimal('montant_total_ht', 15, 2)
                    ->default(0)
                    ->after('observations')
                    ->comment('Somme montant_achat_ht des lignes');
            });
        }

        if (!Schema::hasColumn('receptions', 'montant_total_ttc')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->decimal('montant_total_ttc', 15, 2)
                    ->default(0)
                    ->after('montant_total_ht')
                    ->comment('Somme montant_achat_ttc des lignes');
            });
        }

        if (!Schema::hasColumn('receptions', 'fichier_export_path')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->string('fichier_export_path', 255)
                    ->nullable()
                    ->after('montant_total_ttc')
                    ->comment('Chemin fichier exporté (Excel/CSV/PDF)');
            });
        }

        if (!Schema::hasColumn('receptions', 'format_export')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->enum('format_export', ['EXCEL', 'CSV', 'PDF'])
                    ->nullable()
                    ->after('fichier_export_path')
                    ->comment('Format dernier export');
            });
        }

        if (!Schema::hasColumn('receptions', 'exporte_at')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->timestamp('exporte_at')
                    ->nullable()
                    ->after('format_export')
                    ->comment('Date dernier export');
            });
        }

        if (!Schema::hasColumn('receptions', 'exporte_par_user_id')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->foreignId('exporte_par_user_id')
                    ->nullable()
                    ->after('exporte_at')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('Utilisateur ayant exporté');
            });
        }

        $valideeAtIndexExists = DB::select("
            SHOW INDEX FROM receptions
            WHERE Key_name = 'receptions_validee_at_index'
        ");

        if (empty($valideeAtIndexExists) && Schema::hasColumn('receptions', 'validee_at')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->index('validee_at');
            });
        }

        $statutIndexExists = DB::select("
            SHOW INDEX FROM receptions
            WHERE Key_name = 'receptions_statut_index'
        ");

        if (empty($statutIndexExists) && Schema::hasColumn('receptions', 'statut')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->index('statut');
            });
        }
    }

    public function down(): void
    {
        $valideeAtIndexExists = DB::select("
            SHOW INDEX FROM receptions
            WHERE Key_name = 'receptions_validee_at_index'
        ");

        if (!empty($valideeAtIndexExists)) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->dropIndex('receptions_validee_at_index');
            });
        }

        $fkValideeExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'receptions'
              AND CONSTRAINT_NAME = 'receptions_validee_par_user_id_foreign'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if (!empty($fkValideeExists)) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->dropForeign('receptions_validee_par_user_id_foreign');
            });
        }

        $fkExporteExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'receptions'
              AND CONSTRAINT_NAME = 'receptions_exporte_par_user_id_foreign'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        if (!empty($fkExporteExists)) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->dropForeign('receptions_exporte_par_user_id_foreign');
            });
        }

        $columnsToDrop = [
            'validee_par_user_id',
            'validee_at',
            'date_livraison_prevue',
            'date_livraison_reelle',
            'bon_livraison',
            'facture_fournisseur',
            'montant_total_ht',
            'montant_total_ttc',
            'fichier_export_path',
            'format_export',
            'exporte_at',
            'exporte_par_user_id',
        ];

        foreach ($columnsToDrop as $column) {
            if (Schema::hasColumn('receptions', $column)) {
                Schema::table('receptions', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};