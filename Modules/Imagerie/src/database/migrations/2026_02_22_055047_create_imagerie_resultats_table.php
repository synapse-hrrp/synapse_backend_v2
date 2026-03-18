<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagerie_resultats', function (Blueprint $table) {
            $table->id();

            // ── Lien avec l'examen effectué ───────────────────────
            $table->foreignId('imagerie_id')
                  ->constrained('imageries')
                  ->restrictOnDelete();

            // ── Compte rendu radiologique ─────────────────────────
            // Description détaillée des images par le radiologue
            $table->text('compte_rendu');

            // ── Conclusion ────────────────────────────────────────
            // Synthèse courte du compte rendu
            // Ex: "Opacité basale droite évoquant une pneumonie"
            $table->text('conclusion')->nullable();

            // ── Images ────────────────────────────────────────────
            // Chemin vers les fichiers images (DICOM, JPEG...)
            // Ex: storage/imagerie/2026/02/RX-THORAX-001.dcm
            $table->string('chemin_images', 500)->nullable();

            // Format des images
            $table->enum('format_images', [
                'dicom',  // Format médical standard
                'jpeg',
                'png',
                'pdf',
                'autre',
            ])->nullable();

            // ── Recommandations ───────────────────────────────────
            // Ex: "Contrôle radiologique dans 3 semaines"
            $table->text('recommandations')->nullable();

            // ── Statut du résultat ────────────────────────────────
            $table->enum('status', [
                'brouillon',   // En cours de rédaction
                'valide',      // Validé par le radiologue
                'transmis',    // Transmis au médecin prescripteur
            ])->default('brouillon');

            // ── Radiologue validateur ─────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            $table->timestamp('valide_le')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagerie_resultats');
    }
};