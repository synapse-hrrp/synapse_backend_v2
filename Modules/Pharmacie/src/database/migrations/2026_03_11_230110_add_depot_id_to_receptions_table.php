<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->foreignId('depot_id')->nullable()->after('fournisseur_id')->constrained('depots');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropForeign(['depot_id']);
            $table->dropColumn('depot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
};
