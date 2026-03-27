<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use App\Livewire\Teacher\Classes\Index;
use App\Livewire\Teacher\Classes\Show;
use Illuminate\Support\Facades\Route;


Route::prefix("professor")->middleware('isTeacher')->group(function () {

    Route::livewire('/', 'pages::teacher.home.index')
        ->name('teacher.index');

    Route::livewire('/perfil', 'pages::teacher.profile.index')
        ->name('teacher.profile');

    Route::livewire('/disciplina', 'pages::teacher.discipline.index')
        ->name('teacher.discipline');

    Route::livewire("disciplina/{id}", "pages::teacher.discipline.show")
        ->name(name: 'teacher.discipline.show');

    Route::livewire('/mensagens', 'pages::teacher.messages.index')
        ->name('teacher.messages');

    Route::livewire('/turmas', 'pages::teacher.classes.index')
        ->name('teacher.classes');

    Route::livewire('/turmas/{classe}',  'pages::teacher.classes.show')
        ->name('teacher.classes.show');
    Route::livewire('/forum', 'pages::teacher.forum.index')
        ->name('teacher.forum');

    Route::livewire('/forum/{id}', 'pages::teacher.forum.show')
        ->name('teacher.forum.show');

    Route::livewire('/forum/replies/{id}', 'pages::teacher.forum.replies')
        ->name('teacher.forum.replies');

    // upload and transmition
    // TODO: verificar a rota que faz o upload se é necessario traduzir
    Route::post('discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])
        ->name('teacher.media.upload');

    Route::get("lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
        ->name('teacher.media.stream');
});
