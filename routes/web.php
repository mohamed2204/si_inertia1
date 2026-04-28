<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// Route pour afficher la page de login
Route::get('/auth/login', function () {
    //return Inertia::render('Auth/Login'); // Chemin relatif au dossier resources/js/Pages
    return Inertia::render('Auth/Login/Page');
})->name('login');

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');;

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
