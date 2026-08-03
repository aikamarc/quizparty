<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songless_daily_songs', function (Blueprint $table) {
            $table->id();
            $table->date('play_date');
            $table->unsignedTinyInteger('position');
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['play_date', 'position']);
            $table->unique(['play_date', 'track_id']);
        });

        Schema::create('songless_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_song_id')->constrained('songless_daily_songs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_stage')->default(0);
            $table->string('status')->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['daily_song_id', 'user_id']);
            $table->index(['daily_song_id', 'status', 'current_stage']);
        });

        Schema::create('songless_guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('songless_attempts')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage');
            $table->string('guess');
            $table->string('result');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songless_guesses');
        Schema::dropIfExists('songless_attempts');
        Schema::dropIfExists('songless_daily_songs');
    }
};
