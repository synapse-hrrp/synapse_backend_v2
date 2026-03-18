<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('factures_officielles', function (Blueprint $table) {
            // nullable car toutes les factures ne viennent pas d'une billing_request
            $table->unsignedBigInteger('billing_request_id')->nullable()->after('source_id');

            // index pour recherche rapide
            $table->index('billing_request_id', 'idx_factures_officielles_billing_request_id');

            // FK vers t_billing_requests
            $table->foreign('billing_request_id', 'fk_factures_officielles_billing_request_id')
                ->references('id')
                ->on('t_billing_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('factures_officielles', function (Blueprint $table) {
            $table->dropForeign('fk_factures_officielles_billing_request_id');
            $table->dropIndex('idx_factures_officielles_billing_request_id');
            $table->dropColumn('billing_request_id');
        });
    }
};