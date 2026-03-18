<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pansement_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                  ->constrained('t_patients')
                  ->cascadeOnDelete();

            $table->unsignedBigInteger('registre_id');
            $table->index('registre_id'); // ✅ index utile

            $table->foreignId('tariff_item_id')
                  ->constrained('tariff_items');

            // ✅ AJOUT IMPORTANT
            $table->unsignedBigInteger('billing_request_id')->nullable();
            $table->index('billing_request_id');

            // ✅ FK recommandée (alignée avec ton système)
            $table->foreign('billing_request_id')
                  ->references('id')
                  ->on('t_billing_requests')
                  ->nullOnDelete();

            $table->decimal('unit_price_applied', 10, 2);

            $table->enum('type_pansement', [
                'simple',
                'compressif',
                'chirurgical',
                'occlusif',
                'humide',
            ]);

            $table->string('zone_anatomique', 150)->nullable();

            $table->enum('status', [
                'pending_payment',
                'authorized',
                'in_progress',
                'completed',
            ])->default('pending_payment');

            $table->boolean('is_urgent')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->index('agent_id');

            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pansement_requests');
    }
};