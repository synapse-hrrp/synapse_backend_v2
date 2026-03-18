<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabricants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('nom');
            $table->string('pays', 100)->nullable();
            $table->string('prefixe_code_barre', 20)->nullable();
            $table->string('contact', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabricants');
    }
};