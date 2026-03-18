<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactif_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code')->unique()->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('pays')->nullable();
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactif_fournisseurs');
    }
};