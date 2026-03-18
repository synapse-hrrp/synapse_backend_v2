<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_agents', function (Blueprint $table) {
            $table->id();

            // champs minimum (tu peux ajuster ensuite)
            $table->string('matricule')->nullable()->unique();
            $table->string('statut')->nullable();

            // TODO plus tard
            $table->unsignedBigInteger('personne_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_agents');
    }
};
