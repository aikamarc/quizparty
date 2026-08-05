<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('answer_mode')->default('artist_title')->after('name');
        });

        DB::table('categories')->whereExists(function ($query) {
            $query->selectRaw('1')
                ->from('category_track')
                ->join('tracks', 'tracks.id', '=', 'category_track.track_id')
                ->whereColumn('category_track.category_id', 'categories.id')
                ->where('tracks.answer_mode', 'title_only');
        })->update(['answer_mode' => 'title_only']);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('answer_mode');
        });
    }
};
