<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/register', 'pages::auth.register')
    ->middleware('throttle:10,1')
    ->name('register');

Route::livewire('/login', 'pages::auth.login')
    ->middleware(['guest:teacher,student', 'throttle:10,1'])
    ->name('login');
