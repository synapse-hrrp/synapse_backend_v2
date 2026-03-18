<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_lignes', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la prescription ─────────────────────────
            // Chaque ligne appartient à une ordonnance
            $table->foreignId('prescription_id')
                  ->constrained('prescriptions')
                  ->restrictOnDelete();

            // ── Médicament prescrit ───────────────────────────────
            // Lien vers le produit en pharmacie si disponible
            $table->unsignedBigInteger('produit_id')->nullable();
            $table->index('produit_id');
            // Pas de FK stricte car le médecin peut prescrire
            // un médicament non encore en stock

            // Nom du médicament (saisi librement)
            // Ex: Amoxicilline 500mg, Paracétamol 1g...
            $table->string('medicament', 200);

            // ── Posologie ─────────────────────────────────────────
            // Forme du médicament
            $table->enum('forme', [
                'comprime',   // Comprimé
                'sirop',      // Sirop
                'injection',  // Injectable
                'pommade',    // Pommade / crème
                'gouttes',    // Gouttes
                'sachet',     // Sachet
                'autre',
            ])->default('comprime');

            // Dosage
            // Ex: 500mg, 1g, 250mg/5ml...
            $table->string('dosage', 100)->nullable();

            // Fréquence par jour
            // Ex: 1, 2, 3 fois par jour
            $table->unsignedTinyInteger('frequence_par_jour')->default(1);

            // Durée du traitement en jours
            $table->unsignedSmallInteger('duree_jours')->nullable();

            // Quantité totale à délivrer
            $table->unsignedSmallInteger('quantite')->default(1);

            // ── Instructions spécifiques ──────────────────────────
            // Ex: "Avant les repas", "Au coucher", "À diluer"
            $table->text('instructions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_lignes');
    }
};