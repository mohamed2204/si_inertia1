<?php

use App\Http\Controllers\Admin\GroupAssignmentController;
use App\Http\Controllers\Admin\GroupPivotController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SousDepartementUserController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DesignationPageController;
use App\Http\Controllers\LabRequisController;
use App\Models\Departement;
use App\Models\Membre;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// =========================================================================
// 1. ROUTES PUBLIQUES / INVITÉS (GUEST)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/auth/login', [LoginController::class, 'show'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// =========================================================================
// 2. ROUTES PROTÉGÉES (MIDDLEWARE AUTH)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD CORE ---
    Route::get('/', function () {
        return Inertia::render('Dashboard');
    })->name('home');

    // --- MODULE DÉSIGNATIONS (VUES INERTIA & PROCESS API) ---
    Route::get('/designations-list', [DesignationPageController::class, 'index'])->name('designations.api.index');
    Route::get('/designations/create', [DesignationPageController::class, 'create'])->name('designations.create');
    Route::get('/designations/{designation}', [DesignationPageController::class, 'show'])->name('designations.show');
    Route::get('/designations/{designation}/edit', [DesignationPageController::class, 'edit'])->name('designations.edit');

    // --- ENDPOINTS INTERNAL API (REQUÊTES AXIOS) ---
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/designations', [DesignationPageController::class, 'store'])->name('designations.api.store');
        Route::delete('/designations/{designation}', [DesignationPageController::class, 'destroy'])->name('designations.api.destroy');

        // Labos, Départements et Structures
        Route::get('/sous-departements/{sous_departement}/labs', [DesignationPageController::class, 'getLabsBySousDept'])->name('sous-departements.labs');
        Route::get('/labs/{lab}/config', [DesignationPageController::class, 'getLabConfig'])->name('labs.config');
        Route::get('/labs/{lab}/membres', [DesignationPageController::class, 'getLabMembers'])->name('labs.membres');
        Route::get('/requis-disponibles', [LabRequisController::class, 'list'])->name('requis.list');

        Route::get('/departments/{department}/sous-departments', function (Departement $department) {
            return $department->sousDepartements()->select('id', 'nom')->get();
        })->name('departments.sous-departments');

        Route::get('/membres-disponibles', function () {
            return Membre::select('id', 'nom_membre as nom')->get();
        })->name('membres.index');
    });

    // --- REQUIS DES LABORATOIRES ---
    Route::get('/admin/laboratoires/requis', [LabRequisController::class, 'index'])->name('lab-requis.index');
    Route::post('/laboratoires/{laboratoire}/requis-sync', [LabRequisController::class, 'sync'])->name('laboratoires.requis.sync');

    // --- ADMINISTRATION PÉRIMÉTRIQUE TERRAIN ---
    Route::prefix('admin')->name('permissions.terrain.')->group(function () {
        Route::get('/permissions-terrain', [SousDepartementUserController::class, 'index'])->name('index');
        Route::post('/permissions-terrain/toggle', [SousDepartementUserController::class, 'togglePermission'])->name('toggle');
    });

    // --- GESTION STANDARDS DES UTILISATEURS ---
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // =========================================================================
    // 3. ROUTES D'ADMINISTRATION REINTE (DIRECTION / HAUTE SÉCURITÉ)
    // =========================================================================
    Route::middleware(['admin.group'])->prefix('admin')->name('admin.')->group(function () {

        // Matrice Pivot Globale (Onglet 1)
        Route::get('/permissions-pivot', [GroupPivotController::class, 'index'])->name('permissions.pivot.index');
        Route::post('/permissions-pivot/update', [GroupPivotController::class, 'updatePivot'])->name('permissions.pivot.update');

        // Affectations des Utilisateurs aux Groupes Maîtres
        Route::get('/assignments', [GroupAssignmentController::class, 'index'])->name('assignments.index');
        Route::put('/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('assignments.update');

        // Droits des Modules Applicatifs
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('/permissions/toggle', [PermissionController::class, 'toggleModulePermission'])->name('permissions.modules.toggle');
        Route::post('/permissions/terrain/toggle', [PermissionController::class, 'togglePermission'])->name('permissions.terrain.toggle');
    });

    // --- PAGES SAKAI TEMPLATE TEMPORAIRES / UIKIT ---
    Route::get('pages/crud', function () {
        return Inertia::render('Crud/Crud');
    });
    Route::get('/uikit/formlayout', function () {
        return Inertia::render('Uikit/FormLayout');
    });
});

