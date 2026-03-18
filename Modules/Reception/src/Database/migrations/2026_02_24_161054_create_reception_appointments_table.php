<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reception_appointments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('id_medecin_agent')->nullable();
            $table->unsignedBigInteger('id_agent_createur')->nullable();

            $table->unsignedBigInteger('id_entree_registre')->nullable();

            $table->dateTime('date_heure');
            $table->integer('duree_minutes')->default(15);

            $table->string('statut')->default('booked');
            $table->text('motif')->nullable();
            $table->text('remarques')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'date_heure']);
            $table->index(['statut']);

            // FK possibles (si tes tables existent)
            // $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            // $table->foreign('id_medecin_agent')->references('id')->on('agents')->nullOnDelete();
            // $table->foreign('id_agent_createur')->references('id')->on('agents')->nullOnDelete();

            $table->foreign('id_entree_registre')
                ->references('id')->on('reception_registre_journalier')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_appointments');
    }
};