<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactifs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->string('unite'); // ex: ml, mg, unité, test...
            $table->decimal('stock_actuel', 10, 2)->default(0);
            $table->decimal('stock_minimum', 10, 2)->default(0); // seuil alerte
            $table->decimal('stock_maximum', 10, 2)->nullable();
            $table->string('localisation')->nullable(); // ex: frigo A, étagère 2
            $table->date('date_peremption')->nullable();
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactifs');
    }
};