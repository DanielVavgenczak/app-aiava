<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Middleware\IsTeacher;
use App\Http\Middleware\IsStudent;

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
       // LivewireDoctor::initCustomAsset();
        Livewire::addPersistentMiddleware([
            IsTeacher::class,
            IsStudent::class
        ]);
    }
}
