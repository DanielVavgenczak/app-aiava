<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Volt::route('/register', 'pages.auth.register')
    ->name('register');

Volt::route('/login', 'pages.auth.login')
    ->middleware('guest:teacher,student')
    ->name('login');
