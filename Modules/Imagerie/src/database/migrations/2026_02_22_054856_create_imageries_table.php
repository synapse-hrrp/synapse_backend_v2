<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imageries', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande autorisée ────────────────────
            // Une imagerie est toujours créée depuis une
            // imagerie_request avec status = authorized
            $table->foreignId('imagerie_request_id')
                  ->constrained('imagerie_requests')
                  ->restrictOnDelete();

            // ── Radiologue / Technicien ───────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut d'exécution ────────────────────────────────
            $table->enum('status', [
                'en_attente',  // Autorisé mais pas encore commencé
                'en_cours',    // Examen en cours
                'termine',     // Examen terminé
                'valide',      // Validé par le radiologue
                'annule',      // Annulé
            ])->default('en_attente');

            // ── Appareil utilisé ──────────────────────────────────
            // Ex: Appareil RX Salle 1, Echo Siemens...
            $table->string('appareil', 100)->nullable();

            // ── Salle d'examen ────────────────────────────────────
            $table->string('salle', 50)->nullable();

            // ── Produit de contraste ──────────────────────────────
            $table->boolean('produit_contraste')->default(false);
            $table->string('type_contraste', 100)->nullable();
            // Ex: Iode, Gadolinium...

            // ── Incidents ─────────────────────────────────────────
            $table->boolean('incidents')->default(false);
            $table->text('details_incidents')->nullable();

            // ── Dates clés ────────────────────────────────────────
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('validated_at')->nullable();

            // ── Observations ──────────────────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imageries');
    }
};