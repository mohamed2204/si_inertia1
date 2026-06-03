<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
// Importez les autres modèles et policies si nécessaire
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
// <-- Ajoute cet import

class AppServiceProvider extends ServiceProvider
{
    protected array $policies = [
        User::class => UserPolicy::class,
        // Permission::class => PermissionPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('fr');

        // 1. Enregistrement manuel des Policies (Crucial si vous n'utilisez pas AuthServiceProvider)
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Force le schéma global
        // URL::forceScheme('https');

        // Force explicitement Vite à utiliser des URLs de build sécurisées
        if (config('app.env') !== 'local' || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            Vite::useScriptTagAttributes([
                'crossorigin' => 'anonymous',
            ]);
        }


        if (!App::environment('local') || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            URL::forceScheme('https');
        }
        // 2. LE "COUPE-FILE" POUR L'ADMIN (Stoppe les redirections infinies)
        //        Gate::before(function ($user, $ability) {
        //            dd($user);
        //            //dd($user->roles);
        //            // Si l'utilisateur a le rôle admin ou super_admin, on autorise TOUT
        //            // Retourner true permet de court-circuiter toutes les Policies
        //
        //            //return true;
        //
        //            if ($user->hasRole(['admin'])) {
        //                //dd("admin");
        //                return true;
        //            }
        //
        //            // Pour les autres utilisateurs, on retourne null pour
        //            // que Laravel continue vers les Policies classiques
        //            return null;
        //        });
    }
}
