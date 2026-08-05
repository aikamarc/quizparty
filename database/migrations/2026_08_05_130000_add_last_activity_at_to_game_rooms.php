<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->index()->after('host_last_seen_at');
        });
        Schema::table('culture_rooms', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->index()->after('question_ends_at');
        });

        DB::table('game_rooms')->whereNull('last_activity_at')->update(['last_activity_at'=>now()]);
        DB::table('culture_rooms')->whereNull('last_activity_at')->update(['last_activity_at'=>now()]);
    }

    public function down(): void
    {
        Schema::table('game_rooms', fn (Blueprint $table) => $table->dropColumn('last_activity_at'));
        Schema::table('culture_rooms', fn (Blueprint $table) => $table->dropColumn('last_activity_at'));
    }
};
