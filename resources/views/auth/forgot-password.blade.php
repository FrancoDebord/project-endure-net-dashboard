<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background: #F1F5F9; }</style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center px-4">

    <div class="w-full max-w-sm">

        <div class="flex flex-col items-center mb-8">
            <div style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="rounded-2xl p-3 shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-xl font-extrabold text-gray-900 tracking-wide">ENDURE-Net</h1>
            <p class="text-sm text-gray-500 mt-1">Réinitialisation du mot de passe</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">

            <h2 class="text-base font-bold text-gray-800 mb-2">Mot de passe oublié</h2>
            <p class="text-xs text-gray-500 mb-6">
                Entrez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.
            </p>

            @if (session('status'))
                <div class="bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 text-sm mb-5">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

                <button type="submit"
                        class="w-full py-2.5 rounded-lg text-white text-sm font-bold tracking-wide transition hover:opacity-90"
                        style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                    Envoyer le lien
                </button>
            </form>
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('login') }}"
               class="text-xs font-medium hover:underline" style="color:#C41230">
                ← Retour à la connexion
            </a>
        </div>
    </div>

</body>
</html>
