<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('accueil');
        }

        return view('auth.connexion');
    }

    public function etat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pseudo' => ['required', 'string', 'max:50'],
        ]);

        $user = User::parPseudo($data['pseudo']);

        if ($user === null) {
            return response()->json(['existe' => false]);
        }

        return response()->json([
            'existe' => true,
            'premier' => ! $user->aUnMotDePasse(),
            'nom' => $user->name,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pseudo' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string'],
            'password_confirmation' => ['nullable', 'string'],
        ], [
            'pseudo.required' => 'Indique ton pseudo.',
        ]);

        $user = User::parPseudo($data['pseudo']);

        if ($user === null) {
            return back()
                ->withInput($request->only('pseudo'))
                ->withErrors(['pseudo' => 'Personne ne s’appelle comme ça dans la maison.']);
        }

        if (! $request->filled('password')) {
            return back()
                ->withInput($request->only('pseudo'))
                ->with('connexion_etape', $user->aUnMotDePasse() ? 'mot_de_passe' : 'creation');
        }

        if (! $user->aUnMotDePasse()) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.required' => 'Choisis un mot de passe.',
                'password.min' => 'Au moins 8 caractères.',
                'password.confirmed' => 'Les deux mots de passe ne sont pas identiques.',
            ]);

            $user->password = $data['password'];
            $user->save();
        } elseif (! Hash::check($data['password'], $user->getRawOriginal('password'))) {
            return back()
                ->withInput($request->only('pseudo'))
                ->with('connexion_etape', 'mot_de_passe')
                ->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('accueil'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accueil');
    }
}
