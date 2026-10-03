<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RichLessonContentSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = json_decode(
            file_get_contents(__DIR__.'/lesson_content.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $created = 0;
        $updated = 0;

        foreach ($lessons as $key => $lesson) {
            if (! str_contains($key, '-')) {
                continue;
            }

            [$courseSlug, $chapterNumber] = explode('-', $key, 2);
            $chapter = DB::table('chapters')
                ->join('courses', 'courses.id', '=', 'chapters.course_id')
                ->where('courses.slug', $courseSlug)
                ->where('chapters.number', (int) $chapterNumber)
                ->select('chapters.id')
                ->first();

            if (! $chapter) {
                throw new RuntimeException("Missing chapter for lesson [{$key}].");
            }

            $existing = DB::table('learning_materials')
                ->where('chapter_id', $chapter->id)
                ->where('title', $lesson['title'])
                ->orderBy('id')
                ->first();

            $materialData = [
                'title' => $lesson['title'],
                'content' => json_encode($lesson, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('learning_materials')->where('id', $existing->id)->update($materialData);
                $updated++;

                continue;
            }

            DB::table('learning_materials')->insert($materialData + [
                'chapter_id' => $chapter->id,
                'created_at' => now(),
            ]);
            $created++;
        }

        $this->command?->info("Lesson materials: {$created} created, {$updated} updated.");
    }
}
