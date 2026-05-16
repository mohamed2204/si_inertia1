<?php

use App\Http\Controllers\Admin\GroupPivotController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignationPageController;
use App\Http\Controllers\Admin\GroupAssignmentController;
use App\Http\Controllers\Admin\GroupPivotController as AdminGroupPivotController;
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

    Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])->name('admin.assignments.index');
    Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('admin.assignments.update');

    // Version explicite (recommandée pour mieux contrôler vos URLs)
    // --- Routes Classiques (Gestion Administrative) ---

    // --- Routes de la Grille (Affichage Spécifique) ---
    // Notez le changement des noms pour éviter les conflits
    // 1. La route qui affiche la page Index (appelée une seule fois au chargement)
    Route::get('/designations-list', function () {
        return Inertia::render('Designations/IndexApi', [
            'initialDepartments' => \App\Models\Departement::all(), // On passe les depts pour les filtres
        ]);
    })->name('designations.index.page');

// 2. La route API que votre service api.js va interroger (appelée à chaque filtre/pagination)
    Route::get('/designations', [DesignationPageController::class, 'index'])->name('designations.index');
    Route::get('/designations/create', [DesignationPageController::class, 'create'])->name('designations.create');
    Route::get('/designations/{designation}', [DesignationPageController::class, 'show'])->name('designations.show');
    Route::get('/designations/{designation}/edit', [DesignationPageController::class, 'edit'])->name('designations.edit');
    Route::post('/api/designations', [DesignationPageController::class, 'store'])->name('designations.api.store');
    Route::delete('/api/designations/{designation}', [DesignationPageController::class, 'destroy'])->name('designations.api.destroy');

    // Récupérer les labos d'un sous-département
    Route::get('/api/sous-departements/{sous_departement}/labs', [DesignationPageController::class, 'getLabsBySousDept']);

    // Récupérer la config complète d'un labo
    Route::get('/api/labs/{lab}/config', [DesignationPageController::class, 'getLabConfig']);
    Route::get('/api/labs/{lab}/membres', [DesignationPageController::class, 'getLabMembers']);

    // /api/departments/${deptId}/sous-departments`),
    Route::get('/api/departments/{department}/sous-departments', function (\App\Models\Departement $department) {
        return $department->sousDepartements()->select('id', 'nom')->get();
    })->name('api.departments.sous-departments');

    //Route::post('/designationsapi', [DesignationPageController::class, 'store'])->name('designations.api.store');

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



    // 2. Les routes d'administration sécurisées (Uniquement pour la Direction)
    Route::middleware(['admin.group'])->group(function () {
        // La page de la matrice pivot
        Route::get('/admin/permissions-pivot', [GroupPivotController::class, 'index'])->name('admin.permissions.pivot.index');
        Route::post('/admin/permissions-pivot/update', [GroupPivotController::class, 'updatePivot'])->name('admin.permissions.pivot.update');
        
        // La page d'affectation des utilisateurs aux groupes
        Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])->name('admin.assignments.index');
        Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('admin.assignments.update');
    });

    // Ajoute ici toutes tes autres pages Sakai (Profile, Settings, etc.)
    Route::get('/uikit/formlayout', function () {
        return Inertia::render('Uikit/FormLayout');
    });
});


// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/admin/permissions-pivot', [AdminGroupPivotController::class, 'index'])->name('admin.permissions.pivot.index');
//     Route::post('/admin/permissions-pivot/update', [AdminGroupPivotController::class, 'updatePivot'])->name('admin.permissions.pivot.update');
// });

