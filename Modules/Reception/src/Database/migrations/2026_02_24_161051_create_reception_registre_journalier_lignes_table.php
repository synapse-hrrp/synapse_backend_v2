<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reception_registre_journalier_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_entree_journal')
                ->constrained('reception_registre_journalier')
                ->cascadeOnDelete();

            $table->foreignId('billable_service_id')
                ->constrained('billable_services')
                ->restrictOnDelete();

            $table->foreignId('tariff_item_id')
                ->constrained('tariff_items')
                ->restrictOnDelete();

            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 12, 2);

            $table->text('remarques')->nullable();

            $table->timestamps();

            $table->index(['id_entree_journal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_registre_journalier_lignes');
    }
};