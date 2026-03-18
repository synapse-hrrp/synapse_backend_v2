<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la consultation ─────────────────────────
            // Une prescription est toujours liée à une consultation
            $table->foreignId('consultation_id')
                  ->constrained('consultations')
                  ->restrictOnDelete();

            // ── Médecin prescripteur ──────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut de la prescription ─────────────────────────
            $table->enum('status', [
                'brouillon',  // En cours de rédaction
                'emise',      // Remise au patient
                'dispensee',  // Médicaments délivrés par pharmacie
                'annulee',    // Annulée
            ])->default('brouillon');

            // ── Informations générales ────────────────────────────
            $table->text('instructions_generales')->nullable();
            // Ex: "Prendre après les repas", "Éviter l'alcool"

            // Date de validité de l'ordonnance
            $table->date('valide_jusqu_au')->nullable();

            // ── Renouvellement ────────────────────────────────────
            $table->boolean('renouvelable')->default(false);
            $table->unsignedTinyInteger('nombre_renouvellements')->default(0);

            $table->timestamp('emise_le')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};