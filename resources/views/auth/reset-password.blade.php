<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background: #F1F5F9; }</style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center px-4">

    <div class="w-full max-w-sm">

        <div class="flex flex-col items-center mb-8">
            <div style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="rounded-2xl p-3 shadow-lg mb-4">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-extrabold text-gray-900 tracking-wide">ENDURE-Net</h1>
            <p class="text-sm text-gray-500 mt-1">Définir un nouveau mot de passe</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8">

            <h2 class="text-base font-bold text-gray-800 mb-6">Nouveau mot de passe</h2>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Adresse e-mail
                    </label>
                    <input type="email" name="email" value="{{ old('email', request()->email) }}" required autofocus
                           class="w-full px-4 py-2.5 rounded-lg border text-sm transition
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}
                                  focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Nouveau mot de passe
                    </label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                        Confirmer le mot de passe
                    </label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-transparent">
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-lg text-white text-sm font-bold tracking-wide transition hover:opacity-90"
                        style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                    Réinitialiser le mot de passe
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
