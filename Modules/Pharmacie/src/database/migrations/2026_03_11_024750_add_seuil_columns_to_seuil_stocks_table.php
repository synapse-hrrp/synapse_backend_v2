<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seuil_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('seuil_stocks', 'seuil_min')) {
                $table->decimal('seuil_min', 10, 2)->default(5);
            }
            if (!Schema::hasColumn('seuil_stocks', 'seuil_max')) {
                $table->decimal('seuil_max', 10, 2)->default(200);
            }
        });
    }

    public function down(): void
    {
        Schema::table('seuil_stocks', function (Blueprint $table) {
            $table->dropColumn(['seuil_min', 'seuil_max']);
        });
    }
};