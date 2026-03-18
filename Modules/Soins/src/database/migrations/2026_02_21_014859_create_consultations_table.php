<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande autorisée ────────────────────
            // Une consultation est toujours créée depuis une
            // consultation_request avec status = authorized
            $table->foreignId('consultation_request_id')
                  ->constrained('consultation_requests')
                  ->restrictOnDelete();

            // ── Médecin qui consulte ──────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut de la consultation ─────────────────────────
            $table->enum('status', [
                'en_cours',   // Médecin en train de consulter
                'termine',    // Consultation terminée
                'annule',     // Annulée
            ])->default('en_cours');

            // ── Anamnèse ──────────────────────────────────────────
            // Histoire de la maladie racontée par le patient
            $table->text('anamnese')->nullable();

            // ── Examen clinique ───────────────────────────────────
            $table->text('examen_clinique')->nullable();

            // ── Diagnostic ────────────────────────────────────────
            $table->string('diagnostic', 255)->nullable();

            // Code CIM-10 si disponible
            // Ex: J06.9, A09, B54...
            $table->string('code_cim10', 20)->nullable();

            // ── Conclusion & conduite à tenir ─────────────────────
            $table->text('conclusion')->nullable();

            // ── Dates ─────────────────────────────────────────────
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};