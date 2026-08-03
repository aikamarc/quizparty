<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('game_rooms')->where('seconds_per_round', '>', 30)->update(['seconds_per_round' => 30]);
    }

    public function down(): void
    {
        // Existing values cannot be reconstructed after being capped.
    }
};
