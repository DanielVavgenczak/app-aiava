<?php


use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::auth.login');

require __DIR__ . '/auth.php';
