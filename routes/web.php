<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\LabRequisController;
use App\Models\Membre;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/auth/login', [LoginController::class, 'show'])->name('login')->middleware('guest');
Route::post('/auth/login', [LoginController::class, 'authenticate'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- ROUTES PROTÉGÉES (Middleware auth) ---
Route::middleware(['auth'])->group(function () {

    // Ta route Home (Dashboard) est maintenant protégée
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('home');

    // Version explicite (recommandée pour mieux contrôler vos URLs)
    Route::get('/designations', [DesignationController::class, 'index'])->name('designations.index');
    Route::post('/designations', [DesignationController::class, 'store'])->name('designations.store');

    // Si vous prévoyez de gérer l'édition et la suppression plus tard :
    Route::put('/designations/{designation}', [DesignationController::class, 'update'])->name('designations.update');
    Route::delete('/designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');

    Route::get('/api/membres-disponibles', function () {
        // On ne récupère que les colonnes nécessaires pour alléger le JSON
        return Membre::select('id', 'nom_membre as nom')->get();
    })->name('api.membres.index');

    // Route pour l'affichage de la page de configuration (Inertia)
    Route::get('/admin/laboratoires/requis', [LabRequisController::class, 'index'])
        ->name('lab-requis.index');


   
    // Route pour enregistrer les requis d'un laboratoire spécifique
    Route::post('/laboratoires/{laboratoire}/requis-sync', [LabRequisController::class, 'sync'])
        ->name('laboratoires.requis.sync');

// Route optionnelle pour récupérer la liste initiale deLabRequisControllerxios)
    Route::get('/api/requis-disponibles', [LabRequisController::class, 'list'])
        ->name('api.requis.list');

    Route::get('pages/crud', function () {
        return Inertia::render('Crud/Crud');
    });

    /*
       Alternative rapide :
       Route::resource('designations', DesignationController::class);
    */

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
