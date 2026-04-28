<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Role;
use App\Policies\UserPolicy;
use App\Policies\RolePolicy;
// Importez les autres modèles et policies si nécessaire
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
