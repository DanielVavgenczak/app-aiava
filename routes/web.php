<?php

use App\Http\Controllers\Teacher\LessonUploadController;
use App\Livewire\Teacher\Classes\Index;
use App\Livewire\Teacher\Classes\Show;
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

Volt::route("app/teacher/discipline/{id}", componentName: "pages.teacher.discipline.show")
->middleware((['auth', 'verified']))
->name(name: 'teacher.discipline.show');

Route::view('app/teacher/messages', 'app.teacher.messages.index')
->middleware(['auth', 'verified'])
->name('teacher.messages');

Route::get('app/teacher/classes', Index::class)
->middleware(['auth', 'verified'])
->name('teacher.classes');

Route::get('app/teacher/classes/{classe}', Show::class)
->middleware(['auth', 'verified'])
->name('teacher.classes.show');

Route::view('app/teacher/forum', 'app.teacher.forum.index')
->middleware(['auth', 'verified'])
->name('teacher.forum');

Route::post('app/teacher/discipline/{module_id}/upload', [LessonUploadController::class, 'uploadVideo'])->name('media.upload');
Route::get("app/teacher/lesson/{lesson_id}", [LessonUploadController::class, 'stream'])
->name( 'media.stream');

Route::middleware('guest')->group(function () {
    Volt::route('app/student/login', 'pages.student.auth.login')
        ->name('student.login');
});

require __DIR__.'/auth.php';
