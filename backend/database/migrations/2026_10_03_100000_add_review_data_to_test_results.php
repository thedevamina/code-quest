<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->string('assessment_type')->default('chapter')->after('chapter_id');
            $table->unsignedBigInteger('attempt_id')->nullable()->after('assessment_type');
            $table->json('review_data')->nullable()->after('time_taken');
            $table->index(['user_id', 'assessment_type']);
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'assessment_type']);
            $table->dropColumn(['assessment_type', 'attempt_id', 'review_data']);
        });
    }
};
