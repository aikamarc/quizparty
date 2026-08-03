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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('artist');
            $table->string('album')->nullable();
            $table->text('cover_url')->nullable();
            $table->text('preview_url');
            $table->string('source')->default('deezer');
            $table->string('source_id');
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
