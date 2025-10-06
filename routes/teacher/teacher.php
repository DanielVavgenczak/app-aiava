<?php
use App\Http\Controllers\Teacher\LessonUploadController;
use App\Livewire\Teacher\Classes\Index;
use App\Livewire\Teacher\Classes\Show;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::prefix("teacher")->middleware('isTeacher')->group(function () {

    Route::view('/', 'app.teacher.home.index')
        ->name('teacher.index');

    Volt::route('/profile', 'pages.teacher.profile.index')
        ->name('teacher.profile');

    Route::view('/discipline', 'app.teacher.discipline.index')
        ->name('teacher.discipline');

    Volt::route("discipline/{id}", "pages.teacher.discipline.show")
        ->name(name: 'teacher.discipline.show');

    Route::view('/messages', 'app.teacher.messages.index')
        ->name('teacher.messages');

    Route::get('/classes', Index::class)
        ->name('teacher.classes');

    Route::get('/classes/{classe}', Show::class)
        ->name('teacher.classes.show');

    Route::view('/forum', 'app.teacher.forum.index')
        ->name('teacher.forum');

    // upload and transmition
    Route::post('discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])
        ->name('teacher.media.upload');

    Route::get( "lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
        ->name( 'teacher.media.stream');
});
// Route::get("teacher/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
// ->name( 'media.stream');









// Route::post('app/teacher/discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])->name('media.upload');
// Route::get("app/teacher/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
// ->name( 'media.stream');
