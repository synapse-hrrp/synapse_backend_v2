<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kinesitherapies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kinesitherapie_request_id')->constrained('kinesitherapie_requests');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->enum('status', ['en_cours', 'termine'])->default('en_cours');
            $table->text('observations')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kinesitherapies');
    }
};