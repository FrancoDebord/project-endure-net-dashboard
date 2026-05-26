<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Ces identifiants ne correspondent à aucun compte.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ── Microsoft 365 OAuth ───────────────────────────────────────────────────

    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function handleMicrosoftCallback(Request $request)
    {
        // Azure AD renvoie une erreur explicite (ex: accès refusé par l'admin)
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Connexion Microsoft refusée : ' . $request->input('error_description', $request->input('error')),
            ]);
        }

        try {
            $msUser = Socialite::driver('microsoft')->user();
        } catch (\Exception) {
            return redirect()->route('login')->withErrors([
                'email' => 'Authentification Microsoft échouée. Veuillez réessayer.',
            ]);
        }

        // Recherche par email dans notre base — seuls les comptes actifs sont acceptés
        $user = User::where('email', $msUser->getEmail())
                    ->where('active', 1)
                    ->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Aucun compte actif trouvé pour « ' . $msUser->getEmail() . ' ». Contactez l\'administrateur.',
            ]);
        }

        // Synchronise nom/prenom depuis le profil Azure AD si disponibles
        $azureData = $msUser->user ?? [];
        $updates = [];
        if (!empty($azureData['surname'])   && empty($user->nom))    $updates['nom']    = $azureData['surname'];
        if (!empty($azureData['givenName']) && empty($user->prenom)) $updates['prenom'] = $azureData['givenName'];
        if ($updates) $user->update($updates);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
