<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ne créer la vue que si les tables existent
        if (!Schema::hasTable('t_billing_requests') || !Schema::hasTable('ventes')) {
            return;
        }

        DB::statement("DROP VIEW IF EXISTS v_finance_factures");

        DB::statement("
            CREATE VIEW v_finance_factures AS

            SELECT
                s.id AS source_id,
                'reception' AS module_source,
                't_billing_requests' AS table_source,
                COALESCE(s.ref_source, CONCAT('BR-', s.id)) AS numero,
                s.statut AS statut_source,
                CAST(COALESCE(s.montant_total, 0) AS DECIMAL(12,2)) AS total_du,

                CAST((
                    SELECT COALESCE(SUM(p.montant),0)
                    FROM t_finance_paiements p
                    WHERE p.statut = 'valide'
                      AND p.module_source = 'reception'
                      AND p.table_source = 't_billing_requests'
                      AND p.source_id = s.id
                ) AS DECIMAL(12,2)) AS total_paye,

                CAST(GREATEST(ROUND(
                    COALESCE(s.montant_total,0) - (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'reception'
                          AND p.table_source = 't_billing_requests'
                          AND p.source_id = s.id
                    ), 2
                ), 0) AS DECIMAL(12,2)) AS reste,

                CASE
                    WHEN (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'reception'
                          AND p.table_source = 't_billing_requests'
                          AND p.source_id = s.id
                    ) <= 0 THEN 'NON_PAYE'
                    WHEN (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'reception'
                          AND p.table_source = 't_billing_requests'
                          AND p.source_id = s.id
                    ) < COALESCE(s.montant_total,0) THEN 'PARTIEL'
                    ELSE 'PAYE'
                END AS statut_finance,

                COALESCE(s.created_at, s.updated_at) AS date_facture

            FROM t_billing_requests s

            UNION ALL

            SELECT
                s.id AS source_id,
                'pharmacie' AS module_source,
                'ventes' AS table_source,
                COALESCE(s.numero, CONCAT('V-', s.id)) AS numero,
                s.statut AS statut_source,
                CAST(COALESCE(s.montant_ttc, 0) AS DECIMAL(12,2)) AS total_du,

                CAST((
                    SELECT COALESCE(SUM(p.montant),0)
                    FROM t_finance_paiements p
                    WHERE p.statut = 'valide'
                      AND p.module_source = 'pharmacie'
                      AND p.table_source = 'ventes'
                      AND p.source_id = s.id
                ) AS DECIMAL(12,2)) AS total_paye,

                CAST(GREATEST(ROUND(
                    COALESCE(s.montant_ttc,0) - (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'pharmacie'
                          AND p.table_source = 'ventes'
                          AND p.source_id = s.id
                    ), 2
                ), 0) AS DECIMAL(12,2)) AS reste,

                CASE
                    WHEN (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'pharmacie'
                          AND p.table_source = 'ventes'
                          AND p.source_id = s.id
                    ) <= 0 THEN 'NON_PAYE'
                    WHEN (
                        SELECT COALESCE(SUM(p.montant),0)
                        FROM t_finance_paiements p
                        WHERE p.statut = 'valide'
                          AND p.module_source = 'pharmacie'
                          AND p.table_source = 'ventes'
                          AND p.source_id = s.id
                    ) < COALESCE(s.montant_ttc,0) THEN 'PARTIEL'
                    ELSE 'PAYE'
                END AS statut_finance,

                COALESCE(s.date_vente, s.created_at) AS date_facture

            FROM ventes s
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_finance_factures");
    }
};