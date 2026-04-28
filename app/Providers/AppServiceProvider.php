<?php

namespace App\Providers;

use App\Models\Teacher\Teacher;
use App\Observers\TeacherObserver;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Middleware\IsTeacher;
use App\Http\Middleware\IsStudent;
use App\Http\Middleware\TeacherOrStudent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            IsTeacher::class,
            IsStudent::class,
            TeacherOrStudent::class
        ]);
        Teacher::observe(TeacherObserver::class);
    }
}
