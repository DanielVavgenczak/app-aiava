<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('aluno')->middleware('isStudent')->group(function () {

    Volt::route('/', 'pages.student.panel.index')
        ->name('student.index');

    Volt::route('/disciplina', 'pages.student.discipline.index')
        ->name('student.discipline');

    Volt::route('/disciplina/{discipline_id}', 'pages.student.discipline.show')
        ->name('student.lessons');

    Volt::route('/forum', 'pages.student.forum.index')
        ->name('student.forum.index');

    Volt::route('/forum/{id}', 'pages.student.forum.show')
        ->name('student.forum.show');

    Volt::route('/forum/topicos/{id}', 'pages.student.forum.topic.index')
        ->name('student.forum.topic.index');


    Volt::route('/perfil', 'pages.student.profile.index')
        ->name('student.profile');
    // Load streaming lesson
    Route::get("/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])->name('media.stream');
});
