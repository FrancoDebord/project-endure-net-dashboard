<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur — ENDURE-Net</title>
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
        .blob-1 { width:520px;height:520px;background:#C41230;top:-15%;left:-10%;
                  animation:drift 9s ease-in-out infinite; }
        .blob-2 { width:420px;height:420px;background:#F97316;bottom:-10%;right:-5%;
                  animation:drift 11s ease-in-out infinite reverse; }
        .blob-3 { width:280px;height:280px;background:#8B0D21;top:45%;left:38%;
                  animation:drift 14s ease-in-out infinite 3s; }

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

        .pulse-ring  { animation:pulse-ring 2.4s ease-out infinite; }
        .slide-up    { animation:slide-up .45s cubic-bezier(.22,1,.36,1) both .15s; }
        .badge-pop   { animation:badge-pop .55s cubic-bezier(.34,1.56,.64,1) both .6s; }

        /* Offline bar */
        #offline-bar {
            position:fixed; top:0; left:0; right:0; z-index:9999;
            transform:translateY(-100%);
            transition:transform .35s cubic-bezier(.34,1.56,.64,1);
        }
        #offline-bar.visible { transform:translateY(0); }

        /* Accordion */
        .acc-body {
            max-height:0; overflow:hidden;
            transition:max-height .3s ease;
        }
        .acc-body.open { max-height:400px; }
        .acc-chevron { transition:transform .3s ease; }
        .acc-chevron.open { transform:rotate(180deg); }

        /* Countdown button */
        .btn-primary {
            background:linear-gradient(135deg,#8B0D21,#C41230);
            color:#fff; border:none;
        }
        .btn-primary:hover { background:linear-gradient(135deg,#7a0b1d,#b0102a); }
    </style>
</head>
<body>

{{-- ── Offline detection bar ── --}}
<div id="offline-bar" class="shadow-lg">
    <div class="bg-amber-500 text-white px-4 py-3">
        <div class="max-w-xl mx-auto flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-amber-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M8.464 15.536a5 5 0 010-7.072M5.636 18.364A9 9 0 015.636 5.636"/>
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

{{-- ── Background layers ── --}}
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

{{-- ── Main content ── --}}
<main class="flex-1 relative z-10 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-lg slide-up">

        {{-- Error card --}}
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden ring-1 ring-slate-900/5">

            {{-- Top gradient stripe --}}
            <div class="h-1.5 bg-gradient-to-r from-orange-400 via-orange-500 to-red-500"></div>

            <div class="px-8 py-10 text-center">

                @php
                    $isDbError = isset($exception) && (
                        $exception instanceof \Illuminate\Database\QueryException ||
                        $exception instanceof \PDOException ||
                        (method_exists($exception, 'getMessage') && (
                            str_contains($exception->getMessage(), 'SQLSTATE')         ||
                            str_contains($exception->getMessage(), 'Connection refused')||
                            str_contains($exception->getMessage(), 'could not find driver') ||
                            str_contains($exception->getMessage(), 'Access denied for user')||
                            str_contains($exception->getMessage(), 'No such host')
                        ))
                    );
                @endphp

                {{-- Animated icon --}}
                <div class="relative inline-flex items-center justify-center mb-4">
                    <div class="absolute w-28 h-28 rounded-full bg-orange-100/50 pulse-ring"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-orange-50 to-orange-100
                                rounded-full flex items-center justify-center
                                border border-orange-200 shadow-inner">
                        @if($isDbError)
                        <svg class="w-10 h-10 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <ellipse cx="12" cy="5" rx="9" ry="3" stroke-width="1.5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 5v4c0 1.657 4.03 3 9 3s9-1.343 9-3V5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 9v6c0 1.657 4.03 3 9 3s9-1.343 9-3V9"/>
                        </svg>
                        @else
                        <svg class="w-10 h-10 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2
                                     M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2
                                     m-2-4h.01M17 16h.01"/>
                        </svg>
                        @endif
                    </div>
                    {{-- Error badge --}}
                    <div class="absolute -bottom-1 -right-1 badge-pop
                                w-7 h-7 bg-red-500 rounded-full flex items-center justify-center
                                border-2 border-white shadow-md">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Code number (huge muted) --}}
                <p class="text-9xl font-black leading-none select-none pointer-events-none"
                   style="color:rgba(249,115,22,.1);letter-spacing:-.04em;margin-bottom:-1.5rem;">500</p>

                {{-- Title --}}
                <h2 class="relative text-2xl font-extrabold text-slate-800 mb-3">
                    @if($isDbError)
                        Base de données inaccessible
                    @else
                        Erreur interne du serveur
                    @endif
                </h2>

                {{-- Description --}}
                <p class="text-slate-500 text-sm leading-relaxed mb-8 max-w-sm mx-auto">
                    @if($isDbError)
                        La connexion au serveur MySQL <strong class="text-slate-700">n'a pas pu être établie</strong>.
                        Le service de base de données est peut-être en cours de redémarrage
                        ou les paramètres de connexion ont changé.
                    @else
                        Une erreur inattendue s'est produite côté serveur.
                        Si le problème persiste, contactez l'équipe technique.
                    @endif
                </p>

                {{-- Action buttons --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 mb-8">
                    <button id="retry-btn"
                            onclick="window.location.reload()"
                            class="btn-primary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                                   text-sm font-semibold shadow-md hover:shadow-lg active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581
                                     m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Réessayer
                        <span id="retry-countdown"
                              class="bg-red-700/40 text-white text-xs px-1.5 py-0.5 rounded-md font-bold">30</span>
                    </button>
                    <a href="/"
                       class="flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl
                              text-sm font-semibold border border-slate-200 text-slate-600
                              hover:bg-slate-50 hover:border-slate-300 active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10
                                     a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4
                                     a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Accueil
                    </a>
                </div>

                {{-- Status chips --}}
                <div class="flex items-center justify-center gap-3 flex-wrap mb-6">
                    <span id="chip-db"
                          class="inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full
                                 bg-orange-50 text-orange-600 border border-orange-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                        MySQL · 127.0.0.1:3306
                    </span>
                    <span id="chip-net"
                          class="inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full
                                 bg-emerald-50 text-emerald-600 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Réseau · en ligne
                    </span>
                </div>

                {{-- Technical details (debug mode only) --}}
                @if(isset($exception) && config('app.debug'))
                <div class="border border-slate-200 rounded-xl overflow-hidden text-left">
                    <button onclick="toggleAcc()"
                            class="w-full flex items-center justify-between px-4 py-3
                                   text-xs text-slate-500 hover:bg-slate-50 transition-colors">
                        <span class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            Détails techniques
                        </span>
                        <svg id="acc-chevron" class="acc-chevron w-3.5 h-3.5 text-slate-400"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="acc-body" class="acc-body">
                        <div class="px-4 pb-4">
                            <div class="bg-slate-900 text-slate-100 rounded-lg p-4 font-mono text-xs overflow-x-auto leading-relaxed">
                                <div class="text-red-400 font-bold mb-2">{{ class_basename($exception) }}</div>
                                <div class="text-slate-300 break-all">{{ Str::limit($exception->getMessage(), 350) }}</div>
                                @if(method_exists($exception, 'getCode') && $exception->getCode())
                                <div class="text-slate-500 mt-2 pt-2 border-t border-slate-700">
                                    Code : {{ $exception->getCode() }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- Card footer --}}
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100
                        flex items-center justify-between gap-4 flex-wrap">
                <p class="text-xs text-slate-400">Erreur 500 · ENDURE-Net</p>
                <a href="mailto:support@airid-africa.com"
                   class="text-xs text-red-600 hover:text-red-700 hover:underline
                          font-semibold transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7
                                 a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contacter le support
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-5">
            AIRID · ENDURE-Net · {{ date('Y') }}
        </p>
    </div>
</main>

<script>
// ── Accordion ──────────────────────────────────────────────
function toggleAcc() {
    const body    = document.getElementById('acc-body');
    const chevron = document.getElementById('acc-chevron');
    body   .classList.toggle('open');
    chevron.classList.toggle('open');
}

// ── Connectivity detection ─────────────────────────────────
(function watchNet() {
    const bar     = document.getElementById('offline-bar');
    const chip    = document.getElementById('chip-net');
    function update() {
        if (navigator.onLine) {
            bar.classList.remove('visible');
            if (chip) {
                chip.className = 'inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200';
                chip.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Réseau · en ligne';
            }
        } else {
            bar.classList.add('visible');
            if (chip) {
                chip.className = 'inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full bg-red-50 text-red-600 border border-red-200';
                chip.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>Réseau · hors ligne';
            }
        }
    }
    window.addEventListener('online',  update);
    window.addEventListener('offline', update);
    update();
})();

// ── Auto-retry countdown ───────────────────────────────────
(function countdown() {
    let n = 30;
    const el  = document.getElementById('retry-countdown');
    const btn = document.getElementById('retry-btn');
    if (!el || !btn) return;
    const t = setInterval(() => {
        n--;
        el.textContent = n;
        if (n <= 0) {
            clearInterval(t);
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Connexion en cours…';
            setTimeout(() => window.location.reload(), 800);
        }
    }, 1000);
})();
</script>

</body>
</html>
