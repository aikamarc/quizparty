<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('code');
        });

        DB::table('game_rooms')->select('id')->orderBy('id')->each(function ($room) {
            DB::table('game_rooms')->where('id', $room->id)->update(['public_id' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
