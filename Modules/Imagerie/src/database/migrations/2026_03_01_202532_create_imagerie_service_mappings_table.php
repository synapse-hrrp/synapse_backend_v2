<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagerieServiceMappingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('imagerie_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billable_service_id')->unique();
            $table->unsignedBigInteger('imagerie_type_id');
            $table->timestamps();
            $table->foreign('billable_service_id')
                ->references('id')->on('billable_services')
                ->cascadeOnDelete();
            $table->foreign('imagerie_type_id')
                ->references('id')->on('imagerie_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagerie_service_mappings');
    }
}