<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DesignationPageController;
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
    // --- Routes Classiques (Gestion Administrative) ---
    Route::get('/designations', [DesignationController::class, 'index'])->name('designations.index');
    Route::get('/designations/create', [DesignationController::class, 'create'])->name('designations.create');
    Route::post('/designations', [DesignationController::class, 'store'])->name('designations.store');
    Route::put('/designations/{designation}', [DesignationController::class, 'update'])->name('designations.update');
    Route::delete('/designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');

    // --- Routes de la Grille (Affichage Spécifique) ---
    // Notez le changement des noms pour éviter les conflits
    // 1. La route qui affiche la page Index (appelée une seule fois au chargement)
    Route::get('/designations-list', function () {
        return Inertia::render('Designations/IndexApi', [
            'initialDepartments' => \App\Models\Departement::all(), // On passe les depts pour les filtres
        ]);
    })->name('designations.index.page');

// 2. La route API que votre service api.js va interroger (appelée à chaque filtre/pagination)
    Route::get('/api/designations', [DesignationPageController::class, 'index'])->name('designations.api.index');
    Route::get('/api/designations/create', [DesignationPageController::class, 'create'])->name('designations.api.create');
    Route::get('/api/designations/{designation}', [DesignationPageController::class, 'show'])->name('designations.api.show');
    Route::get('/api/designations/{designation}/edit', [DesignationPageController::class, 'edit'])->name('designations.api.edit');

    // Récupérer les labos d'un sous-département
    Route::get('/api/sous-departements/{sous_departement}/labs', [DesignationPageController::class, 'getLabsBySousDept']);

    // Récupérer la config complète d'un labo
    Route::get('/api/labs/{lab}/config', [DesignationPageController::class, 'getLabConfig']);
    Route::get('/api/labs/{lab}/membres', [DesignationPageController::class, 'getLabMembers']);

    // /api/departments/${deptId}/sous-departments`),
    Route::get('/api/departments/{department}/sous-departments', function (\App\Models\Departement $department) {
        return $department->sousDepartements()->select('id', 'nom')->get();
    })->name('api.departments.sous-departments');

    Route::post('/designationsapi', [DesignationPageController::class, 'store'])->name('designations.api.store');

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
