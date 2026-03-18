<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitalisations', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande autorisée ────────────────────
            $table->foreignId('hospitalisation_request_id')
                  ->constrained('hospitalisation_requests')
                  ->restrictOnDelete();

            // ── Médecin responsable ───────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut du séjour ──────────────────────────────────
            $table->enum('status', [
                'en_cours',   // Patient hospitalisé
                'termine',    // Patient sorti
                'transfere',  // Transféré vers un autre service
                'decede',     // Décès durant le séjour
                'annule',     // Annulé
            ])->default('en_cours');

            // ── Localisation ──────────────────────────────────────
            // Service d'hospitalisation
            $table->string('service', 100)->nullable();
            // Ex: Médecine interne, Chirurgie, Pédiatrie...

            // Numéro de chambre et de lit
            $table->string('chambre', 50)->nullable();
            $table->string('lit', 50)->nullable();

            // ── Diagnostic d'admission ────────────────────────────
            $table->text('diagnostic_admission')->nullable();
            $table->string('code_cim10', 20)->nullable();

            // ── Diagnostic de sortie ──────────────────────────────
            $table->text('diagnostic_sortie')->nullable();

            // ── Mode de sortie ────────────────────────────────────
            $table->enum('mode_sortie', [
                'guerison',       // Guéri
                'amelioration',   // Amélioré
                'stationnaire',   // Sans changement
                'transfert',      // Transféré
                'contre_avis',    // Sorti contre avis médical
                'decede',         // Décédé
            ])->nullable();

            // ── Durée du séjour ───────────────────────────────────
            $table->timestamp('admission_at')->nullable();
            $table->timestamp('sortie_at')->nullable();
            // Durée calculée en jours (colonne virtuelle)
            // = DATEDIFF(sortie_at, admission_at)

            // ── Observations ──────────────────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalisations');
    }
};