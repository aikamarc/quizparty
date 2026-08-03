<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('lobby');
            $table->unsignedSmallInteger('rounds_total')->default(10);
            $table->unsignedSmallInteger('seconds_per_round')->default(20);
            $table->unsignedSmallInteger('current_round')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
