<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use App\Models\Discipline;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('app/teacher', 'app.teacher.home.index')
    ->middleware(['auth', 'verified'])
    ->name('teacher.home');

Route::view('app/teacher/profile', 'app.teacher.profile.index')
->middleware(['auth', 'verified'])
->name('teacher.profile');

Route::view('app/teacher/discipline', 'app.teacher.discipline.index')
->middleware(['auth', 'verified'])
->name('teacher.discipline');

Route::view('app/teacher/discipline/create', 'app.teacher.discipline.create')
->middleware(['auth', 'verified'])
->name('teacher.content.create');

Volt::route("app/teacher/discipline/{id}", 'teacher.discipline.show')
->middleware((['auth', 'verified']))
->name('teacher.discipline.show');

Route::post('app/teacher/discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])->name('media.upload');
Route::get("app/teacher/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
->name( 'media.stream');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
