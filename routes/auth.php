<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::livewire('teacher/register', 'pages::auth.register')
        ->middleware('throttle:10,1')
        ->name('teacher.register');

    Route::livewire('teacher/login', 'pages::auth.login')
        ->middleware(['guest:teacher,student', 'throttle:10,1'])
        ->name('teacher.login');

    // Login student
    Route::livewire('student/login', 'pages::auth.login')
        ->middleware(['guest:teacher,student', 'throttle:10,1'])
        ->name('student.login');


    Route::livewire('forgot-password', 'pages::auth.forgot-password')
        ->middleware('throttle:10,1')
        ->name('password.request');

    Route::livewire('reset-password/{token}', 'pages::auth.reset-password')
        ->middleware('throttle:10,1')
        ->name('password.reset');
});



Route::middleware('auth')->group(function () {
    Route::livewire('verify-email', 'pages::auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::livewire('confirm-password', 'pages::auth.confirm-password')
        ->name('password.confirm');
});
