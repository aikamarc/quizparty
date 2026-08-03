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
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->timestamp('started_at');
            $table->timestamp('ends_at');
            $table->foreignId('artist_found_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('artist_found_at')->nullable();
            $table->foreignId('title_found_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('title_found_at')->nullable();
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
