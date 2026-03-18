<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_finance_sessions', function (Blueprint $table) {
            // ✅ Chez toi, pas de service_id. On met après "poste" si possible.
            if (!Schema::hasColumn('t_finance_sessions', 'module_scope')) {
                $table->string('module_scope', 30)->nullable()->after('poste')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_finance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('t_finance_sessions', 'module_scope')) {
                $table->dropColumn('module_scope');
            }
        });
    }
};
