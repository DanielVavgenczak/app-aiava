<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix("forum")->middleware(['teacherOrStudent'])->group(function () {

    // Exemplo de rotas dentro do grupo
    Volt::route('/', 'pages.forum.index')
        ->name('forum.index');
    Volt::route('/show/{discipline_id}', 'pages.forum.show')
        ->name('forum.show');
});
