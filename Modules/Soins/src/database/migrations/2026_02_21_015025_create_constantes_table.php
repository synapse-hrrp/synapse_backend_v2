<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constantes', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la consultation ─────────────────────────
            // Les constantes sont prises pendant une consultation
            $table->foreignId('consultation_id')
                  ->constrained('consultations')
                  ->restrictOnDelete();

            // ── Qui a pris les constantes ─────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Mesures vitales ───────────────────────────────────

            // Tension artérielle
            // Ex: 120/80
            $table->string('tension_systolique', 10)->nullable();
            $table->string('tension_diastolique', 10)->nullable();

            // Fréquence cardiaque (battements/min)
            $table->unsignedSmallInteger('frequence_cardiaque')->nullable();

            // Fréquence respiratoire (cycles/min)
            $table->unsignedSmallInteger('frequence_respiratoire')->nullable();

            // Température en degrés Celsius
            // Ex: 37.5
            $table->decimal('temperature', 4, 1)->nullable();

            // Poids en kg
            $table->decimal('poids', 5, 2)->nullable();

            // Taille en cm
            $table->decimal('taille', 5, 1)->nullable();

            // Indice de masse corporelle (calculé)
            // IMC = poids / (taille/100)²
            $table->decimal('imc', 4, 1)->nullable();

            // Saturation en oxygène (%)
            $table->unsignedSmallInteger('saturation_o2')->nullable();

            // Glycémie capillaire (mmol/L)
            $table->decimal('glycemie', 5, 2)->nullable();

            // ── Observations ──────────────────────────────────────
            $table->text('observations')->nullable();

            // Moment de la prise des constantes
            $table->timestamp('pris_le')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constantes');
    }
};