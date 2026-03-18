<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_billing_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_demande_facturation')
                ->constrained('t_billing_requests')
                ->cascadeOnDelete();

            $table->foreignId('billable_service_id')
                ->constrained('billable_services')
                ->restrictOnDelete();

            $table->foreignId('tariff_item_id')
                ->constrained('tariff_items')
                ->restrictOnDelete();

            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('total_ligne', 12, 2);

            $table->timestamps();

            $table->index(['id_demande_facturation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_billing_request_items');
    }
};