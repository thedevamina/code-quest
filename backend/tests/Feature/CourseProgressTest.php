<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_calculated_per_course_and_completion_cannot_be_assigned_to_another_course(): void
    {
        $user = User::factory()->create();
        $courseIds = [];
        $chapterIds = [];
        foreach (['oop' => 16, 'dsa' => 16, 'dbms' => 5] as $slug => $total) {
            $courseIds[$slug] = DB::table('courses')->insertGetId([
                'slug' => $slug,
                'title' => strtoupper($slug),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            for ($number = 1; $number <= $total; $number++) {
                $chapterIds[$slug][] = DB::table('chapters')->insertGetId([
                    'course_id' => $courseIds[$slug],
                    'slug' => "{$slug}-{$number}",
                    'number' => $number,
                    'title' => "{$slug} chapter {$number}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (['oop' => 5, 'dsa' => 8, 'dbms' => 2] as $slug => $completed) {
            foreach (array_slice($chapterIds[$slug], 0, $completed) as $chapterId) {
                DB::table('chapter_completions')->insert([
                    'user_id' => $user->id,
                    'course_id' => $courseIds[$slug],
                    'chapter_id' => $chapterId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->actingAs($user)->getJson('/api/progress')->assertOk()
            ->assertJsonPath('course_progress.0.course_id', 'oop')
            ->assertJsonPath('course_progress.0.total_chapters', 16)
            ->assertJsonPath('course_progress.0.completed_chapters', 5)
            ->assertJsonPath('course_progress.0.progress', 31)
            ->assertJsonPath('course_progress.1.course_id', 'dsa')
            ->assertJsonPath('course_progress.1.progress', 50)
            ->assertJsonPath('course_progress.2.course_id', 'dbms')
            ->assertJsonPath('course_progress.2.total_chapters', 5)
            ->assertJsonPath('course_progress.2.progress', 40);

        $this->postJson('/api/chapters/'.$chapterIds['oop'][5].'/completion', ['course_id' => $courseIds['dsa']])
            ->assertNotFound();

        $this->postJson('/api/chapters/'.$chapterIds['dsa'][8].'/completion', ['course_id' => $courseIds['dsa']])
            ->assertOk()
            ->assertJsonPath('course_progress.0.progress', 31)
            ->assertJsonPath('course_progress.1.completed_chapters', 9)
            ->assertJsonPath('course_progress.1.progress', 56)
            ->assertJsonPath('course_progress.2.progress', 40);
    }
}
