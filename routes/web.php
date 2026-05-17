<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::auth.login')->name('login');

require __DIR__ . '/auth.php';
require __DIR__ . '/teacher/teacher.php';
