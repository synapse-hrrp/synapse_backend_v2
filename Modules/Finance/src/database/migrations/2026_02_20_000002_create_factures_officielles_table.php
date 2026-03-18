<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factures_officielles', function (Blueprint $table) {
            $table->id();

            // Numéro global unique (document comptable)
            $table->string('numero_global', 30)->unique(); // ex: FAC-2026-000001

            // Source (module/table/id)
            $table->string('module_source', 30);  // pharmacie, reception, ...
            $table->string('table_source', 80);   // ventes, t_billing_requests, ...
            $table->unsignedBigInteger('source_id');

            // Montants (snapshot figé)
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2);

            // Snapshot client (optionnel)
            $table->string('client_nom')->nullable();
            $table->string('client_reference')->nullable();

            // Date officielle
            $table->date('date_emission');

            // Statuts document
            $table->enum('statut', ['EMISE', 'ANNULEE', 'CLOTUREE'])->default('EMISE');

            // Statut de paiement (snapshot finance)
            $table->enum('statut_paiement', ['NON_PAYE', 'PARTIEL', 'PAYE'])->default('PAYE');

            $table->timestamps();

            // Une seule facture_officielle par source (par module)
            $table->unique(['module_source', 'table_source', 'source_id'], 'uniq_facture_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_officielles');
    }
};