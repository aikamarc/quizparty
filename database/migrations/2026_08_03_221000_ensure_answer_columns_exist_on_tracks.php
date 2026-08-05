<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tracks', 'answer_mode')) {
            Schema::table('tracks', function (Blueprint $table) {
                $table->string('answer_mode')->default('artist_title')->after('artist');
            });
        }

        if (! Schema::hasColumn('tracks', 'custom_answer')) {
            Schema::table('tracks', function (Blueprint $table) {
                $table->string('custom_answer')->nullable()->after('answer_mode');
            });
        }
    }

    public function down(): void
    {
        // This migration repairs a potentially inconsistent production schema.
        // Its rollback intentionally keeps the columns owned by the original migration.
    }
};
