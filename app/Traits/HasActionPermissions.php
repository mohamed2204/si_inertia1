<?php

namespace App\Traits; // <--- Très important

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

trait HasActionPermissions
{
    // Dans votre Trait HasPagePermissions

    /**
     * Vérification automatique de l'accès à la page.
     */
    public static function canAccess(array $parameters = []): bool
    {
        if (empty($parameters)) {
            $action = 'view_any_' . static::getPagePrefix();
        }else{
            $action = 'access_' . static::getPagePrefix();
        }


        return static::canPerform($action, true);

        //return static::canPerform('view_any_' . static::getPagePrefix());
    }

//    protected function authorizeAccess(): void
//    {
//        if (!static::canAccess()) {
//            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
//        }
//    }

    // Vérification générique pour une action donnée
    public static function canPerform(string $action, bool $resource = false): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // On peut définir un pattern : "access.nom_de_la_page.action"
        // Exemple : "promotion.creer_pdf"  access.eleves.import
        if (!$resource) {
            $permission =  $action . '_' . static::getPagePrefix() ;
        }else{
            $permission = $action;
        }


        // 2. Vérifier si la permission existe en DB avant de demander à Spatie
        // Cela évite l'exception "There is no permission named..."
        $exists = Permission::where('name', $permission)
            ->where('guard_name', 'web')
            ->exists();

        if (!$exists) {
            //return false; // Ou Log::warning("Permission manquante : $permissionName");
            Log::warning("Permission manquante : $permission");
            return false;
        }

        // Autorise si c'est un admin OU s'il a la permission spécifique

        $isAdmin = $user->isAdmin();
        $isSuperAdmin = $user->isSuperAdmin();
        $hasSpecificPermission = $user->can($permission);




        return $isAdmin || $isSuperAdmin || $hasSpecificPermission;
    }

    // Préfixe pour les permissions de cette page (ex: "promotion")
    abstract static function getPagePrefix(): string;
}
