<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(['prix_achat', 'taxable']);
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->decimal('prix_achat', 10, 2)->default(0)->after('dosage');
            $table->boolean('taxable')->default(true)->after('prix_achat');
        });
    }
};