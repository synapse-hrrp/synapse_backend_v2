<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabServiceMappingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('lab_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billable_service_id')->unique();
            $table->unsignedBigInteger('examen_type_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_service_mappings');
    }
}