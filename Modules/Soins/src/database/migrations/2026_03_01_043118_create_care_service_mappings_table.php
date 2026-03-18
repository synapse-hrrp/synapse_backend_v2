<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCareServiceMappingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('care_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billable_service_id')->unique();
            $table->string('care_kind');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_service_mappings');
    }
}