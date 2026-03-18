<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_types', function (Blueprint $table) {
            $table->id();

            // Nom complet de l'examen
            // Ex: Numération Formule Sanguine, Glycémie à jeun...
            $table->string('nom', 150);

            // Code court unique
            // Ex: NFS, GLY, BILI, CREAT...
            $table->string('code', 50)->unique();

            // Catégorie de l'examen
            $table->enum('categorie', [
                'hematologie',        // Sang : NFS, groupe sanguin...
                'biochimie',          // Chimie : glycémie, créatinine...
                'microbiologie',      // Bactéries, cultures...
                'parasitologie',      // Paludisme, parasites...
                'immunologie',        // Anticorps, sérologie...
                'anatomopathologie',  // Biopsies, tissus...
                'autre',
            ])->default('autre');

            // Délai de rendu des résultats en heures
            // Ex: 2 = résultats dans 2h, 24 = lendemain
            $table->unsignedInteger('delai_heures')->default(24);

            // Instructions de prélèvement
            // Ex: "À jeun depuis 8h", "Prélèvement urinaire du matin"
            $table->text('instructions')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_types');
    }
};