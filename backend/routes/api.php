<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [ApiController::class, 'register']);
Route::post('/auth/login', [ApiController::class, 'login']);
Route::get('/courses', [ApiController::class, 'courses']);
Route::get('/courses/{course}', [ApiController::class, 'course']);
Route::get('/courses/{course}/chapters', [ApiController::class, 'chapters']);
Route::get('/chapters/{chapter}/material', [ApiController::class, 'material']);
Route::get('/courses/{course}/tests', [ApiController::class, 'tests']);
Route::get('/tests/{test}/questions', [ApiController::class, 'testQuestions']);
Route::get('/courses/{course}/exams', [ApiController::class, 'exams']);
Route::get('/exams/{exam}/questions', [ApiController::class, 'examQuestions']);
Route::get('/assessments/{course}/{type}/{chapter?}', [ApiController::class, 'assessment']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [ApiController::class, 'logout']);
    Route::get('/auth/me', [ApiController::class, 'me']);
    Route::get('/profile', [ApiController::class, 'profile']);
    Route::put('/profile', [ApiController::class, 'profile']);
    Route::get('/results/{result}', [ApiController::class, 'result']);
    Route::get('/progress', [ApiController::class, 'progress']);
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
    Route::post('/chapters/{chapter}/completion', [ApiController::class, 'complete']);
    Route::post('/tests/submit', [ApiController::class, 'submitTest']);
    Route::post('/exams/submit', [ApiController::class, 'submitExam']);
    Route::post('/assessments/submit', [ApiController::class, 'submitAssessment']);
    Route::post('/results', [ApiController::class, 'saveResult']);
    Route::post('/admin/courses', [ApiController::class, 'adminStoreCourse']);
    Route::get('/admin/courses', [ApiController::class, 'adminCourses']);
    Route::put('/admin/courses/{course}', [ApiController::class, 'adminUpdateCourse']);
    Route::delete('/admin/courses/{course}', [ApiController::class, 'adminDeleteCourse']);
    Route::post('/admin/chapters', [ApiController::class, 'adminStoreChapter']);
    Route::get('/admin/chapters', [ApiController::class, 'adminChapters']);
    Route::put('/admin/chapters/{chapter}', [ApiController::class, 'adminUpdateChapter']);
    Route::delete('/admin/chapters/{chapter}', [ApiController::class, 'adminDeleteChapter']);
    Route::post('/admin/materials', [ApiController::class, 'adminStoreMaterial']);
    Route::get('/admin/materials', [ApiController::class, 'adminMaterials']);
    Route::put('/admin/materials/{material}', [ApiController::class, 'adminUpdateMaterial']);
    Route::delete('/admin/materials/{material}', [ApiController::class, 'adminDeleteMaterial']);
    Route::post('/admin/tests', [ApiController::class, 'adminStoreTest']);
    Route::get('/admin/tests', [ApiController::class, 'adminTests']);
    Route::get('/admin/questions', [ApiController::class, 'adminQuestions']);
    Route::post('/admin/questions', [ApiController::class, 'adminStoreQuestion']);
    Route::delete('/admin/questions/{question}', [ApiController::class, 'adminDeleteQuestion']);
    Route::put('/admin/questions/{question}', [ApiController::class, 'adminUpdateQuestion']);
    Route::post('/admin/exam-questions', [ApiController::class, 'adminStoreExamQuestion']);
    Route::delete('/admin/exam-questions/{question}', [ApiController::class, 'adminDeleteExamQuestion']);
    Route::put('/admin/exam-questions/{question}', [ApiController::class, 'adminUpdateExamQuestion']);
    Route::get('/admin/exams', [ApiController::class, 'adminExams']);
    Route::get('/admin/exam-questions', [ApiController::class, 'adminExamQuestions']);
});
