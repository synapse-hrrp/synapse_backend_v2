<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande ──────────────────────────────
            // Un examen est toujours créé depuis une examen_request
            // autorisée (status = authorized)
            $table->foreignId('examen_request_id')
                  ->constrained('examen_requests')
                  ->restrictOnDelete();

            // ── Qui a exécuté l'examen ────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut d'exécution ────────────────────────────────
            // Distinct du statut de paiement dans examen_requests
            $table->enum('status', [
                'en_attente',    // Autorisé mais pas encore commencé
                'en_cours',      // Technicien en train de traiter
                'termine',       // Résultats saisis
                'valide',        // Validé par le biologiste
                'annule',        // Annulé
            ])->default('en_attente');

            // ── Dates clés ────────────────────────────────────────
            $table->timestamp('started_at')->nullable();   // Début de l'examen
            $table->timestamp('finished_at')->nullable();  // Fin de l'examen
            $table->timestamp('validated_at')->nullable(); // Validation biologiste

            // ── Observations générales ────────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};