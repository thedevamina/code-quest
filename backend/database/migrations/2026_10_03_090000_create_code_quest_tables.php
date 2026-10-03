<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name')->default('');
            $table->string('learning_goal')->default('');
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('level')->nullable();
            $table->timestamps();
        });
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('available');
            $table->string('duration')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'number']);
        });
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('content');
            $table->timestamps();
        });
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('chapter');
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('option');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->unsignedInteger('correct_answers');
            $table->unsignedInteger('incorrect_answers');
            $table->string('time_taken');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('course_id');
            $table->string('chapter_id');
            $table->unsignedInteger('score');
            $table->unsignedInteger('correct_answers');
            $table->unsignedInteger('incorrect_answers');
            $table->string('time_taken');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->timestamps();
            $table->unique(['course_id', 'type']);
        });
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->json('options');
            $table->unsignedInteger('correct_index');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->unsignedInteger('correct_answers');
            $table->unsignedInteger('incorrect_answers');
            $table->string('time_taken');
            $table->timestamps();
        });
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('selected_index')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
        Schema::create('chapter_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_completions');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('profiles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
