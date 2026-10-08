<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('aluno')->middleware('isStudent')->group(function () {

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


    Route::livewire('/perfil', 'pages::student.profile.index')
        ->name('student.profile');
});

// Streaming de aulas: compartilhado entre professor e aluno.
// A autorização por vínculo de disciplina é feita no controller.
Route::get('/aluno/lesson/{lesson_id}', [LessonUploadController::class, 'stream'])
    ->middleware('teacherOrStudent')
    ->name('media.stream');
