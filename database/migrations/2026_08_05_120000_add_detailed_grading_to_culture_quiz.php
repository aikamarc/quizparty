<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('culture_room_players', function (Blueprint $table) {
            $table->decimal('score', 8, 1)->default(0)->change();
        });

        Schema::table('culture_answers', function (Blueprint $table) {
            $table->json('grading')->nullable()->after('answer');
            $table->decimal('awarded_points', 8, 1)->default(0)->after('grading');
        });
    }

    public function down(): void
    {
        Schema::table('culture_answers', function (Blueprint $table) {
            $table->dropColumn(['grading', 'awarded_points']);
        });

        Schema::table('culture_room_players', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->change();
        });
    }
};
