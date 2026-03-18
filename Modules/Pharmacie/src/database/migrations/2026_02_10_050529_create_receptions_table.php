<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('commande_id')->nullable()->constrained('commandes')->onDelete('set null');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict');
            $table->date('date_reception');
            $table->text('observations')->nullable();
            $table->timestamps();
            
            $table->index('numero');
            $table->index('date_reception');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptions');
    }
};