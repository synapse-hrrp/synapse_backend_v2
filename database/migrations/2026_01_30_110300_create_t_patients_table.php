<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_patients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('personne_id')
                ->constrained('t_personnes')
                ->cascadeOnDelete();

            $table->string('nip', 50)->unique();

            // Champs demandés par ton insert
            $table->string('telephone', 30)->nullable();
            $table->string('adresse', 255)->nullable();

            // Autres champs optionnels
            $table->string('code_patient', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 1 personne = 1 patient max
            $table->unique('personne_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_patients');
    }
};
