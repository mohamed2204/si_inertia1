<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        // Force l'injection du Middleware de la Debugbar dans le groupe Web
        $middleware->web(append: [
            \Barryvdh\Debugbar\Middleware\InjectDebugbar::class,
            HandleInertiaRequests::class,
        ]);
        // Indispensable pour que le middleware 'auth' sache où aller
        $middleware->redirectTo(
            guests: '/auth/login',
            users: '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //        pour rediriger vers la route nommée 'home' les 404
        // $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        //     return redirect()->route('home'); // Redirige vers la route nommée 'home'
        // });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            // On ne redirige vers 'home' que si la route existe
            // et on laisse Laravel gérer le reste
            if (Route::has('home')) {
                return redirect()->route('home');
            }
        });
    })->create();

//use Illuminate\Http\Request;
//use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//->withExceptions(function (Exceptions $exceptions) {
//    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
//        return redirect()->route('home'); // Redirige vers la route nommée 'home'
//    });
//})
