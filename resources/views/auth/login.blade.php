<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #F1F5F9; }
        :root { --red: #C41230; --red-dark: #8B0D21; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center px-4">

    <div class="w-full max-w-sm">

        {{-- Logo / Brand --}}
        <div class="flex flex-col items-center mb-8">
            <div style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="rounded-2xl p-3 shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h1 class="text-xl font-extrabold text-gray-900 tracking-wide">ENDURE-Net</h1>
            <p class="text-sm text-gray-500 mt-1">Tableau de bord AIRID · REDCap</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg p-8">

            <h2 class="text-base font-bold text-gray-800 mb-6">Connexion</h2>

            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm mb-5">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Adresse e-mail
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-4 py-2.5 rounded-lg border text-sm transition
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}
                                  focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Mot de passe
                    </label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                        <input type="checkbox" name="remember" class="rounded text-red-600 focus:ring-red-400">
                        Se souvenir de moi
                    </label>
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium hover:underline" style="color:#C41230">
                        Mot de passe oublié ?
                    </a>
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-lg text-white text-sm font-bold tracking-wide transition hover:opacity-90 mt-2"
                        style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                    Se connecter
                </button>
            </form>

            {{-- Séparateur --}}
            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">ou</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            {{-- Bouton Microsoft 365 --}}
            <a href="{{ route('auth.microsoft') }}"
               class="w-full flex items-center justify-center gap-3 py-2.5 px-4 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 transition text-sm font-semibold text-gray-700 shadow-sm">
                {{-- Logo Microsoft --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 23 23" class="w-5 h-5 flex-shrink-0">
                    <rect x="1"  y="1"  width="10" height="10" fill="#F25022"/>
                    <rect x="12" y="1"  width="10" height="10" fill="#7FBA00"/>
                    <rect x="1"  y="12" width="10" height="10" fill="#00A4EF"/>
                    <rect x="12" y="12" width="10" height="10" fill="#FFB900"/>
                </svg>
                Se connecter avec Microsoft 365
            </a>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            © {{ date('Y') }} AIRID — Accès réservé
        </p>
    </div>

</body>
</html>
