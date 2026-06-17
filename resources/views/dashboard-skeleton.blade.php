<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ENDURE-Net Dashboard — Chargement…</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#C41230">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root { --red: #C41230; --red-dark: #8B0D21; }
        body  { background: #F1F5F9; }

        /* ── Skeleton shimmer ── */
        @keyframes shimmer {
            0%   { background-position: -800px 0; }
            100% { background-position:  800px 0; }
        }
        .skel {
            background: linear-gradient(90deg,#E2E8F0 25%,#F1F5F9 50%,#E2E8F0 75%);
            background-size: 800px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: .5rem;
        }

        /* ── Progress bar ── */
        @keyframes progress-pulse {
            0%   { width: 5%; }
            40%  { width: 60%; }
            80%  { width: 85%; }
            100% { width: 95%; }
        }
        #warm-bar {
            height: 4px;
            background: var(--red);
            width: 5%;
            animation: progress-pulse 25s ease-in-out forwards;
            transition: width .3s ease;
        }

        /* ── Status message ── */
        #warm-status {
            font-size: .78rem;
            color: #64748B;
            text-align: center;
            margin-top: .5rem;
        }
    </style>
</head>
<body class="min-h-screen">

{{-- ══════════════════ HEADER (identique dashboard) ══════════════════ --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <img src="/icons/icon.svg" alt="AIRID" class="w-8 h-8">
            </div>
            <div>
                <h1 class="text-base font-extrabold leading-tight tracking-wide">ENDURE-Net Dashboard</h1>
                <p class="text-red-200 text-xs mt-0.5">AIRID · REDCap #{{ config('redcap.project_id') }} · District de Dassa-Zoumè</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden md:block text-red-200 text-xs">Chargement des données…</span>
            {{-- Nav buttons (disabled pendant chargement) --}}
            <span class="flex items-center gap-1.5 text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 opacity-50 cursor-not-allowed font-medium">Queries</span>
            <span class="flex items-center gap-1.5 text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 opacity-50 cursor-not-allowed font-medium">GPS</span>
            <span class="flex items-center gap-1.5 text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 opacity-50 cursor-not-allowed font-medium">Rapport bras</span>
            <span class="flex items-center gap-1.5 text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 opacity-50 cursor-not-allowed font-medium">Inspections</span>
            <span class="flex items-center gap-1.5 text-xs bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 opacity-50 cursor-not-allowed font-medium">Sélection AE</span>
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/20">
                <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->prenom ?: auth()->user()->nom, 0, 1)) }}
                </div>
                <span class="text-xs font-medium leading-tight">
                    {{ trim(auth()->user()->prenom . ' ' . auth()->user()->nom) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Barre de progression sous le header --}}
    <div class="w-full bg-red-900/40" style="height:4px;">
        <div id="warm-bar"></div>
    </div>
</header>

{{-- ══════════════════ BANNIÈRE DE CHARGEMENT ══════════════════ --}}
<div class="max-w-screen-2xl mx-auto px-6 pt-6">
    <div class="bg-white rounded-2xl shadow border border-blue-100 px-6 py-4 flex items-center gap-4">
        {{-- Spinner --}}
        <div class="flex-shrink-0">
            <svg class="animate-spin w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="font-bold text-gray-800 text-sm">Synchronisation des données REDCap en cours…</p>
            <p id="warm-status" class="text-xs text-gray-500 mt-0.5">
                Récupération des formulaires de l'étude (peut prendre 20-40 secondes)
            </p>
        </div>
        <div class="flex-shrink-0 text-right hidden md:block">
            <p class="text-xs text-gray-400">Les données seront mises en cache</p>
            <p class="text-xs text-gray-400">pour les prochaines consultations</p>
        </div>
    </div>
</div>

