<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8']]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'student']);
        $user->profile()->create(['full_name' => $user->name, 'learning_goal' => '']);

        return response()->json(['user' => $this->userPayload($user), 'token' => $user->createToken('code-quest')->plainTextToken], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        return response()->json(['user' => $this->userPayload($user), 'token' => $user->createToken('code-quest')->plainTextToken]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function profile(Request $request): JsonResponse
    {
        $data = $request->validate(['full_name' => ['sometimes', 'string', 'max:255'], 'learning_goal' => ['sometimes', 'string', 'max:255']]);
        $profile = $request->user()->profile()->updateOrCreate([], $data);

        return response()->json(['profile' => $profile]);
    }

    public function result(Request $request, int $result): JsonResponse
    {
        $row = DB::table('test_results')->where(['id' => $result, 'user_id' => $request->user()->id])->first();
        abort_unless($row, 404);
        $row->review_data = $row->review_data ? json_decode($row->review_data, true) : null;

        return response()->json(['result' => $row]);
    }

    public function courses(): JsonResponse
    {
        return response()->json(['courses' => DB::table('courses')->get()->map(function ($course) {
            $course->chapters = DB::table('chapters')->where('course_id', $course->id)->orderBy('number')->get();

            return $course;
        })]);
    }

    public function course(int $course): JsonResponse
    {
        $row = DB::table('courses')->where('id', $course)->first();
        abort_unless($row, 404);
        $row->chapters = DB::table('chapters')->where('course_id', $course)->orderBy('number')->get();

        return response()->json(['course' => $row]);
    }

    public function chapters(int $course): JsonResponse
    {
        return response()->json(['chapters' => DB::table('chapters')->where('course_id', $course)->orderBy('number')->get()]);
    }

    public function material(string $chapter): JsonResponse
    {
        $row = DB::table('chapters')->where('slug', $chapter)->first();
        abort_unless($row, 404);
        $materials = DB::table('learning_materials')->where('chapter_id', $row->id)->get()->map(function ($material) {
            $material->content = json_decode($material->content, true);

            return $material;
        });

        return response()->json(['materials' => $materials]);
    }

    public function complete(Request $request, int $chapter): JsonResponse
    {
        $data = $request->validate(['course_id' => ['required', 'integer', 'exists:courses,id']]);
        abort_unless(DB::table('chapters')->where(['id' => $chapter, 'course_id' => $data['course_id']])->exists(), 404);
        $completion = DB::table('chapter_completions')->where(['user_id' => $request->user()->id, 'chapter_id' => $chapter, 'course_id' => $data['course_id']])->first();
        if ($completion) {
            DB::table('chapter_completions')->where('id', $completion->id)->delete();
        } else {
            DB::table('chapter_completions')->insert(['user_id' => $request->user()->id, 'course_id' => $data['course_id'], 'chapter_id' => $chapter, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $this->progress($request);
    }

    public function tests(int $course): JsonResponse
    {
        return response()->json(['tests' => DB::table('tests')->where('course_id', $course)->get()]);
    }

    public function testQuestions(int $test): JsonResponse
    {
        return response()->json(['questions' => DB::table('questions')->where('test_id', $test)->orderBy('sort_order')->get()->map(function ($question) {
            $question->options = DB::table('question_options')->where('question_id', $question->id)->orderBy('sort_order')->get(['id', 'option', 'sort_order']);

            return $question;
        })]);
    }

    public function exams(int $course): JsonResponse
    {
        return response()->json(['exams' => DB::table('exams')->where('course_id', $course)->get()]);
    }

    public function examQuestions(int $exam): JsonResponse
    {
        return response()->json(['questions' => DB::table('exam_questions')->where('exam_id', $exam)->orderBy('sort_order')->get()->map(function ($question) {
            $question->options = json_decode($question->options, true);
            unset($question->correct_index);

            return $question;
        })]);
    }

    public function assessment(string $course, string $type, ?string $chapter = null): JsonResponse
    {
        $courseRow = DB::table('courses')->where('slug', $course)->first();
        abort_unless($courseRow, 404);
        if ($type === 'chapter') {
            $chapterRow = DB::table('chapters')->where(['course_id' => $courseRow->id, 'slug' => $chapter])->first();
            abort_unless($chapterRow, 404);
            $test = DB::table('tests')->where(['course_id' => $courseRow->id, 'chapter_id' => $chapterRow->id, 'type' => 'chapter'])->first();
            abort_unless($test, 404);

            return response()->json(['assessment' => ['id' => $test->id, 'type' => 'chapter', 'title' => $test->title, 'questions' => $this->testQuestionPayload($test->id)]]);
        }
        $exam = DB::table('exams')->where(['course_id' => $courseRow->id, 'type' => $type])->first();
        abort_unless($exam, 404);

        return response()->json(['assessment' => ['id' => $exam->id, 'type' => $type, 'title' => $exam->title, 'questions' => DB::table('exam_questions')->where('exam_id', $exam->id)->orderBy('sort_order')->get()->map(fn ($question) => ['id' => $question->id, 'question' => $question->question, 'options' => json_decode($question->options, true)])]]);
    }

    private function testQuestionPayload(int $testId): array
    {
        return DB::table('questions')->where('test_id', $testId)->orderBy('sort_order')->get()->map(fn ($question) => ['id' => $question->id, 'question' => $question->question, 'options' => DB::table('question_options')->where('question_id', $question->id)->orderBy('sort_order')->pluck('option')->values()])->all();
    }

    private function reviewTestQuestionPayload(int $testId): array
    {
        return DB::table('questions')->where('test_id', $testId)->orderBy('sort_order')->get()->map(fn ($question) => ['id' => $question->id, 'question' => $question->question, 'options' => DB::table('question_options')->where('question_id', $question->id)->orderBy('sort_order')->pluck('option')->values(), 'correctIndex' => DB::table('question_options')->where(['question_id' => $question->id, 'is_correct' => true])->value('sort_order')])->all();
    }

    public function submitAssessment(Request $request): JsonResponse
    {
        $data = $request->validate(['course' => ['required', 'string'], 'type' => ['required', 'in:chapter,midterm,final'], 'chapter' => ['nullable', 'string'], 'answers' => ['required', 'array'], 'time_taken' => ['required', 'string']]);
        $course = DB::table('courses')->where('slug', $data['course'])->first();
        abort_unless($course, 404);
        $type = $data['type'];
        if ($type === 'chapter') {
            $chapter = DB::table('chapters')->where(['course_id' => $course->id, 'slug' => $data['chapter']])->first();
            abort_unless($chapter, 404);
            $assessment = DB::table('tests')->where(['course_id' => $course->id, 'chapter_id' => $chapter->id, 'type' => 'chapter'])->first();
            $questions = DB::table('questions')->where('test_id', $assessment->id)->orderBy('sort_order')->get();
            $correct = 0;
            foreach ($questions as $index => $question) {
                $selected = $data['answers'][$index] ?? null;
                $isCorrect = DB::table('question_options')->where(['question_id' => $question->id, 'sort_order' => $selected, 'is_correct' => true])->exists();
                $correct += (int) $isCorrect;
            } $attemptId = DB::table('test_attempts')->insertGetId(['user_id' => $request->user()->id, 'test_id' => $assessment->id, 'score' => round($correct / max(1, $questions->count()) * 100), 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken'], 'created_at' => now(), 'updated_at' => now()]);
            foreach ($questions as $index => $question) {
                $selected = $data['answers'][$index] ?? null;
                $option = DB::table('question_options')->where(['question_id' => $question->id, 'sort_order' => $selected])->first();
                DB::table('test_answers')->insert(['test_attempt_id' => $attemptId, 'question_id' => $question->id, 'question_option_id' => $option?->id, 'is_correct' => (bool) ($option?->is_correct), 'created_at' => now(), 'updated_at' => now()]);
            } $result = ['course_id' => $course->slug, 'chapter_id' => $chapter->slug, 'score' => round($correct / max(1, $questions->count()) * 100), 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken']];
        } else {
            $assessment = DB::table('exams')->where(['course_id' => $course->id, 'type' => $type])->first();
            $questions = DB::table('exam_questions')->where('exam_id', $assessment->id)->orderBy('sort_order')->get();
            $correct = 0;
            foreach ($questions as $index => $question) {
                $correct += (int) ((int) ($data['answers'][$index] ?? -1) === (int) $question->correct_index);
            } $attemptId = DB::table('exam_attempts')->insertGetId(['user_id' => $request->user()->id, 'exam_id' => $assessment->id, 'score' => round($correct / max(1, $questions->count()) * 100), 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken'], 'created_at' => now(), 'updated_at' => now()]);
            foreach ($questions as $index => $question) {
                DB::table('exam_answers')->insert(['exam_attempt_id' => $attemptId, 'exam_question_id' => $question->id, 'selected_index' => $data['answers'][$index] ?? null, 'is_correct' => (int) ($data['answers'][$index] ?? -1) === (int) $question->correct_index, 'created_at' => now(), 'updated_at' => now()]);
            } $result = ['course_id' => $course->slug, 'chapter_id' => $course->slug.'-'.$type, 'score' => round($correct / max(1, $questions->count()) * 100), 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken']];
        }
        $reviewData = ['answers' => $data['answers'], 'questions' => $type === 'chapter' ? $this->reviewTestQuestionPayload($assessment->id) : $questions->map(fn ($question) => ['id' => $question->id, 'question' => $question->question, 'options' => json_decode($question->options, true), 'correctIndex' => $question->correct_index])->values()->all()];
        $resultId = DB::table('test_results')->insertGetId($result + ['user_id' => $request->user()->id, 'assessment_type' => $type, 'attempt_id' => $attemptId, 'review_data' => json_encode($reviewData), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['result' => $result + ['id' => $resultId, 'attempt_id' => $attemptId, 'assessment_type' => $type, 'review_data' => $reviewData]], 201);
    }

    public function submitTest(Request $request): JsonResponse
    {
        $data = $request->validate(['test_id' => ['required', 'integer', 'exists:tests,id'], 'answers' => ['required', 'array'], 'time_taken' => ['required', 'string']]);
        $questions = DB::table('questions')->where('test_id', $data['test_id'])->orderBy('sort_order')->get();
        $correct = 0;
        foreach ($questions as $index => $question) {
            $selected = $data['answers'][$index] ?? null;
            $correct += (int) DB::table('question_options')->where(['question_id' => $question->id, 'id' => $selected, 'is_correct' => true])->exists();
        }
        $attempt = DB::table('test_attempts')->insertGetId(['user_id' => $request->user()->id, 'test_id' => $data['test_id'], 'score' => $questions->count() ? round($correct / $questions->count() * 100) : 0, 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken'], 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['attempt' => DB::table('test_attempts')->where('id', $attempt)->first()], 201);
    }

    public function submitExam(Request $request): JsonResponse
    {
        $data = $request->validate(['exam_id' => ['required', 'integer', 'exists:exams,id'], 'answers' => ['required', 'array'], 'time_taken' => ['required', 'string']]);
        $questions = DB::table('exam_questions')->where('exam_id', $data['exam_id'])->orderBy('sort_order')->get();
        $correct = 0;
        foreach ($questions as $index => $question) {
            $correct += (int) (($data['answers'][$index] ?? null) === $question->correct_index);
        }
        $id = DB::table('exam_attempts')->insertGetId(['user_id' => $request->user()->id, 'exam_id' => $data['exam_id'], 'score' => $questions->count() ? round($correct / $questions->count() * 100) : 0, 'correct_answers' => $correct, 'incorrect_answers' => $questions->count() - $correct, 'time_taken' => $data['time_taken'], 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['attempt' => DB::table('exam_attempts')->where('id', $id)->first()], 201);
    }

    public function saveResult(Request $request): JsonResponse
    {
        $data = $request->validate(['course_id' => ['required', 'string'], 'chapter_id' => ['required', 'string'], 'score' => ['required', 'integer', 'between:0,100'], 'correct_answers' => ['required', 'integer', 'min:0'], 'incorrect_answers' => ['required', 'integer', 'min:0'], 'time_taken' => ['required', 'string']]);
        $id = DB::table('test_results')->insertGetId($data + ['user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['result' => DB::table('test_results')->where('id', $id)->first()], 201);
    }

    public function progress(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $completions = DB::table('chapter_completions')
            ->join('chapters', function ($join) {
                $join->on('chapters.id', '=', 'chapter_completions.chapter_id')
                    ->on('chapters.course_id', '=', 'chapter_completions.course_id');
            })
            ->join('courses', 'courses.id', '=', 'chapters.course_id')
            ->where('chapter_completions.user_id', $userId)
            ->select('courses.slug as course_slug', 'chapters.number')
            ->get();
        $courseProgress = DB::table('courses')
            ->leftJoin('chapters', 'chapters.course_id', '=', 'courses.id')
            ->leftJoin('chapter_completions', function ($join) use ($userId) {
                $join->on('chapter_completions.chapter_id', '=', 'chapters.id')
                    ->on('chapter_completions.course_id', '=', 'courses.id')
                    ->where('chapter_completions.user_id', '=', $userId);
            })
            ->select('courses.slug as course_id', DB::raw('COUNT(DISTINCT chapters.id) as total_chapters'), DB::raw('COUNT(DISTINCT chapter_completions.id) as completed_chapters'))
            ->groupBy('courses.id', 'courses.slug')
            ->orderBy('courses.id')
            ->get()
            ->map(function ($course) {
                $course->progress = $course->total_chapters > 0
                    ? (int) round($course->completed_chapters / $course->total_chapters * 100)
                    : 0;

                return $course;
            });
        $results = DB::table('test_results')->where('user_id', $userId)->latest()->get();
        $exams = DB::table('exam_attempts')->where('user_id', $userId)->latest()->get();

        return response()->json(['completions' => $completions->map(fn ($row) => $row->course_slug.'-'.$row->number)->values(), 'course_progress' => $courseProgress, 'results' => $results, 'exams' => $exams]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user()), 'progress' => $this->progress($request)->getData(true)]);
    }

    public function adminStoreCourse(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['slug' => ['required', 'string', 'unique:courses,slug'], 'title' => ['required', 'string'], 'code' => ['nullable', 'string'], 'description' => ['nullable', 'string'], 'level' => ['nullable', 'string']]);
        $id = DB::table('courses')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        return response()->json(['course' => DB::table('courses')->find($id)], 201);
    }

    public function adminCourses(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        return response()->json(['courses' => DB::table('courses')->get()]);
    }

    public function adminChapters(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('chapters');
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        return response()->json(['chapters' => $query->orderBy('course_id')->orderBy('number')->get()]);
    }

    public function adminMaterials(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('learning_materials');
        if ($request->filled('chapter_id')) {
            $query->where('chapter_id', $request->integer('chapter_id'));
        }

        $materials = $query->latest()->get()->map(function ($material) {
            $material->content = json_decode($material->content, true);

            return $material;
        });

        return response()->json(['materials' => $materials]);
    }

    public function adminTests(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('tests');
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        return response()->json(['tests' => $query->latest()->get()]);
    }

    public function adminQuestions(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('questions');
        if ($request->filled('test_id')) {
            $query->where('test_id', $request->integer('test_id'));
        }

        return response()->json(['questions' => $query->orderBy('test_id')->orderBy('sort_order')->get()->map(function ($question) {
            $question->options = DB::table('question_options')->where('question_id', $question->id)->orderBy('sort_order')->get(['id', 'option', 'is_correct', 'sort_order'])->map(function ($option) {
                $option->is_correct = (bool) $option->is_correct;

                return $option;
            });

            return $question;
        })]);
    }

    public function adminExams(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('exams');
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        return response()->json(['exams' => $query->latest()->get()]);
    }

    public function adminExamQuestions(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $query = DB::table('exam_questions');
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->integer('exam_id'));
        }

        return response()->json(['questions' => $query->orderBy('exam_id')->orderBy('sort_order')->get()->map(function ($question) {
            $question->options = json_decode($question->options, true);

            return $question;
        })]);
    }

    public function adminUpdateCourse(Request $request, int $course): JsonResponse
    {
        $this->requireAdmin($request);
        abort_unless(DB::table('courses')->where('id', $course)->exists(), 404);
        $data = $request->validate(['slug' => ['sometimes', 'required', 'string', 'max:255', 'unique:courses,slug,'.$course], 'title' => ['sometimes', 'required', 'string', 'max:255'], 'code' => ['sometimes', 'nullable', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'level' => ['sometimes', 'nullable', 'string', 'max:255']]);
        DB::table('courses')->where('id', $course)->update($data + ['updated_at' => now()]);

        return response()->json(['course' => DB::table('courses')->find($course)]);
    }

    public function adminDeleteCourse(Request $request, int $course): JsonResponse
    {
        $this->requireAdmin($request);
        DB::table('courses')->where('id', $course)->delete();

        return response()->json(['message' => 'Course deleted']);
    }

    public function adminStoreChapter(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['course_id' => ['required', 'integer', 'exists:courses,id'], 'slug' => ['required', 'string'], 'number' => ['required', 'integer'], 'title' => ['required', 'string'], 'description' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'duration' => ['nullable', 'string']]);
        $id = DB::table('chapters')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        return response()->json(['chapter' => DB::table('chapters')->find($id)], 201);
    }

    public function adminUpdateChapter(Request $request, int $chapter): JsonResponse
    {
        $this->requireAdmin($request);
        abort_unless(DB::table('chapters')->where('id', $chapter)->exists(), 404);
        $data = $request->validate(['course_id' => ['sometimes', 'required', 'integer', 'exists:courses,id'], 'slug' => ['sometimes', 'required', 'string', 'max:255'], 'number' => ['sometimes', 'required', 'integer', 'min:1'], 'title' => ['sometimes', 'required', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'required', 'in:available,in-progress,completed,locked'], 'duration' => ['sometimes', 'nullable', 'string', 'max:255']]);
        DB::table('chapters')->where('id', $chapter)->update($data + ['updated_at' => now()]);

        return response()->json(['chapter' => DB::table('chapters')->find($chapter)]);
    }

    public function adminDeleteChapter(Request $request, int $chapter): JsonResponse
    {
        $this->requireAdmin($request);
        DB::table('chapters')->where('id', $chapter)->delete();

        return response()->json(['message' => 'Chapter deleted']);
    }

    public function adminStoreMaterial(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['chapter_id' => ['required', 'integer', 'exists:chapters,id'], 'title' => ['required', 'string'], 'content' => ['required', 'array']]);
        $data['content'] = json_encode($data['content'], JSON_THROW_ON_ERROR);
        $id = DB::table('learning_materials')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
        $material = DB::table('learning_materials')->find($id);
        $material->content = json_decode($material->content, true);

        return response()->json(['material' => $material], 201);
    }

    public function adminDeleteMaterial(Request $request, int $material): JsonResponse
    {
        $this->requireAdmin($request);
        DB::table('learning_materials')->where('id', $material)->delete();

        return response()->json(['message' => 'Material deleted']);
    }

    public function adminUpdateMaterial(Request $request, int $material): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['title' => ['sometimes', 'string'], 'content' => ['sometimes', 'array']]);
        if (array_key_exists('content', $data)) {
            $data['content'] = json_encode($data['content'], JSON_THROW_ON_ERROR);
        }
        DB::table('learning_materials')->where('id', $material)->update($data + ['updated_at' => now()]);
        $row = DB::table('learning_materials')->find($material);
        abort_unless($row, 404);
        $row->content = json_decode($row->content, true);

        return response()->json(['material' => $row]);
    }

    public function adminStoreTest(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['course_id' => ['required', 'integer', 'exists:courses,id'], 'chapter_id' => ['nullable', 'integer', 'exists:chapters,id'], 'type' => ['required', 'string'], 'title' => ['required', 'string']]);
        $id = DB::table('tests')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);

        return response()->json(['test' => DB::table('tests')->find($id)], 201);
    }

    public function adminStoreQuestion(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['test_id' => ['required', 'integer', 'exists:tests,id'], 'question' => ['required', 'string'], 'options' => ['required', 'array', 'min:2'], 'options.*' => ['required', 'string', 'max:1000'], 'correct_index' => ['required', 'integer', 'min:0']]);
        $this->validateCorrectIndex($data);
        $questionId = DB::transaction(function () use ($data) {
            $questionId = DB::table('questions')->insertGetId(['test_id' => $data['test_id'], 'question' => $data['question'], 'sort_order' => DB::table('questions')->where('test_id', $data['test_id'])->count(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['options'] as $index => $option) {
                DB::table('question_options')->insert(['question_id' => $questionId, 'option' => $option, 'sort_order' => $index, 'is_correct' => $index === $data['correct_index'], 'created_at' => now(), 'updated_at' => now()]);
            }

            return $questionId;
        });

        return response()->json(['question' => DB::table('questions')->find($questionId)], 201);
    }

    public function adminDeleteQuestion(Request $request, int $question): JsonResponse
    {
        $this->requireAdmin($request);
        DB::table('questions')->where('id', $question)->delete();

        return response()->json(['message' => 'Question deleted']);
    }

    public function adminUpdateQuestion(Request $request, int $question): JsonResponse
    {
        $this->requireAdmin($request);
        abort_unless(DB::table('questions')->where('id', $question)->exists(), 404);
        $data = $request->validate(['question' => ['required', 'string'], 'options' => ['required', 'array', 'min:2'], 'options.*' => ['required', 'string', 'max:1000'], 'correct_index' => ['required', 'integer', 'min:0']]);
        $this->validateCorrectIndex($data);
        DB::transaction(function () use ($question, $data) {
            DB::table('questions')->where('id', $question)->update(['question' => $data['question'], 'updated_at' => now()]);
            DB::table('question_options')->where('question_id', $question)->delete();
            foreach ($data['options'] as $index => $option) {
                DB::table('question_options')->insert(['question_id' => $question, 'option' => $option, 'sort_order' => $index, 'is_correct' => $index === $data['correct_index'], 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return response()->json(['question' => DB::table('questions')->find($question)]);
    }

    public function adminStoreExamQuestion(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $data = $request->validate(['exam_id' => ['required', 'integer', 'exists:exams,id'], 'question' => ['required', 'string'], 'options' => ['required', 'array', 'min:2'], 'options.*' => ['required', 'string', 'max:1000'], 'correct_index' => ['required', 'integer', 'min:0']]);
        $this->validateCorrectIndex($data);
        $id = DB::table('exam_questions')->insertGetId(['exam_id' => $data['exam_id'], 'question' => $data['question'], 'options' => json_encode($data['options']), 'correct_index' => $data['correct_index'], 'sort_order' => DB::table('exam_questions')->where('exam_id', $data['exam_id'])->count(), 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['question' => DB::table('exam_questions')->find($id)], 201);
    }

    public function adminDeleteExamQuestion(Request $request, int $question): JsonResponse
    {
        $this->requireAdmin($request);
        DB::table('exam_questions')->where('id', $question)->delete();

        return response()->json(['message' => 'Exam question deleted']);
    }

    public function adminUpdateExamQuestion(Request $request, int $question): JsonResponse
    {
        $this->requireAdmin($request);
        abort_unless(DB::table('exam_questions')->where('id', $question)->exists(), 404);
        $data = $request->validate(['question' => ['required', 'string'], 'options' => ['required', 'array', 'min:2'], 'options.*' => ['required', 'string', 'max:1000'], 'correct_index' => ['required', 'integer', 'min:0']]);
        $this->validateCorrectIndex($data);
        DB::table('exam_questions')->where('id', $question)->update(['question' => $data['question'], 'options' => json_encode($data['options']), 'correct_index' => $data['correct_index'], 'updated_at' => now()]);

        return response()->json(['question' => DB::table('exam_questions')->find($question)]);
    }

    private function validateCorrectIndex(array $data): void
    {
        if ($data['correct_index'] >= count($data['options'])) {
            throw ValidationException::withMessages(['correct_index' => ['Choose a correct answer from the supplied options.']]);
        }
    }

    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Administrator access required.');
    }

    private function userPayload(User $user): array
    {
        $user->load('profile');

        return ['id' => (string) $user->id, 'email' => $user->email, 'name' => $user->name, 'role' => $user->role, 'profile' => $user->profile];
    }
}
