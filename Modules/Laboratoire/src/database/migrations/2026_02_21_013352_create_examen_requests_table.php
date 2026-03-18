<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_requests', function (Blueprint $table) {
            $table->id();

            // ── Références patient & registre ─────────────────────
            $table->foreignId('patient_id')
                  ->constrained('t_patients')
                  ->restrictOnDelete();

            // Registre journalier = la visite du patient
            // reception_registre_journalier
            $table->unsignedBigInteger('registre_id');
            $table->index('registre_id');

            // ── Type d'examen demandé ─────────────────────────────
            // Dépend de examen_types → migrer examen_types AVANT
            $table->foreignId('examen_type_id')
                  ->constrained('examen_types')
                  ->restrictOnDelete();

            // ── Tarification FIGÉE posée par Réception ────────────
            // Prix figé au moment de la demande
            // Ne change JAMAIS même si le tarif catalogue évolue
            $table->unsignedBigInteger('tariff_item_id')->nullable();
            $table->decimal('unit_price_applied', 10, 2)->nullable();

            // ── Lien avec la facturation existante ────────────────
            // Référence vers t_billing_requests
            $table->unsignedBigInteger('billing_request_id')->nullable();
            $table->index('billing_request_id');

            // ── Statut piloté par BillableAuthorized ──────────────
            // pending_payment → authorized → in_progress → completed
            $table->enum('status', [
                'pending_payment', // Réception vient de créer la demande
                'authorized',      // Finance confirmé → Labo peut exécuter
                'in_progress',     // Labo en cours d'exécution
                'completed',       // Résultats disponibles
                'rejected',        // Rejeté par Finance
                'cancelled',       // Annulé
            ])->default('pending_payment');

            // Rempli automatiquement par le Listener après paiement
            $table->timestamp('authorized_at')->nullable();

            // Rempli quand le Labo termine l'examen
            $table->timestamp('completed_at')->nullable();

            // ── Informations cliniques ────────────────────────────
            $table->text('notes')->nullable();
            $table->boolean('is_urgent')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_requests');
    }
};