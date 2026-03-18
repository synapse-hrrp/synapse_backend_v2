<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict');
            $table->date('date_commande');
            $table->enum('statut', ['EN_ATTENTE', 'PARTIELLE', 'COMPLETE', 'ANNULEE'])->default('EN_ATTENTE');
            $table->text('observations')->nullable();
            $table->timestamps();
            
            $table->index('numero');
            $table->index('date_commande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};