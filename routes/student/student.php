<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('student')->middleware('isStudent')->group(function () {

    Volt::route('/', 'pages.student.panel.index')
        ->name('student.index');

    Volt::route('/discipline', 'pages.student.discipline.index')
        ->name('student.discipline');

    Volt::route('/discipline/{discipline_id}', 'pages.student.discipline.show')
        ->name('student.lessons');

    // Load streaming lesson
    Route::get("/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])->name('media.stream');
});

