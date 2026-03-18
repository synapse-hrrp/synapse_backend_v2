<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariff_plans', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type')->nullable(); // standard|assurance|convention
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);

            // ✅ règle métier
            $table->boolean('paiement_obligatoire')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_plans');
    }
};