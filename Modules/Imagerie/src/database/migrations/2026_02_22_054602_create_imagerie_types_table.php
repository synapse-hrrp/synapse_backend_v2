<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagerie_types', function (Blueprint $table) {
            $table->id();

            // ── Nom de l'examen d'imagerie ────────────────────────
            // Ex: Radiographie thorax, Echo abdominale, Scanner...
            $table->string('nom', 150);

            // ── Code court unique ─────────────────────────────────
            // Ex: RX-THORAX, ECHO-ABD, TDM-CRANE...
            $table->string('code', 50)->unique();

            // ── Catégorie d'imagerie ──────────────────────────────
            $table->enum('categorie', [
                'radiographie',   // Rayons X standard
                'echographie',    // Ultrasons
                'scanner',        // TDM / Scanner
                'irm',            // Imagerie par résonance magnétique
                'mammographie',   // Mammographie
                'endoscopie',     // Endoscopie digestive, ORL...
                'autre',
            ])->default('autre');

            // ── Délai de rendu des résultats en heures ────────────
            $table->unsignedInteger('delai_heures')->default(24);

            // ── Préparation nécessaire ────────────────────────────
            // Ex: "À jeun depuis 6h", "Vessie pleine"
            $table->text('preparation')->nullable();

            // ── Contre-indications ────────────────────────────────
            // Ex: "Grossesse", "Pacemaker", "Allergie produit contraste"
            $table->text('contre_indications')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagerie_types');
    }
};