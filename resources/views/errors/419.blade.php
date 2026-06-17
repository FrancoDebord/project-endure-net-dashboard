<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expirée — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background:#F1F5F9; min-height:100vh; display:flex; flex-direction:column; }

        .bg-grid {
            position:fixed; inset:0; pointer-events:none;
            background-image:linear-gradient(rgba(0,0,0,.025) 1px,transparent 1px),
                             linear-gradient(90deg,rgba(0,0,0,.025) 1px,transparent 1px);
            background-size:48px 48px;
        }
        .blob {
            position:fixed; border-radius:50%; filter:blur(80px);
            opacity:.07; pointer-events:none;
        }
        .blob-1 { width:500px;height:500px;background:#F59E0B;top:-15%;left:-10%;
                  animation:drift 9s ease-in-out infinite; }
        .blob-2 { width:400px;height:400px;background:#D97706;bottom:-10%;right:-5%;
                  animation:drift 12s ease-in-out infinite reverse; }

        @keyframes drift {
            0%,100% { transform:translate(0,0) scale(1); }
            33%      { transform:translate(18px,-18px) scale(1.03); }
            66%      { transform:translate(-14px,14px) scale(.97); }
        }
        @keyframes pulse-ring {
            0%   { transform:scale(.85); opacity:.5; }
            100% { transform:scale(1.9);  opacity:0; }
        }
        @keyframes slide-up {
            from { transform:translateY(24px); opacity:0; }
            to   { transform:translateY(0);    opacity:1; }
        }
        @keyframes tick {
            0%,100% { transform:rotate(-5deg); }
            50%      { transform:rotate(5deg); }
        }

        .pulse-ring { animation:pulse-ring 2.4s ease-out infinite; }
        .slide-up   { animation:slide-up .45s cubic-bezier(.22,1,.36,1) both .15s; }
        .tick       { animation:tick 1s ease-in-out infinite; transform-origin:50% 90%; }

        #offline-bar {
            position:fixed; top:0; left:0; right:0; z-index:9999;
            transform:translateY(-100%);
            transition:transform .35s cubic-bezier(.34,1.56,.64,1);
        }
        #offline-bar.visible { transform:translateY(0); }
    </style>
</head>
<body>

{{-- ── Offline bar ── --}}
<div id="offline-bar" class="shadow-lg">
    <div class="bg-amber-500 text-white px-4 py-3">
        <div class="max-w-xl mx-auto flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-amber-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072
                             M12 12h.01M8.464 15.536a5 5 0 010-7.072M5.636 18.364A9 9 0 015.636 5.636"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-sm">Aucune connexion Internet détectée</p>
                <p class="text-xs text-amber-100 mt-0.5">Vérifiez votre réseau Wi-Fi ou câble Ethernet, puis rechargez la page.</p>
            </div>
            <button onclick="document.getElementById('offline-bar').classList.remove('visible')"
                    class="ml-auto text-amber-200 hover:text-white flex-shrink-0 mt-0.5 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<div class="bg-grid"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

{{-- ── Header ── --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="relative z-10 text-white shadow-lg flex-shrink-0">
    <div class="max-w-screen-lg mx-auto px-6 py-3 flex items-center gap-4">
        <div class="bg-white rounded-lg p-1.5 flex-shrink-0 shadow-sm">
            <img src="/icons/icon.svg" alt="AIRID" class="w-7 h-7" onerror="this.style.display='none'">
        </div>
        <div>
            <h1 class="text-sm font-extrabold leading-tight tracking-wide">ENDURE-Net Dashboard</h1>
            <p class="text-red-200 text-xs mt-0.5">AIRID · Système de suivi des moustiquaires</p>
        </div>
    </div>
</header>

{{-- ── Main ── --}}
<main class="flex-1 relative z-10 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md slide-up">

        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden ring-1 ring-slate-900/5">

            {{-- Top stripe --}}
            <div class="h-1.5 bg-gradient-to-r from-amber-400 via-orange-400 to-yellow-400"></div>

            <div class="px-8 py-10 text-center">

                {{-- Animated icon --}}
                <div class="relative inline-flex items-center justify-center mb-4">
                    <div class="absolute w-28 h-28 rounded-full bg-amber-100/60 pulse-ring"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-amber-50 to-amber-100
                                rounded-full flex items-center justify-center
                                border border-amber-200 shadow-inner">
                        <svg class="w-10 h-10 text-amber-500 tick" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                </div>

                {{-- Code --}}
                <p class="text-9xl font-black leading-none select-none pointer-events-none"
                   style="color:rgba(245,158,11,.1);letter-spacing:-.04em;margin-bottom:-1.5rem;">419</p>

                {{-- Title --}}
                <h2 class="relative text-2xl font-extrabold text-slate-800 mb-3">
                    Session expirée
                </h2>

                {{-- Description --}}
                <p class="text-slate-500 text-sm leading-relaxed mb-8 max-w-xs mx-auto">
                    Votre session a expiré par mesure de sécurité.
                    Veuillez <strong class="text-slate-700">recharger la page</strong> ou
                    vous reconnecter pour continuer.
                </p>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                    <button onclick="window.location.reload()"
                            class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                                   text-sm font-semibold text-white shadow-md hover:shadow-lg
                                   active:scale-95 transition-all"
                            style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581
                                     m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Recharger la page
                    </button>
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                              text-sm font-semibold border border-slate-200 text-slate-600
                              hover:bg-slate-50 hover:border-slate-300 active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                                     a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Se reconnecter
                    </a>
                </div>

            </div>

            {{-- Card footer --}}
            <div class="px-8 py-4 bg-amber-50 border-t border-amber-100
                        flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2 text-xs text-amber-700">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6
                                 a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Session sécurisée expirée
                </div>
                <span id="chip-net"
                      class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full
                             bg-emerald-50 text-emerald-600 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    En ligne
                </span>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-5">AIRID · ENDURE-Net · {{ date('Y') }}</p>
    </div>
</main>

<script>
(function watchNet() {
    const bar  = document.getElementById('offline-bar');
    const chip = document.getElementById('chip-net');
    function update() {
        if (navigator.onLine) {
            bar.classList.remove('visible');
            if (chip) {
                chip.className = 'inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200';
                chip.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>En ligne';
            }
        } else {
            bar.classList.add('visible');
            if (chip) {
                chip.className = 'inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-200';
                chip.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>Hors ligne';
            }
        }
    }
    window.addEventListener('online',  update);
    window.addEventListener('offline', update);
    update();
})();
</script>

</body>
</html>
