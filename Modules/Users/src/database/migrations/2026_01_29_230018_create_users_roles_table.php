<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('users_id');
            $table->unsignedBigInteger('roles_id');
            $table->timestamps();

            $table->foreign('users_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('roles_id')->references('id')->on('roles')->cascadeOnDelete();

            $table->unique(['users_id', 'roles_id']);
            $table->index('users_id');
            $table->index('roles_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_roles');
    }
};
