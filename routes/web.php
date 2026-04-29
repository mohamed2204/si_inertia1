<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\LoginController;


Route::get('/auth/login', [LoginController::class, 'show'])->name('login')->middleware('guest');
Route::post('/auth/login', [LoginController::class, 'authenticate'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::get('pages/crud', function () {
    return Inertia::render('Crud/Crud');
}); 

// --- ROUTES PROTÉGÉES (Middleware auth) ---
Route::middleware(['auth'])->group(function () {
    
    // Ta route Home (Dashboard) est maintenant protégée
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('home');

    // Route::get('pages/crud', function () {
    //    // return Inertia::render('Crud');
    //     return Inertia::render('Crud/Crud');
    // })->name('crud');

    // Ajoute ici toutes tes autres pages Sakai (Profile, Settings, etc.)
    Route::get('/uikit/formlayout', function () {
        return Inertia::render('Uikit/FormLayout');
    });
});


// // Route pour afficher la page de login
// Route::get('/auth/login', function () {
//     //return Inertia::render('Auth/Login'); // Chemin relatif au dossier resources/js/Pages
//     return Inertia::render('Auth/Login/Page');
// })->name('login');

// Route::get('/', function () {
//     return Inertia::render('Dashboard');
// })->name('home');;

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
