<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_content_crud_persists_courses_chapters_materials_and_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum');

        $course = $this->postJson('/api/admin/courses', [
            'slug' => 'admin-crud-course',
            'title' => 'Admin CRUD Course',
            'code' => 'CS-900',
            'level' => 'Advanced',
            'description' => 'Course description',
        ])->assertCreated()->assertJsonPath('course.title', 'Admin CRUD Course')->json('course');

        $chapter = $this->postJson('/api/admin/chapters', [
            'course_id' => $course['id'],
            'slug' => 'admin-crud-chapter-1',
            'number' => 1,
            'title' => 'First chapter',
            'status' => 'available',
        ])->assertCreated()->json('chapter');

        $this->putJson('/api/admin/chapters/'.$chapter['id'], [
            'title' => 'Updated chapter',
            'description' => 'Updated description',
        ])->assertOk()->assertJsonPath('chapter.title', 'Updated chapter');

        $material = $this->postJson('/api/admin/materials', [
            'chapter_id' => $chapter['id'],
            'title' => 'Lesson notes',
            'content' => ['sections' => [['heading' => 'Intro', 'body' => 'Persisted content']]],
        ])->assertCreated()->json('material');

        $this->putJson('/api/admin/materials/'.$material['id'], [
            'title' => 'Updated lesson notes',
            'content' => ['sections' => [['heading' => 'Updated', 'body' => 'Saved']]],
        ])->assertOk()->assertJsonPath('material.title', 'Updated lesson notes');

        $testId = DB::table('tests')->insertGetId([
            'course_id' => $course['id'],
            'chapter_id' => $chapter['id'],
            'type' => 'chapter',
            'title' => 'Chapter test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $question = $this->postJson('/api/admin/questions', [
            'test_id' => $testId,
            'question' => 'Which answer is correct?',
            'options' => ['First', 'Second', 'Third'],
            'correct_index' => 1,
        ])->assertCreated()->json('question');

        $this->putJson('/api/admin/questions/'.$question['id'], [
            'question' => 'Updated question?',
            'options' => ['One', 'Two'],
            'correct_index' => 0,
        ])->assertOk()->assertJsonPath('question.question', 'Updated question?');

        $examId = DB::table('exams')->insertGetId([
            'course_id' => $course['id'],
            'type' => 'midterm',
            'title' => 'Midterm examination',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $examQuestion = $this->postJson('/api/admin/exam-questions', [
            'exam_id' => $examId,
            'question' => 'Midterm question?',
            'options' => ['Yes', 'No'],
            'correct_index' => 0,
        ])->assertCreated()->json('question');

        $this->putJson('/api/admin/exam-questions/'.$examQuestion['id'], [
            'question' => 'Updated midterm question?',
            'options' => ['Correct', 'Incorrect'],
            'correct_index' => 0,
        ])->assertOk()->assertJsonPath('question.question', 'Updated midterm question?');

        $this->getJson('/api/admin/questions?test_id='.$testId)
            ->assertOk()
            ->assertJsonPath('questions.0.options.0.is_correct', true);
        $this->getJson('/api/admin/exam-questions?exam_id='.$examId)
            ->assertOk()
            ->assertJsonPath('questions.0.correct_index', 0);

        $this->assertDatabaseHas('courses', ['id' => $course['id'], 'code' => 'CS-900']);
        $this->assertDatabaseHas('chapters', ['id' => $chapter['id'], 'title' => 'Updated chapter']);
        $this->assertDatabaseHas('learning_materials', ['id' => $material['id'], 'title' => 'Updated lesson notes']);
        $this->assertDatabaseHas('questions', ['id' => $question['id'], 'question' => 'Updated question?']);
        $this->assertDatabaseHas('question_options', ['question_id' => $question['id'], 'option' => 'One', 'is_correct' => true]);
        $this->assertDatabaseHas('exam_questions', ['id' => $examQuestion['id'], 'question' => 'Updated midterm question?']);

        $finalId = DB::table('exams')->insertGetId([
            'course_id' => $course['id'],
            'type' => 'final',
            'title' => 'Final examination',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finalQuestion = $this->postJson('/api/admin/exam-questions', [
            'exam_id' => $finalId,
            'question' => 'Final question?',
            'options' => ['Answer A', 'Answer B'],
            'correct_index' => 1,
        ])->assertCreated()->json('question');

        $this->deleteJson('/api/admin/questions/'.$question['id'])->assertOk();
        $this->deleteJson('/api/admin/exam-questions/'.$examQuestion['id'])->assertOk();
        $this->deleteJson('/api/admin/exam-questions/'.$finalQuestion['id'])->assertOk();
        $this->deleteJson('/api/admin/materials/'.$material['id'])->assertOk();
        $this->deleteJson('/api/admin/courses/'.$course['id'])->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $course['id']]);
        $this->assertDatabaseMissing('chapters', ['id' => $chapter['id']]);
        $this->assertDatabaseMissing('learning_materials', ['id' => $material['id']]);
        $this->assertDatabaseMissing('questions', ['id' => $question['id']]);
        $this->assertDatabaseMissing('exam_questions', ['id' => $examQuestion['id']]);
        $this->assertDatabaseMissing('exam_questions', ['id' => $finalQuestion['id']]);
    }
}
