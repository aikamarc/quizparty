<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->boolean('anti_cheat')->default(false);
            $table->unsignedTinyInteger('lives_per_round')->default(3);
        });

        Schema::create('game_round_player_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('lives_remaining');
            $table->boolean('disqualified')->default(false);
            $table->timestamps();
            $table->unique(['game_round_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_round_player_states');
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropColumn(['anti_cheat', 'lives_per_round']);
        });
    }
};
