<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Route::middleware('web')->group(function () {
//     Volt::route('teacher/register', 'pages.teacher.auth.register')
//         ->name('teacher.register');

//     Volt::route('teacher/login', 'pages.teacher.auth.login')
//         ->name('teacher.login');

//     // Login student
//     Volt::route('student/login', 'pages.student.auth.login')
//         ->name('student.login');


//     Volt::route('forgot-password', 'pages.auth.forgot-password')
//         ->name('password.request');

//     Volt::route('reset-password/{token}', 'pages.auth.reset-password')
//         ->name('password.reset');


// });



Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');
});
