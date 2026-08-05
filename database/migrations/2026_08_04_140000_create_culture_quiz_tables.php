<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('culture_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('culture_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_category_id')->constrained('culture_categories')->cascadeOnDelete();
            $table->string('type')->default('standard');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
        Schema::create('culture_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_question_id')->constrained('culture_questions')->cascadeOnDelete();
            $table->string('locale', 2);
            $table->text('question');
            $table->text('answer')->nullable();
            $table->unique(['culture_question_id', 'locale']);
        });
        Schema::create('culture_rooms', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 6)->unique();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('lobby');
            $table->unsignedSmallInteger('questions_total')->default(10);
            $table->unsignedSmallInteger('seconds_per_question')->default(30);
            $table->unsignedSmallInteger('current_question')->default(0);
            $table->timestamp('question_ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('culture_category_room', function (Blueprint $table) {
            $table->foreignId('culture_room_id')->constrained('culture_rooms')->cascadeOnDelete();
            $table->foreignId('culture_category_id')->constrained('culture_categories')->cascadeOnDelete();
            $table->primary(['culture_room_id', 'culture_category_id']);
        });
        Schema::create('culture_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_room_id')->constrained('culture_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unique(['culture_room_id', 'user_id']);
        });
        Schema::create('culture_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_room_id')->constrained('culture_rooms')->cascadeOnDelete();
            $table->foreignId('culture_question_id')->nullable()->constrained('culture_questions')->nullOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('special_type')->nullable();
            $table->string('special_letter', 1)->nullable();
            $table->unique(['culture_room_id', 'position']);
        });
        Schema::create('culture_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_round_id')->constrained('culture_rounds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('answer');
            $table->boolean('is_correct')->nullable();
            $table->boolean('poll_open')->default(false);
            $table->unique(['culture_round_id', 'user_id']);
        });
        Schema::create('culture_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culture_answer_id')->constrained('culture_answers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('accepted');
            $table->unique(['culture_answer_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['culture_votes', 'culture_answers', 'culture_rounds', 'culture_room_players', 'culture_category_room', 'culture_rooms', 'culture_question_translations', 'culture_questions', 'culture_categories'] as $table) Schema::dropIfExists($table);
    }
};
