<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('t_finance_sessions', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('user_id');
      $table->string('poste', 50); // ex: POSTE-1 (workstation)

      $table->timestamp('ouverte_le')->nullable();
      $table->timestamp('fermee_le')->nullable();

      // agrégats
      $table->unsignedInteger('nb_paiements')->default(0);
      $table->decimal('total_montant', 15, 2)->default(0);

      // "trick" pour empêcher 2 sessions ouvertes (unique sur open_key non null)
      $table->string('cle_ouverture', 120)->nullable()->unique();

      $table->timestamps();

      $table->index(['user_id', 'poste']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('t_finance_sessions');
  }
};
