<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use App\Models\Discipline;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('app/teacher', 'app.teacher.home.index')
    ->middleware(['auth', 'verified'])
    ->name('teacher.home');

Route::view('app/teacher/content', 'app.teacher.content.index')
->middleware(['auth', 'verified'])
->name('teacher.content');

Route::view('app/teacher/content/create', 'app.teacher.content.create')
->middleware(['auth', 'verified'])
->name('teacher.content.create');

Volt::route("app/teacher/content/{id}", 'teacher.content.content-show')
->middleware((['auth', 'verified']))
->name('teacher.content.show');

Route::post('app/teacher/content/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])->name('media.upload');
Route::get("app/teacher/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
->name( 'media.stream');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
