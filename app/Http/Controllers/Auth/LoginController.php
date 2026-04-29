<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Affiche la page de login (Sakai React)
     */
    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Gère la tentative de connexion
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        //dd($credentials, $remember);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Redirige vers la route 'home' (votre Dashboard)
            return redirect()->intended('/');
        }

        // Si ça échoue, on renvoie une erreur que React affichera
        throw ValidationException::withMessages([
            'email' => 'Les identifiants ne correspondent pas à nos enregistrements.',
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/auth/login');
    }
}
