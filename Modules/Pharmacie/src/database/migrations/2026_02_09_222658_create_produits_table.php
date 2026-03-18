<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('nom', 200);
            $table->text('description')->nullable();
            $table->string('forme', 50)->nullable(); // comprimé, sirop, etc.
            $table->string('dosage', 50)->nullable(); // 500mg, 10ml, etc.
            $table->decimal('prix_achat', 10, 2)->default(0);
            $table->boolean('taxable')->default(true); // true = TVA+CA, false = exonéré
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('nom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};