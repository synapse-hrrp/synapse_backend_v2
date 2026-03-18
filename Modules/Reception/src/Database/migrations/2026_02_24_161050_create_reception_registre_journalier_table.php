<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reception_registre_journalier', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_patient');
            $table->unsignedBigInteger('id_agent_createur')->nullable();

            $table->dateTime('date_arrivee')->nullable();
            $table->text('motif')->nullable();
            $table->boolean('urgence')->default(false);

            $table->string('statut')->default('open');

            // lien facture
            $table->unsignedBigInteger('id_demande_paiement')->nullable();

            // ✅ plan par visite
            $table->foreignId('tariff_plan_id')
                ->nullable()
                ->constrained('tariff_plans')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['id_patient', 'date_arrivee']);
            $table->index(['statut', 'urgence']);

            // FK possibles (si tes tables existent)
            // $table->foreign('id_patient')->references('id')->on('patients')->restrictOnDelete();
            // $table->foreign('id_agent_createur')->references('id')->on('agents')->nullOnDelete();

            // Lien billingRequest (t_billing_requests)
            $table->foreign('id_demande_paiement')
                ->references('id')->on('t_billing_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_registre_journalier');
    }
};