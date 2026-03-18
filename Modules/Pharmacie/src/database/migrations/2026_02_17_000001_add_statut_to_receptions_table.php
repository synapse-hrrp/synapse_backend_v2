<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->string('statut', 20)->default('VALIDEE')->after('date_reception');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropIndex(['statut']);
            $table->dropColumn('statut');
        });
    }
};
