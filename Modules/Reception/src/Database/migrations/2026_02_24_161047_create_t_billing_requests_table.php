<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_billing_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_patient');
            $table->string('module_source')->nullable(); // reception|pharmacie|...
            $table->unsignedBigInteger('ref_source')->nullable(); // id de l'objet source (registre, vente...)
            $table->unsignedBigInteger('id_agent_demandeur')->nullable();

            $table->string('statut')->default('awaiting_payment');

            $table->decimal('montant_total', 12, 2)->default(0);
            $table->decimal('montant_paye', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['module_source', 'ref_source']);
            $table->index(['id_patient', 'statut']);

            // FK possibles (si tes tables existent)
            // $table->foreign('id_patient')->references('id')->on('patients')->restrictOnDelete();
            // $table->foreign('id_agent_demandeur')->references('id')->on('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_billing_requests');
    }
};