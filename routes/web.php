<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing-page')->name('landing-page');

require __DIR__ . '/auth.php';
require __DIR__ . '/teacher/teacher.php';
