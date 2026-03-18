<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();

            // ── Patient & registre ───────────────────────────────
            $table->foreignId('patient_id')
                  ->constrained('t_patients')
                  ->restrictOnDelete();

            $table->unsignedBigInteger('registre_id');
            $table->index('registre_id');

            // ── Tarification figée ────────────────────────────────
            $table->unsignedBigInteger('tariff_item_id')->nullable();
            $table->decimal('unit_price_applied', 10, 2)->nullable();

            // ── Lien facturation ──────────────────────────────────
            $table->unsignedBigInteger('billing_request_id')->nullable();
            $table->index('billing_request_id');

            $table->foreign('billing_request_id')
                  ->references('id')
                  ->on('t_billing_requests')
                  ->nullOnDelete();

            // ── Type de consultation (plus ENUM !) ───────────────
            $table->string('type_acte', 100)->default('consultation');

            // ── Statut métier ─────────────────────────────────────
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

            // ── Données cliniques ────────────────────────────────
            $table->text('motif')->nullable();
            $table->boolean('is_urgent')->default(false);

            // ── Médecin assigné ───────────────────────────────────
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
        Schema::dropIfExists('consultation_requests');
    }
};