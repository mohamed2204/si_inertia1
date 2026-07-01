<?php

use App\Http\Controllers\Admin\GroupAssignmentController;
use App\Http\Controllers\Admin\GroupPivotController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SousDepartementUserController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignationPageController;
use App\Http\Controllers\ExcelController;
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

    // Page principale de la matrice terrain
    Route::get('/admin/permissions-terrain', [SousDepartementUserController::class, 'index'])->name('permissions.terrain.index');

    // Traitement Ajax du switch de permission
    Route::post('/admin/permissions-terrain/toggle', [SousDepartementUserController::class, 'togglePermission'])->name('permissions.terrain.toggle');

    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])->name('admin.assignments.index');
    Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('admin.assignments.update');

    // Version explicite (recommandée pour mieux contrôler vos URLs)
    // --- Routes Classiques (Gestion Administrative) ---

    // --- Routes de la Grille (Affichage Spécifique) ---
    // Notez le changement des noms pour éviter les conflits
    // 1. La route qui affiche la page Index (appelée une seule fois au chargement)
    // Route::get('/designations-list', function () {
    //     return Inertia::render('Designations/IndexApi', [
    //         'initialDepartments' => \App\Models\Departement::all(), // On passe les depts pour les filtres
    //     ]);
    // })->name('designations.index.page'); // pas controller php, c'est une page Inertia qui va faire les appels API ensuite

    // 2. La route API que votre service api.js va interroger (appelée à chaque filtre/pagination)
    Route::get('/designations-list', [DesignationPageController::class, 'index'])->name('designations.api.index');
    //Route::get('/api/designations', [DesignationPageController::class, 'listApi'])->name('designations.list'); // C'est cette route qui est appelée par le service JS pour récupérer les données filtrées
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



    Route::get('/telecharger-excel', [ExcelController::class, 'genererRapport'])
        ->name('excel.telecharger');


    // 2. Les routes d'administration sécurisées (Uniquement pour la Direction)
    Route::middleware(['admin.group'])->group(function () {
        // La page de la matrice pivot
        Route::get('/admin/permissions-pivot', [GroupPivotController::class, 'index'])->name('admin.permissions.pivot.index');
        Route::post('/admin/permissions-pivot/update', [GroupPivotController::class, 'updatePivot'])->name('admin.permissions.pivot.update');

        // La page d'affectation des utilisateurs aux groupes
        Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])->name('admin.assignments.index');
        Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('admin.assignments.update');

        // Page principale de gestion des modules
        //Route::get('/admin/permissions/modules', [ModulePermissionController::class, 'index'])->name('permissions.modules.index');
        Route::get('/admin/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        // Traitement du basculement d'une action
        Route::post('/admin/permissions/toggle', [PermissionController::class, 'toggleModulePermission'])->name('admin.permissions.modules.toggle');
        Route::post('/admin/permissions/modules', [PermissionController::class, 'updatePivotPermission'])->name('admin.permissions.pivot.update');
        // 🆕 La nouvelle route mise à jour et branchée sur le bon contrôleur :
        Route::post('/admin/permissions/terrain/toggle', [PermissionController::class, 'togglePermission'])->name('admin.permissions.terrain.toggle');
    });

    // Ajoute ici toutes tes autres pages Sakai (Profile, Settings, etc.)
    Route::get('/uikit/formlayout', function () {
        return Inertia::render('Uikit/FormLayout');
    });
});

// Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
//     // Page principale de gestion des modules
//     Route::get('/permissions/modules', [ModulePermissionController::class, 'index'])->name('permissions.modules.index');
//     // Traitement du basculement d'une action
//     Route::post('/permissions/modules/toggle', [ModulePermissionController::class, 'togglePermission'])->name('permissions.modules.toggle');
// });
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/admin/permissions-pivot', [AdminGroupPivotController::class, 'index'])->name('admin.permissions.pivot.index');
//     Route::post('/admin/permissions-pivot/update', [AdminGroupPivotController::class, 'updatePivot'])->name('admin.permissions.pivot.update');
// });
