<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_billing_request_items', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('prix_unitaire');
        });
    }

    public function down(): void
    {
        Schema::table('t_billing_request_items', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
