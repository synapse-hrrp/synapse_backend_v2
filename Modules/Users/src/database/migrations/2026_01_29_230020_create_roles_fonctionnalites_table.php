<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles_fonctionnalites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roles_id');
            $table->unsignedBigInteger('fonc_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('roles_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('fonc_id')->references('id')->on('fonctionnalites')->cascadeOnDelete();

            // ⚠️ Avec softDeletes, on ne met PAS unique strict (sinon tu ne peux pas "réactiver")
            $table->index(['roles_id', 'fonc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_fonctionnalites');
    }
};
