<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billable_services', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('categorie');
            $table->text('description')->nullable();

            $table->boolean('active')->default(true);

            // ✅ règles métier (hôpital)
            $table->boolean('rendez_vous_obligatoire')->default(false);
            $table->boolean('necessite_medecin')->default(false);
            $table->boolean('paiement_obligatoire_avant_prestation')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['categorie', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billable_services');
    }
};