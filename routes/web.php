<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('teacher')->check()) {
        return redirect()->route('teacher.discipline');
    }

    if (Auth::guard('student')->check()) {
        return redirect()->route('student.discipline');
    }

    return redirect()->route('login');
})->name('home');

require __DIR__ . '/auth.php';