// <!-- <?php

// use App\Http\Controllers\Admin\GroupAssignmentController;
// use App\Http\Controllers\Admin\GroupPivotController;
// use App\Http\Controllers\Admin\PermissionController;
// use App\Http\Controllers\Admin\SousDepartementUserController;
// use App\Http\Controllers\Admin\UserController;
// use App\Http\Controllers\Auth\LoginController;
// use App\Http\Controllers\DesignationPageController;
// use App\Http\Controllers\LabRequisController;
// use App\Models\Membre;
// use Illuminate\Support\Facades\Route;
// use Inertia\Inertia;

// Route::get('/auth/login', [LoginController::class, 'show'])
//     ->name('login')->middleware('guest'); // Affiche le formulaire de connexion uniquement pour les invités (non-authentifiés)
// Route::post('/auth/login', [LoginController::class, 'authenticate'])
//     ->middleware('guest')->name('login'); // Traite la soumission du formulaire de connexion uniquement pour les invités (non-authentifiés)
// Route::post('/logout', [LoginController::class, 'logout'])
//     ->name('logout'); // Traite la déconnexion (peut être appelé par un utilisateur authentifié pour se déconnecter)

// // --- ROUTES PROTÉGÉES (Middleware auth) ---
// Route::middleware(['auth'])->group(function () {

//     // Ta route Home (Dashboard) est maintenant protégée
//     Route::get('/', function () {
//         return Inertia::render('Dashboard'); // Assure-toi d'avoir une page Dashboard.vue dans resources/js/Pages/
//     })->name('home');

//     // Page principale de la matrice terrain
//     Route::get('/admin/permissions-terrain', [SousDepartementUserController::class, 'index'])->name('permissions.terrain.index'); // Affichage de la page Inertia

//     // Traitement Ajax du switch de permission
//     Route::post('/admin/permissions-terrain/toggle', [SousDepartementUserController::class, 'togglePermission'])->name('permissions.terrain.toggle'); // Route pour basculer une permission CRUD d'un utilisateur sur un sous-département spécifique

//     Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index'); // Page principale de gestion des utilisateurs (vue Inertia)
//     Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store'); // Route pour créer un nouvel utilisateur
//     Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update'); // Route pour mettre à jour un utilisateur existant
//     Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy'); // Route pour supprimer un utilisateur

//     Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])->name('admin.assignments.index'); // Page principale de gestion des affectations (vue Inertia)
//     Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])->name('admin.assignments.update'); // Route pour mettre à jour une affectation existante


//     // 2. La route API que votre service api.js va interroger (appelée à chaque filtre/pagination)
//     Route::get('/designations-list', [DesignationPageController::class, 'index'])
//         ->name('designations.api.index'); // Route API pour récupérer la liste des designations
//     //Route::get('/api/designations', [DesignationPageController::class, 'listApi'])->name('designations.list'); // C'est cette route qui est appelée par le service JS pour récupérer les données filtrées
//     Route::get('/designations/create', [DesignationPageController::class, 'create'])
//         ->name('designations.create'); // Route pour afficher le formulaire de création (Inertia)
//     Route::get('/designations/{designation}', [DesignationPageController::class, 'show'])
//         ->name('designations.show'); // Route pour afficher les détails d'une désignation (Inertia)
//     Route::get('/designations/{designation}/edit', [DesignationPageController::class, 'edit'])
//         ->name('designations.edit'); // Route pour afficher le formulaire d'édition d'une désignation (Inertia)
//     Route::post('/api/designations', [DesignationPageController::class, 'store'])
//         ->name('designations.api.store'); // Route pour créer une nouvelle désignation (appelée par le formulaire Inertia)
//     Route::delete('/api/designations/{designation}', [DesignationPageController::class, 'destroy'])
//         ->name('designations.api.destroy'); // Route pour supprimer une désignation (appelée par le formulaire Inertia)

//     // Récupérer les labos d'un sous-département
//     Route::get('/api/sous-departements/{sous_departement}/labs', [DesignationPageController::class, 'getLabsBySousDept'])
//         ->name('api.sous-departements.labs'); // Route pour récupérer les laboratoires d'un sous-département spécifique (appelée par Axios)

