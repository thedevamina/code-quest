<?php

namespace Tests\Feature;

use Database\Seeders\RichLessonContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RichLessonContentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_existing_lessons_are_available_from_the_chapter_material_api(): void
    {
        $lessons = json_decode(
            file_get_contents(database_path('seeders/lesson_content.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $courseIds = [];
        $chapterIds = [];

        foreach ($lessons as $key => $lesson) {
            if (! str_contains($key, '-')) {
                continue;
            }

            [$courseSlug, $chapterNumber] = explode('-', $key, 2);
            $courseIds[$courseSlug] ??= DB::table('courses')->insertGetId([
                'slug' => $courseSlug,
                'title' => strtoupper($courseSlug),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $chapterIds[$key] = DB::table('chapters')->insertGetId([
                'course_id' => $courseIds[$courseSlug],
                'slug' => $key,
                'number' => (int) $chapterNumber,
                'title' => $lesson['title'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('learning_materials')->insert([
            'chapter_id' => $chapterIds['oop-1'],
            'title' => 'Instructor resource',
            'content' => json_encode(['body' => 'Keep this resource'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(RichLessonContentSeeder::class);

        $this->assertDatabaseCount('learning_materials', 38);
        $this->assertDatabaseHas('learning_materials', [
            'chapter_id' => $chapterIds['oop-1'],
            'title' => 'Instructor resource',
        ]);
        $this->assertDatabaseHas('learning_materials', [
            'chapter_id' => $chapterIds['oop-1'],
            'title' => 'The Big Picture',
        ]);

        $this->getJson('/api/chapters/oop-2/material')
            ->assertOk()
            ->assertJsonPath('materials.0.content', $lessons['oop-2']);

        $this->seed(RichLessonContentSeeder::class);

        $this->assertDatabaseCount('learning_materials', 38);
    }
}
