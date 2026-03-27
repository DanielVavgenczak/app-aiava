<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/register', 'pages::auth.register')
    ->name('register');

Route::livewire('/login', 'pages::auth.login')
    ->middleware('guest:teacher,student')
    ->name('login');
