<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affectation_agents', function (Blueprint $table) {
            $table->id();

            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('structure_id')->nullable(); // TODO plus tard

            $table->boolean('active')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agent_id')->references('id')->on('t_agents')->cascadeOnDelete();
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectation_agents');
    }
};
