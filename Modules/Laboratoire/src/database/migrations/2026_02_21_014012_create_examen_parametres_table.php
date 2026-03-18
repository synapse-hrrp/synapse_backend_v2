<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_parametres', function (Blueprint $table) {
            $table->id();

            // ── Lien avec le type d'examen ────────────────────────
            // Chaque type d'examen a ses propres paramètres
            // Ex: NFS → Hémoglobine, Leucocytes, Plaquettes...
            $table->foreignId('examen_type_id')
                  ->constrained('examen_types')
                  ->restrictOnDelete();

            // ── Nom du paramètre ──────────────────────────────────
            // Ex: Hémoglobine, Glycémie, Créatinine...
            $table->string('nom', 150);

            // ── Code court ────────────────────────────────────────
            // Ex: HGB, GLY, CREAT...
            $table->string('code', 50);

            // ── Unité de mesure ───────────────────────────────────
            // Ex: g/dL, mmol/L, mg/L, %...
            $table->string('unite', 50)->nullable();

            // ── Valeurs normales de référence ─────────────────────
            $table->decimal('normale_min', 8, 2)->nullable();
            $table->decimal('normale_max', 8, 2)->nullable();

            // ── Type de valeur attendue ───────────────────────────
            $table->enum('type_valeur', [
                'numerique',    // Ex: 12.5, 0.8
                'qualitatif',   // Ex: Positif / Négatif
                'texte',        // Ex: observations libres
            ])->default('numerique');

            // Ordre d'affichage dans le rapport
            $table->unsignedInteger('ordre')->default(0);

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_parametres');
    }
};