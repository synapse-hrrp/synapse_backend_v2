<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('declarations_naissance', function (Blueprint $table) {
            $table->id();

            // ── Lien avec l'accouchement ──────────────────────────
            // Une déclaration est toujours liée à un accouchement
            $table->foreignId('accouchement_id')
                  ->constrained('accouchements')
                  ->restrictOnDelete();

            // ── Informations sur le nouveau-né ────────────────────
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 100);

            $table->enum('sexe', [
                'masculin',
                'feminin',
                'indetermine',
            ]);

            $table->timestamp('date_heure_naissance');

            // Lieu de naissance
            $table->string('lieu_naissance', 200)->nullable();
            // Ex: Maternité Centrale, Clinique Saint-Joseph...

            // Poids à la naissance en grammes
            $table->unsignedSmallInteger('poids_naissance')->nullable();

            // Taille à la naissance en cm
            $table->decimal('taille_naissance', 4, 1)->nullable();

            // ── Informations sur les parents ──────────────────────
            // Mère — liée au patient (t_patients)
            $table->foreignId('mere_patient_id')
                  ->constrained('t_patients')
                  ->restrictOnDelete();

            // Père
            $table->string('pere_nom', 100)->nullable();
            $table->string('pere_prenom', 100)->nullable();
            $table->string('pere_profession', 100)->nullable();

            // ── Statut de la déclaration ──────────────────────────
            $table->enum('status', [
                'brouillon',    // En cours de rédaction
                'validee',      // Validée par le médecin
                'transmise',    // Transmise à l'état civil
                'enregistree',  // Enregistrée à l'état civil
            ])->default('brouillon');

            // ── Numéro d'acte de naissance ────────────────────────
            // Attribué par l'état civil après enregistrement
            $table->string('numero_acte', 100)->nullable();
            $table->date('date_enregistrement')->nullable();

            // ── Agent qui a fait la déclaration ───────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Observations ──────────────────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('declarations_naissance');
    }
};