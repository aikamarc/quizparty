<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('name');
        });

        Schema::table('game_rounds', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('track_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_rounds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
