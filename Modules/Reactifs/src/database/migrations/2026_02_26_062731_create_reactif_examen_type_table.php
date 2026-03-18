<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_examen_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reactif_id')->constrained('reactifs')->onDelete('cascade');
            $table->unsignedBigInteger('examen_type_id'); // lien vers module Laboratoire
            $table->decimal('quantite_utilisee', 10, 3); // quantité consommée par examen
            $table->string('unite')->nullable(); // peut différer de l'unité stock
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['reactif_id', 'examen_type_id']);
            $table->index('examen_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_examen_type');
    }
};