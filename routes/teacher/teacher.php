<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use App\Livewire\Teacher\Classes\Index;
use App\Livewire\Teacher\Classes\Show;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::prefix("professor")->middleware('isTeacher')->group(function () {

    Route::view('/', 'app.teacher.home.index')
        ->name('teacher.index');

    Volt::route('/perfil', 'pages.teacher.profile.index')
        ->name('teacher.profile');

    Route::view('/disciplina', 'app.teacher.discipline.index')
        ->name('teacher.discipline');

    Volt::route("disciplina/{id}", "pages.teacher.discipline.show")
        ->name(name: 'teacher.discipline.show');

    Route::view('/mensagens', 'app.teacher.messages.index')
        ->name('teacher.messages');

    Route::get('/turmas', Index::class)
        ->name('teacher.classes');

    Route::get('/turmas/{classe}', Show::class)
        ->name('teacher.classes.show');



    Route::view('/forum', 'app.teacher.forum.index')
        ->name('teacher.forum');

    Volt::route('/forum/{id}', 'pages.teacher.forum.show')
        ->name('teacher.forum.show');

    Volt::route('/forum/question/{id}', 'pages.teacher.forum.topic.questions')
        ->name('teacher.forum.question');

    // upload and transmition
    // TODO: verificar a rota que faz o upload se é necessario traduzir
    Route::post('discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])
        ->name('teacher.media.upload');

    Route::get("lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
        ->name('teacher.media.stream');
});
