<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group([
                base_path('routes/auth/auth.php'),
                base_path('routes/teacher/teacher.php'),
                base_path('routes/student/student.php'),
            ]);
            // Route::middleware('web')
            //     ->group((base_path('routes/admin/admin.php')));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isTeacher' => App\Http\Middleware\IsTeacher::class,
            'isStudent' => App\Http\Middleware\IsStudent::class,
            'teacherOrStudent' => App\Http\Middleware\TeacherOrStudent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
