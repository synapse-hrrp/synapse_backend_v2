<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('t_finance_paiements', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('session_id');
      $table->unsignedBigInteger('encaisse_par_user_id');

      // liaison générique vers la "source"
      $table->string('module_source', 50);  // pharmacie, reception...
      $table->string('table_source', 80);   // ventes, t_billing_requests...
      $table->unsignedBigInteger('source_id');

      $table->decimal('montant', 15, 2);
      $table->string('mode', 30);       // momo, cash, carte...
      $table->string('reference', 100)->nullable();

      $table->enum('statut', ['valide', 'annule'])->default('valide');

      // annulation
      $table->text('raison_annulation')->nullable();
      $table->timestamp('annule_le')->nullable();
      $table->unsignedBigInteger('annule_par_user_id')->nullable();

      $table->timestamps();

      $table->index(['table_source', 'source_id']);
      $table->index(['session_id', 'statut']);

      $table->foreign('session_id')->references('id')->on('t_finance_sessions');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('t_finance_paiements');
  }
};
