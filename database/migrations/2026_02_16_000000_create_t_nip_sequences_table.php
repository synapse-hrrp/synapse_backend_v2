<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_nip_sequences', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('annee')->unique(); // 2026, 2027...
            $table->unsignedInteger('dernier_numero')->default(0);

            $table->timestamps();

            $table->index('annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_nip_sequences');
    }
};
