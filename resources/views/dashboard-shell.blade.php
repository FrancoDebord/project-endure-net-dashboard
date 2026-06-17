<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $projectInfo['project_title'] ?? 'ENDURE-Net Dashboard' }}</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#C41230">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ENDURE-Net">
    <link rel="apple-touch-icon" href="/icons/icon.svg">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --red:      #C41230;
            --red-dark: #8B0D21;
            --red-lite: #FDECED;
            --dark:     #1A1A1A;
        }
        body { background: #F1F5F9; }

        /* Tabs */
        .tab-btn {
            padding: .65rem 1.5rem;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-bottom: 3px solid transparent;
            color: #64748B;
            cursor: pointer;
            transition: color .15s, border-color .15s;
            white-space: nowrap;
        }
        .tab-btn:hover  { color: var(--red); }
        .tab-btn.active { color: var(--red); border-color: var(--red); }
        .tab-panel      { display: none; }
        .tab-panel.active { display: block; }

        /* Cards */
        .card { background: #fff; border-radius: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,.09); }
        .kpi-card { transition: transform .15s, box-shadow .15s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.14); }

        /* Section title */
        .section-title {
            font-size: .80rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--dark);
            border-left: 4px solid var(--red);
            padding-left: .65rem;
            margin-bottom: .9rem;
        }

        /* Progress */
        .prog      { background: #E2E8F0; border-radius: 9999px; height: .55rem; }
        .prog-fill { border-radius: 9999px; height: .55rem; transition: width .5s ease; }

        /* Map */
        #map { height: 380px; border-radius: 1rem; z-index: 0; }
        #map:-webkit-full-screen { height: 100vh !important; border-radius: 0 !important; }
        #map:fullscreen           { height: 100vh !important; border-radius: 0 !important; }

        /* Badges */
        .badge-ok   { background: #DCFCE7; color: #15803D; font-weight: 600; }
        .badge-no   { background: #FEE2E2; color: #991B1B; font-weight: 600; }
        .badge-red  { background: var(--red-lite); color: var(--red); font-weight: 600; }
        .badge-gray { background: #E2E8F0; color: #1E293B; font-weight: 600; }
        .badge-amb  { background: #FEF3C7; color: #92400E; font-weight: 600; }
        .badge-slt  { background: #EEF2FF; color: #3730A3; font-weight: 600; }

        /* Details/summary collapsible */
        details summary { cursor: pointer; user-select: none; list-style: none; }
        details summary::-webkit-details-marker { display: none; }
        details summary .chevron { transition: transform .2s; }
        details[open] summary .chevron { transform: rotate(180deg); }

        /* Table hover */
        .hov-row:hover { background: #FDF4F5; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }

        /* Inner timepoint tabs */
        .suivi-tp-btn {
            padding: .55rem 1.3rem; font-size: .78rem; font-weight: 700;
            letter-spacing: .04em; text-transform: uppercase;
            border-bottom: 2px solid transparent;
            color: #6B7280; cursor: pointer;
            transition: color .15s, border-color .15s;
            white-space: nowrap; background: none;
        }
        .suivi-tp-btn:hover  { color: var(--red); }
        .suivi-tp-btn.active { color: var(--red); border-bottom-color: var(--red); }
        .suivi-tp-panel      { display: none; }
        .suivi-tp-panel.active { display: block; }

        /* Skeleton shimmer */
        @keyframes shimmer {
            0%   { background-position: -800px 0; }
            100% { background-position:  800px 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 800px 100%;
            animation: shimmer 1.5s infinite linear;
            border-radius: .5rem;
        }
    </style>
</head>
<body class="min-h-screen">

{{-- ══════════════════ HEADER ══════════════════ --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <img src="/icons/icon.svg" alt="AIRID" class="w-8 h-8">
            </div>
            <div>
                <h1 class="text-base font-extrabold leading-tight tracking-wide">
                    {{ $projectInfo['project_title'] ?? 'ENDURE-Net Dashboard' }}
                </h1>
                <p class="text-red-200 text-xs mt-0.5">
                    AIRID · REDCap #{{ config('redcap.project_id') }} · District de Dassa-Zoumè
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden md:block text-red-200 text-xs">Actualisé le {{ now()->format('d/m/Y à H:i') }}</span>
            <a href="{{ route('queries') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Queries
            </a>
            <a href="{{ route('gps.export') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                GPS
            </a>
            <a href="{{ route('acceptabilite.index') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Acceptabilité
            </a>
            <a href="{{ route('ae.selection.index') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Sélection AE
            </a>
            <form method="POST" action="{{ route('cache.clear') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualiser
                </button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-1.5 text-xs bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg border border-white/20 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</header>

{{-- ══════════════════ TAB NAV ══════════════════ --}}
<nav class="bg-white sticky top-[57px] z-40" style="border-bottom:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,.06)">
    <div class="max-w-screen-2xl mx-auto px-6 flex gap-0 overflow-x-auto">
        @foreach ($events as $key => $ev)
            <button class="tab-btn {{ $loop->first ? 'active' : '' }} loading"
                    data-section="{{ in_array($ev['key'], ['suivi_a','suivi']) ? 'followup' : $ev['key'] }}"
                    onclick="showTab('{{ $ev['key'] }}', this)">
                {{ $ev['icon'] }} {{ $ev['label'] }}
            </button>
        @endforeach
        <button class="tab-btn loading" data-section="rapport" onclick="showTab('rapport', this)">
            &#128202; Rapport
        </button>
        <button class="tab-btn loading" data-section="distribution" onclick="showTab('distribution', this)">
            &#129498; Distribution
        </button>
    </div>
</nav>

{{-- ══════════════════ TAB PANELS (skeleton) ══════════════════ --}}
<div class="max-w-screen-2xl mx-auto px-6 py-8">

    {{-- Baseline skeleton --}}
    <div id="tab-baseline" class="tab-panel active space-y-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="card p-6 space-y-3">
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton h-10 w-1/3"></div>
                <div class="skeleton h-3 w-1/2"></div>
            </div>
            <div class="card p-6 space-y-3">
                <div class="skeleton h-4 w-2/3"></div>
                <div class="skeleton h-10 w-1/3"></div>
                <div class="skeleton h-3 w-1/2"></div>
            </div>
        </div>
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="skeleton h-48 w-full"></div>
        </div>
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="skeleton h-64 w-full"></div>
        </div>
    </div>

    {{-- User Acceptability skeleton --}}
    <div id="tab-ua" class="tab-panel space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @for ($i = 0; $i < 6; $i++)
            <div class="card p-4 space-y-2">
                <div class="skeleton h-3 w-2/3"></div>
                <div class="skeleton h-8 w-1/2"></div>
            </div>
            @endfor
        </div>
        <div class="card p-6 space-y-3">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="skeleton h-48 w-full"></div>
        </div>
    </div>

    {{-- Adverse Events skeleton --}}
    <div id="tab-ae" class="tab-panel space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            @for ($i = 0; $i < 4; $i++)
            <div class="card p-5 space-y-2">
                <div class="skeleton h-3 w-3/4"></div>
                <div class="skeleton h-10 w-1/2"></div>
            </div>
            @endfor
        </div>
        <div class="card p-6 space-y-3">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="skeleton h-56 w-full"></div>
        </div>
    </div>

    {{-- Suivi Cohorte A skeleton --}}
    <div id="tab-suivi_a" class="tab-panel">
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/3"></div>
            <div class="skeleton h-32 w-full"></div>
            <div class="skeleton h-48 w-full"></div>
        </div>
    </div>

    {{-- Suivi Cohorte B skeleton --}}
    <div id="tab-suivi" class="tab-panel">
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/3"></div>
            <div class="skeleton h-32 w-full"></div>
            <div class="skeleton h-48 w-full"></div>
        </div>
    </div>

    {{-- Rapport skeleton --}}
    <div id="tab-rapport" class="tab-panel space-y-6">
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="skeleton h-6 w-1/2"></div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="skeleton h-10 w-full"></div>
                <div class="skeleton h-10 w-full"></div>
                <div class="skeleton h-24 w-full"></div>
            </div>
            <div class="skeleton h-12 w-48"></div>
        </div>
    </div>

    {{-- Distribution skeleton --}}
    <div id="tab-distribution" class="tab-panel space-y-6">
        <div class="card p-6 space-y-4">
            <div class="skeleton h-4 w-1/4"></div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="skeleton h-10 w-full"></div>
                <div class="skeleton h-10 w-full"></div>
                <div class="skeleton h-10 w-full"></div>
                <div class="skeleton h-10 w-full"></div>
            </div>
            <div class="skeleton h-64 w-full"></div>
        </div>
    </div>

</div>{{-- /max-w --}}

<footer class="text-center text-xs text-gray-400 py-6 mt-2">
    AIRID · ENDURE-Net · REDCap #{{ config('redcap.project_id') }} · Dassa-Zoumè, Bénin
</footer>

{{-- Loading indicator --}}
<div id="load-status" class="fixed bottom-4 right-4 bg-white shadow-lg rounded-xl px-4 py-3 text-sm text-gray-600 border border-gray-200 z-50 flex items-center gap-2">
    <svg class="animate-spin w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
    </svg>
    Chargement <span id="load-counter">0/6</span> onglets…
</div>

<script>
const RED  = '#C41230';
const GRAY = '#374151';

// Re-execute <script> tags inside injected HTML
function injectHTML(el, html) {
    el.innerHTML = html;
    el.querySelectorAll('script').forEach(old => {
        const s = document.createElement('script');
        [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
        s.textContent = old.textContent;
        old.parentNode?.replaceChild(s, old);
    });
}

function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    btn?.classList.add('active');
    window._lastTab = name;
}

const SECTIONS = ['baseline', 'ua', 'ae', 'followup', 'rapport', 'distribution'];
let loaded = 0;
const total = SECTIONS.length;

SECTIONS.forEach(tab => {
    fetch(`/dashboard/section/${tab}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
        const ids = tab === 'followup' ? ['suivi_a', 'suivi'] : [tab];
        const parser = new DOMParser();
        const doc    = parser.parseFromString(html, 'text/html');
        ids.forEach(id => {
            const src  = doc.getElementById('tab-' + id);
            const dest = document.getElementById('tab-' + id);
            if (src && dest) injectHTML(dest, src.innerHTML);
        });
        loaded++;
        const counter = document.getElementById('load-counter');
        if (counter) counter.textContent = loaded + '/' + total;
        if (loaded === total) {
            document.getElementById('load-status')?.remove();
        }
        document.querySelectorAll('.tab-btn[data-section="' + tab + '"]')
            .forEach(btn => btn.classList.remove('loading'));
    })
    .catch(() => { loaded++; });
});

// PWA
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(()=>{});
</script>
</body>
</html>
