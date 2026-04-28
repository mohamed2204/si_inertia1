<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ParentAuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Parents/Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('parent')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect('/parents/dashboard');
        }

        return back()->withErrors([
            'email' => 'Identifiants incorrects',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::guard('parent')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('parents.login');
    }
}
