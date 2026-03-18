<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_resultats', function (Blueprint $table) {
            $table->id();

            // ── Lien avec l'examen exécuté ────────────────────────
            // Un résultat appartient toujours à un examen
            $table->foreignId('examen_id')
                  ->constrained('examens')
                  ->restrictOnDelete();

            // ── Paramètre mesuré ──────────────────────────────────
            // Ex: Hémoglobine, Glycémie, Créatinine...
            $table->string('parametre', 150);

            // ── Valeur mesurée ────────────────────────────────────
            // Ex: 12.5, 0.8, Positif, Négatif...
            $table->string('valeur', 100);

            // ── Unité de mesure ───────────────────────────────────
            // Ex: g/dL, mmol/L, mg/L...
            $table->string('unite', 50)->nullable();

            // ── Valeurs normales de référence ─────────────────────
            // Ex: 12.0 - 16.0
            $table->string('valeur_normale_min', 50)->nullable();
            $table->string('valeur_normale_max', 50)->nullable();

            // ── Interprétation automatique ────────────────────────
            $table->enum('interpretation', [
                'normal',   // Dans les normes
                'bas',      // En dessous de la normale
                'eleve',    // Au dessus de la normale
                'positif',  // Pour les examens qualitatifs
                'negatif',  // Pour les examens qualitatifs
            ])->nullable();

            // ── Observations du technicien ────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_resultats');
    }
};