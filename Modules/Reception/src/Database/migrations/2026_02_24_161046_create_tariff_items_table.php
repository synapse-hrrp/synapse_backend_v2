<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariff_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tariff_plan_id')
                ->constrained('tariff_plans')
                ->restrictOnDelete();

            $table->foreignId('billable_service_id')
                ->constrained('billable_services')
                ->restrictOnDelete();

            $table->decimal('prix_unitaire', 12, 2);
            $table->boolean('active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            // Un service = 1 tarif par plan
            $table->unique(['tariff_plan_id', 'billable_service_id'], 'uniq_plan_service');

            $table->index(['tariff_plan_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_items');
    }
};