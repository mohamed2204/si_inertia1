<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminGroup
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sécurité : On vérifie si l'utilisateur appartient au groupe de direction
        if ($user && $user->groups()->where('code', 'admin')->exists()) {
            return $next($request);
        }

        // Si ce n'est pas le cas, on le rejette (Erreur 403 - Non autorisé)
        abort(403, "Vous n'avez pas les droits nécessaires pour accéder à cette page d'administration.");
    }
}