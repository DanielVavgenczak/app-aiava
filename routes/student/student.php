<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('aluno')->middleware('isStudent')->group(function () {

    Route::livewire('/', 'pages::student.home.index')
        ->name('student.index');

    Route::livewire('/disciplina', 'pages::student.discipline.index')
        ->name('student.discipline');

    Route::livewire('/disciplina/{discipline_id}', 'pages::student.discipline.show')
        ->name('student.lessons');

    Route::livewire('/forum', 'pages::student.forum.index')
        ->name('student.forum.index');

    Route::livewire('/forum/{id}', 'pages::student.forum.show')
        ->name('student.forum.show');

    Route::livewire('/forum/replies/{id}', 'pages::student.forum.replie')
        ->name('student.forum.topic.replie');

    Route::livewire('/mensagens', 'pages::student.messages.index')
        ->name('student.messages');


    Route::livewire('/perfil', 'pages.student.profile.index')
        ->name('student.profile');
    // Load streaming lesson
    Route::get("/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])->name('media.stream');
});
