<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('depot_id')->constrained('depots')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // vendeur
            $table->date('date_vente');
            $table->enum('type', ['VENTE', 'GRATUITE'])->default('VENTE');
            $table->enum('statut', ['EN_ATTENTE', 'PAYEE', 'ANNULEE'])->default('EN_ATTENTE');
            $table->decimal('montant_ttc', 10, 2)->default(0);
            $table->text('observations')->nullable();
            $table->timestamps();
            
            $table->index('numero');
            $table->index('date_vente');
            $table->index(['depot_id', 'date_vente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};