{{-- ══════════════════ SQUELETTE DU CONTENU ══════════════════ --}}
<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

    {{-- Tabs squelette --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex gap-1 px-4 pt-3 border-b border-gray-100">
            @foreach(['VUE D\'ENSEMBLE', 'DISTRIBUTION', 'SUIVI', 'ADVERSE EVENTS', 'PERFORMANCE', 'CARTE'] as $tab)
                <div class="skel h-8 w-28 mb-3 mr-1 rounded"></div>
            @endforeach
        </div>

        {{-- KPI cards squelette (4 colonnes) --}}
        <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4 mb-2">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                    <div class="skel h-4 w-3/4 rounded"></div>
                    <div class="skel h-8 w-1/2 rounded"></div>
                    <div class="skel h-3 w-full rounded"></div>
                    <div class="skel h-2 w-full rounded-full"></div>
                </div>
            @endfor
        </div>

        {{-- Ligne de KPI secondaires --}}
        <div class="px-5 pb-5 grid grid-cols-2 md:grid-cols-4 gap-4">
            @for($i = 0; $i < 4; $i++)
                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                    <div class="skel h-4 w-2/3 rounded"></div>
                    <div class="skel h-7 w-2/5 rounded"></div>
                    <div class="skel h-3 w-full rounded"></div>
                </div>
            @endfor
        </div>
    </div>

    {{-- Graphiques squelette (2 colonnes) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @for($i = 0; $i < 2; $i++)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                <div class="skel h-5 w-1/3 rounded"></div>
                <div class="skel rounded-xl" style="height:220px;"></div>
            </div>
        @endfor
    </div>

    {{-- Tableau squelette --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        <div class="flex items-center justify-between">
            <div class="skel h-5 w-40 rounded"></div>
            <div class="skel h-8 w-24 rounded-lg"></div>
        </div>
        <div class="space-y-2">
            {{-- En-tête de tableau --}}
            <div class="grid grid-cols-6 gap-3">
                @for($c = 0; $c < 6; $c++)
                    <div class="skel h-4 rounded"></div>
                @endfor
            </div>
            {{-- Lignes --}}
            @for($r = 0; $r < 8; $r++)
                <div class="grid grid-cols-6 gap-3 py-1">
                    @for($c = 0; $c < 6; $c++)
                        <div class="skel h-4 rounded" style="opacity:{{ 1 - $r * 0.08 }}"></div>
                    @endfor
                </div>
            @endfor
        </div>
    </div>

</main>

{{-- ══════════════════ SCRIPT DE PRÉCHAUFFAGE ══════════════════ --}}
<script>
(function () {
    const statusEl  = document.getElementById('warm-status');
    const barEl     = document.getElementById('warm-bar');
    const messages  = [
        'Récupération de la liste des ménages…',
        'Chargement du formulaire de consentement…',
        'Chargement des données de distribution…',
        'Chargement des données de suivi…',
        'Récupération des événements indésirables…',
        'Finalisation du cache…',
    ];
    let msgIdx = 0;
    const msgTimer = setInterval(function () {
        if (msgIdx < messages.length - 1) {
            msgIdx++;
            if (statusEl) statusEl.textContent = messages[msgIdx];
        }
    }, 5000);

    fetch('{{ route('dashboard.warm') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept':       'application/json',
        },
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        clearInterval(msgTimer);
        if (barEl) { barEl.style.animation = 'none'; barEl.style.width = '100%'; }
        if (statusEl) { statusEl.textContent = 'Cache prêt — redirection…'; }
        // Courte pause visuelle pour que l'utilisateur voie "100%"
        setTimeout(function () { window.location.reload(); }, 400);
    })
    .catch(function (err) {
        clearInterval(msgTimer);
        if (statusEl) {
            statusEl.textContent = 'Erreur de synchronisation — rechargement…';
        }
        // En cas d'erreur réseau, recharger quand même (peut réussir sur la 2e tentative)
        setTimeout(function () { window.location.reload(); }, 2000);
    });
})();
</script>

</body>
</html>
