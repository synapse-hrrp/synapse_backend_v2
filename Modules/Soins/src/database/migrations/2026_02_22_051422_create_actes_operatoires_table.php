<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actes_operatoires', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande autorisée ────────────────────
            $table->foreignId('acte_operatoire_request_id')
                  ->constrained('acte_operatoire_requests')
                  ->restrictOnDelete();

            // ── Équipe chirurgicale ───────────────────────────────
            // Chirurgien principal
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut de l'opération ─────────────────────────────
            $table->enum('status', [
                'en_attente',   // Programmé mais pas encore commencé
                'en_cours',     // Opération en cours
                'termine',      // Opération terminée
                'complique',    // Complications survenues
                'annule',       // Annulé
            ])->default('en_attente');

            // ── Informations opératoires ──────────────────────────
            $table->string('type_operation', 200)->nullable();
            // Ex: Appendicectomie, Herniorrhaphie...

            $table->enum('type_anesthesie', [
                'generale',      // Anesthésie générale
                'locorégionale', // Rachianesthésie, péridurale
                'locale',        // Anesthésie locale
                'sedation',      // Sédation consciente
            ])->nullable();

            // ── Salle d'opération ─────────────────────────────────
            $table->string('salle', 50)->nullable();
            // Ex: Bloc A, Salle 1...

            // ── Compte rendu opératoire ───────────────────────────
            $table->text('compte_rendu')->nullable();
            // Description détaillée de l'acte réalisé

            $table->text('incidents')->nullable();
            // Incidents ou complications peropératoires

            // ── Suites opératoires ────────────────────────────────
            $table->text('suites_operatoires')->nullable();
            $table->boolean('complications')->default(false);
            $table->text('details_complications')->nullable();

            // ── Dates clés ────────────────────────────────────────
            $table->timestamp('debut_at')->nullable();   // Début opération
            $table->timestamp('fin_at')->nullable();     // Fin opération
            $table->timestamp('reveil_at')->nullable();  // Réveil anesthésie

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actes_operatoires');
    }
};