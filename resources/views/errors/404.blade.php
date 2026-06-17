<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable — ENDURE-Net</title>
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
        .blob-1 { width:500px;height:500px;background:#8B5CF6;top:-15%;left:-10%;
                  animation:drift 9s ease-in-out infinite; }
        .blob-2 { width:400px;height:400px;background:#EC4899;bottom:-10%;right:-5%;
                  animation:drift 12s ease-in-out infinite reverse; }
        .blob-3 { width:280px;height:280px;background:#6D28D9;top:40%;left:38%;
                  animation:drift 15s ease-in-out infinite 2s; }

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
        @keyframes badge-pop {
            0%   { transform:scale(0) rotate(-45deg); }
            70%  { transform:scale(1.2) rotate(5deg); }
            100% { transform:scale(1) rotate(0); }
        }
        @keyframes float-icon {
            0%,100% { transform:translateY(0); }
            50%      { transform:translateY(-6px); }
        }

        .pulse-ring  { animation:pulse-ring 2.4s ease-out infinite; }
        .slide-up    { animation:slide-up .45s cubic-bezier(.22,1,.36,1) both .15s; }
        .badge-pop   { animation:badge-pop .55s cubic-bezier(.34,1.56,.64,1) both .6s; }
        .float-icon  { animation:float-icon 3s ease-in-out infinite; }

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
                          d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01
                             M8.464 15.536a5 5 0 010-7.072M5.636 18.364A9 9 0 015.636 5.636"/>
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

{{-- ── Background ── --}}
<div class="bg-grid"></div>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

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
    <div class="w-full max-w-lg slide-up">

        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden ring-1 ring-slate-900/5">

            {{-- Top stripe --}}
            <div class="h-1.5 bg-gradient-to-r from-violet-400 via-purple-500 to-pink-500"></div>

            <div class="px-8 py-10 text-center">

                {{-- Animated icon --}}
                <div class="relative inline-flex items-center justify-center mb-4">
                    <div class="absolute w-28 h-28 rounded-full bg-violet-100/60 pulse-ring"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-violet-50 to-violet-100
                                rounded-full flex items-center justify-center
                                border border-violet-200 shadow-inner float-icon">
                        <svg class="w-10 h-10 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div class="absolute -bottom-1 -right-1 badge-pop
                                w-7 h-7 bg-violet-500 rounded-full flex items-center justify-center
                                border-2 border-white shadow-md">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Code --}}
                <p class="text-9xl font-black leading-none select-none pointer-events-none"
                   style="color:rgba(139,92,246,.1);letter-spacing:-.04em;margin-bottom:-1.5rem;">404</p>

                {{-- Title --}}
                <h2 class="relative text-2xl font-extrabold text-slate-800 mb-3">
                    Page introuvable
                </h2>

                {{-- Description --}}
                <p class="text-slate-500 text-sm leading-relaxed mb-6 max-w-sm mx-auto">
                    La page que vous recherchez n'existe pas ou a été déplacée.
                    Vérifiez l'URL ou revenez à l'accueil.
                </p>

                {{-- URL hint --}}
                <div class="bg-violet-50 border border-violet-200 rounded-xl px-4 py-3 mb-6 text-left">
                    <p class="text-xs text-violet-500 font-medium mb-1">URL demandée</p>
                    <p class="font-mono text-xs text-violet-700 break-all">{{ request()->url() }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 mb-7">
                    <a href="/"
                       class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                              text-sm font-semibold text-white shadow-md hover:shadow-lg
                              active:scale-95 transition-all"
                       style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10
                                     a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4
                                     a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Retour à l'accueil
                    </a>
                    <button onclick="history.back()"
                            class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                                   text-sm font-semibold border border-slate-200 text-slate-600
                                   hover:bg-slate-50 hover:border-slate-300 active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Page précédente
                    </button>
                </div>

                {{-- Quick links --}}
                <div class="border-t border-slate-100 pt-5">
                    <p class="text-xs text-slate-400 mb-3 font-medium uppercase tracking-wider">Liens rapides</p>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <a href="{{ route('dashboard') }}"
                           class="text-xs px-3 py-1.5 rounded-lg bg-slate-50 text-slate-600
                                  border border-slate-200 hover:bg-slate-100 transition-colors">
                            Tableau de bord
                        </a>
                        <a href="{{ route('inspections') }}"
                           class="text-xs px-3 py-1.5 rounded-lg bg-slate-50 text-slate-600
                                  border border-slate-200 hover:bg-slate-100 transition-colors">
                            Inspections
                        </a>
                        <a href="{{ route('queries') }}"
                           class="text-xs px-3 py-1.5 rounded-lg bg-slate-50 text-slate-600
                                  border border-slate-200 hover:bg-slate-100 transition-colors">
                            Queries
                        </a>
                    </div>
                </div>

            </div>

            {{-- Card footer --}}
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100
                        flex items-center justify-between gap-4 flex-wrap">
                <p class="text-xs text-slate-400">Erreur 404 · ENDURE-Net</p>
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
