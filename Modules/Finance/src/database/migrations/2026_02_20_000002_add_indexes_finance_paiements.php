<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_finance_paiements', function (Blueprint $table) {
            // Accélère paidAggQuery() + les filtres facture
            $table->index(['statut', 'module_source', 'table_source', 'source_id'], 'idx_fin_paiements_agg');
            // Accélère le tri/filtre par session et list paiements
            $table->index(['session_id'], 'idx_fin_paiements_session');
            // Accélère les détails facture (liste paiements)
            $table->index(['module_source', 'table_source', 'source_id'], 'idx_fin_paiements_source');
        });

        Schema::table('t_finance_audits', function (Blueprint $table) {
            // Accélère le détail facture (audits)
            $table->index(['table_source', 'source_id'], 'idx_fin_audits_source');
        });
    }

    public function down(): void
    {
        Schema::table('t_finance_paiements', function (Blueprint $table) {
            $table->dropIndex('idx_fin_paiements_agg');
            $table->dropIndex('idx_fin_paiements_session');
            $table->dropIndex('idx_fin_paiements_source');
        });

        Schema::table('t_finance_audits', function (Blueprint $table) {
            $table->dropIndex('idx_fin_audits_source');
        });
    }
};