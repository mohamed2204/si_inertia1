<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     //dd($_SERVER['REMOTE_ADDR']);
//     return view('welcome');
//     //return redirect('/si');
// })->name('home');


Route::get('/', function () {
    return Inertia::render('Dashboard'); // 'Dashboard' doit correspondre au nom de votre fichier dans resources/js/Pages/
});

// Route::get('/parents/login', [ParentAuthController::class, 'create'])
//     ->name('parents.login')
//     ->middleware('guest:parent');


//Route::middleware('guest:parent')->group(function () {
//    // Route::get('/parents/login', [ParentAuthController::class, 'create'])
//    //     ->name('parents.login');
//    Route::get('/parents/login', function () {
//        return inertia('Parents/Auth/Login');
//    })->name('login');
//
//    Route::post('/parents/login', [ParentAuthController::class, 'store']);
//});
//Route::post('/parents/logout', [ParentAuthController::class, 'destroy'])
//    ->middleware('auth:parent')
//    ->name('logout');
//
//Route::middleware(['auth:parent'])->group(function () {
//    // Route::get('/parents/dashboard', [ParentDashboardController::class, 'index']);
//    Route::get('/parents/enfants/{eleve}', [ParentEleveController::class, 'show']);
//});
