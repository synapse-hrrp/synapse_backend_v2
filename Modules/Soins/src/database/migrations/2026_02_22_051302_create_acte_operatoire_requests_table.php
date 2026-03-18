<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acte_operatoire_requests', function (Blueprint $table) {
            $table->id();

            // ── Références patient & registre ─────────────────────
            $table->foreignId('patient_id')
                  ->constrained('t_patients')
                  ->restrictOnDelete();

            $table->unsignedBigInteger('registre_id');
            $table->index('registre_id');

            // ── Tarification FIGÉE posée par Réception ────────────
            $table->unsignedBigInteger('tariff_item_id')->nullable();
            $table->decimal('unit_price_applied', 10, 2)->nullable();

            // ── Lien facturation ──────────────────────────────────
            $table->unsignedBigInteger('billing_request_id')->nullable();
            $table->index('billing_request_id');

            // ── Statut piloté par BillableAuthorized ──────────────
            $table->enum('status', [
                'pending_payment',
                'authorized',
                'in_progress',
                'completed',
                'rejected',
                'cancelled',
            ])->default('pending_payment');

            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // ── Informations cliniques ────────────────────────────
            // Type d'acte opératoire prévu
            // Ex: Appendicectomie, Césarienne, Hernie...
            $table->string('type_operation', 200)->nullable();

            $table->text('indication')->nullable();
            // Pourquoi cette opération est nécessaire

            $table->boolean('is_urgent')->default(false);

            // ── Chirurgien assigné ────────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // Date prévue de l'opération
            $table->timestamp('date_prevue')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acte_operatoire_requests');
    }
};