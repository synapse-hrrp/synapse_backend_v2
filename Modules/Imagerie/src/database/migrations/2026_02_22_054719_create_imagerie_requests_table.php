<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagerie_requests', function (Blueprint $table) {
            $table->id();

            // ── Références patient & registre ─────────────────────
            $table->foreignId('patient_id')
                  ->constrained('t_patients')
                  ->restrictOnDelete();

            $table->unsignedBigInteger('registre_id');
            $table->index('registre_id');

            // ── Type d'examen d'imagerie demandé ──────────────────
            $table->foreignId('imagerie_type_id')
                  ->constrained('imagerie_types')
                  ->restrictOnDelete();

            // ── Tarification FIGÉE posée par Réception ────────────
            // Prix figé au moment de la demande
            // Ne change JAMAIS même si le tarif catalogue évolue
            $table->unsignedBigInteger('tariff_item_id')->nullable();
            $table->decimal('unit_price_applied', 10, 2)->nullable();

            // ── Lien facturation ──────────────────────────────────
            $table->unsignedBigInteger('billing_request_id')->nullable();
            $table->index('billing_request_id');

            // ── Statut piloté par BillableAuthorized ──────────────
            $table->enum('status', [
                'pending_payment', // Réception vient de créer
                'authorized',      // Finance confirmé → Imagerie peut exécuter
                'in_progress',     // Examen en cours
                'completed',       // Résultats disponibles
                'rejected',        // Rejeté par Finance
                'cancelled',       // Annulé
            ])->default('pending_payment');

            // Rempli automatiquement par le Listener après paiement
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // ── Informations cliniques ────────────────────────────
            // Région anatomique concernée
            // Ex: Thorax, Abdomen, Crâne, Membre inférieur...
            $table->string('region_anatomique', 100)->nullable();

            // Renseignements cliniques pour le radiologue
            $table->text('renseignements_cliniques')->nullable();

            $table->boolean('is_urgent')->default(false);

            // ── Médecin prescripteur ──────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagerie_requests');
    }
};