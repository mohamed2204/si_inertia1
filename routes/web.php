<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

// Public login route
// Route::middleware('guest:web')->group(function () {
//     Route::get('/', function () {
//         return Inertia::render('Auth/Login');
//     })->name('login');
// });

// // Protected routes after login
// Route::middleware('auth:web')->group(function () {
//     Route::get('/dashboard', function () {
//         return Inertia::render('Dashboard');
//     })->name('dashboard');
// });