//     // Récupérer la config complète d'un labo
//     Route::get('/api/labs/{lab}/config', [DesignationPageController::class, 'getLabConfig'])
//         ->name('api.labs.config'); // Route pour récupérer la configuration complète d'un laboratoire spécifique (appelée par Axios)
//     Route::get('/api/labs/{lab}/membres', [DesignationPageController::class, 'getLabMembers'])
//         ->name('api.labs.membres'); // Route pour récupérer les membres d'un laboratoire spécifique (appelée par Axios)

//     // /api/departments/${deptId}/sous-departments`),
//     Route::get('/api/departments/{department}/sous-departments', function (\App\Models\Departement $department) {
//         return $department->sousDepartements()->select('id', 'nom')->get();
//     })->name('api.departments.sous-departments'); // Route pour récupérer les sous-départements d'un département spécifique (appelée par Axios)

//     //Route::post('/designationsapi', [DesignationPageController::class, 'store'])->name('designations.api.store');

//     Route::get('/api/membres-disponibles', function () {
//         // On ne récupère que les colonnes nécessaires pour alléger le JSON
//         return Membre::select('id', 'nom_membre as nom')->get();
//     })->name('api.membres.index'); // Route pour récupérer la liste des membres disponibles (appelée par Axios)

//     // Route pour l'affichage de la page de configuration (Inertia)
//     Route::get('/admin/laboratoires/requis', [LabRequisController::class, 'index'])
//         ->name('lab-requis.index'); // Page principale de gestion des requis (vue Inertia)

//     // Route pour enregistrer les requis d'un laboratoire spécifique
//     Route::post('/laboratoires/{laboratoire}/requis-sync', [LabRequisController::class, 'sync'])
//         ->name('laboratoires.requis.sync'); // Route pour synchroniser les requis d'un laboratoire spécifique (appelée par le formulaire Inertia)

//     // Route optionnelle pour récupérer la liste initiale deLabRequisControllerxios)
//     Route::get('/api/requis-disponibles', [LabRequisController::class, 'list'])->name('api.requis.list'); // Route pour récupérer la liste des requis disponibles (appelée par Axios)

//     Route::get('pages/crud', function () {
//         return Inertia::render('Crud/Crud');
//     });

//     // 2. Les routes d'administration sécurisées (Uniquement pour la Direction)
//     Route::middleware(['admin.group'])->group(function () {
//         // La page de la matrice pivot
//         Route::get('/admin/permissions-pivot', [GroupPivotController::class, 'index'])
//             ->name('admin.permissions.pivot.index');
//         Route::post('/admin/permissions-pivot/update', [GroupPivotController::class, 'updatePivot'])
//             ->name('admin.permissions.pivot.update');

//         // La page d'affectation des utilisateurs aux groupes
//         Route::get('/admin/assignments', [GroupAssignmentController::class, 'index'])
//             ->name('admin.assignments.index');
//         Route::put('/admin/assignments/{user}', [GroupAssignmentController::class, 'update'])
//             ->name('admin.assignments.update');

//         // Page principale de gestion des modules
//         //Route::get('/admin/permissions/modules', [ModulePermissionController::class, 'index'])->name('permissions.modules.index');
//         Route::get('/admin/permissions', [PermissionController::class, 'index'])
//             ->name('permissions.index');
//         // Traitement du basculement d'une action
//         Route::post('/admin/permissions/toggle', [PermissionController::class, 'toggleModulePermission'])
//             ->name('admin.permissions.modules.toggle');
//         //Route::post('/admin/permissions/modules', [PermissionController::class, 'updatePivotPermission'])->name('admin.permissions.pivot.update');
//         // 🆕 La nouvelle route mise à jour et branchée sur le bon contrôleur :
//         Route::post('/admin/permissions/terrain/toggle', [PermissionController::class, 'togglePermission'])
//             ->name('admin.permissions.terrain.toggle');
//     });

//     // Ajoute ici toutes tes autres pages Sakai (Profile, Settings, etc.)
//     Route::get('/uikit/formlayout', function () {
//         return Inertia::render('Uikit/FormLayout');
//     });
// }); -->

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
