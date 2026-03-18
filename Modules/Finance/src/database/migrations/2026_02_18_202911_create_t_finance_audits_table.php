<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('t_finance_audits', function (Blueprint $table) {
      $table->id();

      $table->string('evenement', 50); // SESSION_OUVERTE, SESSION_FERMEE, PAIEMENT_CREE, PAIEMENT_ANNULE

      $table->unsignedBigInteger('session_id')->nullable();
      $table->unsignedBigInteger('user_id');

      $table->unsignedBigInteger('paiement_id')->nullable();

      $table->string('table_source', 80)->nullable();
      $table->unsignedBigInteger('source_id')->nullable();

      $table->json('payload')->nullable();

      $table->timestamp('cree_le')->useCurrent(); // audit immuable
      // pas de updated_at
      $table->index(['evenement', 'user_id', 'cree_le']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('t_finance_audits');
  }
};
