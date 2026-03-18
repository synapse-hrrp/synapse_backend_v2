<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fonctionnalites', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('modules_id')->nullable();
            $table->unsignedBigInteger('parent')->nullable();

            $table->string('label');              // ex: "Créer utilisateur"
            $table->string('tech_label')->unique(); // ex: "users.create"

            $table->timestamps();

            // FK vers modules
            $table->foreign('modules_id')
                ->references('id')->on('modules')
                ->nullOnDelete();

            // parent/enfants
            $table->foreign('parent')
                ->references('id')->on('fonctionnalites')
                ->nullOnDelete();

            $table->index('modules_id');
            $table->index('parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fonctionnalites');
    }
};
