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
            <button class="tab-btn {{ $loop->first ? 'active' : '' }}"
                    onclick="showTab('{{ $ev['key'] }}', this)">
                {{ $ev['icon'] }} {{ $ev['label'] }}
            </button>
        @endforeach
        <button class="tab-btn" onclick="showTab('rapport', this)">
            &#128202; Rapport
        </button>
        <button class="tab-btn" onclick="showTab('distribution', this)">
            &#129498; Distribution
        </button>
    </div>
</nav>

{{-- ══════════════════ TAB PANELS ══════════════════ --}}
<div class="max-w-screen-2xl mx-auto px-6 py-8">

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-xl text-sm font-medium mb-6">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- ══ TAB 1 : Baseline & Distribution ══ --}}
    <div id="tab-baseline" class="tab-panel active space-y-8">

        @php extract($baseline); @endphp

        {{-- KPI par bras --}}
        @php
            $consentRate  = $totalVisited > 0 ? round($totalConsented / $totalVisited * 100, 1) : 0;
            $totalMembers = $membersByHh->sum();
            $brasMap = $byBras->keyBy(fn($r) => $r['label']);
            $bras1   = $brasMap->get('PERMANET DUAL',  ['total'=>0,'consented'=>0]);
            $bras2   = $brasMap->get('INTERCEPTOR G2', ['total'=>0,'consented'=>0]);
            $rate1   = $bras1['total'] > 0 ? round($bras1['consented'] / $bras1['total'] * 100, 1) : 0;
            $rate2   = $bras2['total'] > 0 ? round($bras2['consented'] / $bras2['total'] * 100, 1) : 0;
        @endphp

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Bras 1 --}}
            <div class="kpi-card card overflow-hidden">
                <div class="h-2.5" style="background:linear-gradient(90deg,#E8192C,#8B0D21)"></div>
                <div class="p-6">
                    <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full badge-red">
                        Bras 1 · PERMANET DUAL
                    </span>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Visités</p>
                            <p class="text-4xl font-black" style="color:var(--red)">{{ $bras1['total'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Consentis</p>
                            <p class="text-4xl font-black text-green-600">{{ $bras1['consented'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Taux</p>
                            <p class="text-4xl font-black text-gray-700">{{ $rate1 }}<span class="text-xl">%</span></p></div>
                    </div>
                    <div class="prog mt-3"><div class="prog-fill" style="width:{{ $rate1 }}%;background:var(--red)"></div></div>
                </div>
            </div>
            {{-- Bras 2 --}}
            <div class="kpi-card card overflow-hidden">
                <div class="h-2.5" style="background:linear-gradient(90deg,#334155,#0F172A)"></div>
                <div class="p-6">
                    <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full badge-gray">
                        Bras 2 · INTERCEPTOR G2
                    </span>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Visités</p>
                            <p class="text-4xl font-black text-gray-800">{{ $bras2['total'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Consentis</p>
                            <p class="text-4xl font-black text-green-600">{{ $bras2['consented'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Taux</p>
                            <p class="text-4xl font-black text-gray-700">{{ $rate2 }}<span class="text-xl">%</span></p></div>
                    </div>
                    <div class="prog mt-3"><div class="prog-fill" style="width:{{ $rate2 }}%;background:#374151"></div></div>
                </div>
            </div>
        </section>

        {{-- KPI globaux --}}
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            <div class="kpi-card card p-5" style="border-top:4px solid #C41230">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Total visités</p>
                <p class="text-4xl font-extrabold" style="color:#C41230">{{ $totalVisited }}</p>
                <p class="text-xs text-gray-400 mt-1">Global tous bras</p>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #16A34A">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Total consentis</p>
                <p class="text-4xl font-extrabold text-green-700">{{ $totalConsented }}</p>
                <p class="text-xs text-gray-400 mt-1">sur {{ $totalVisited }} visités</p>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #2563EB">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Taux global</p>
                <p class="text-4xl font-extrabold text-blue-700">{{ $consentRate }}<span class="text-xl text-gray-400">%</span></p>
                <div class="prog mt-2"><div class="prog-fill" style="width:{{ $consentRate }}%;background:#2563EB"></div></div>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #D97706">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Membres enregistrés</p>
                <p class="text-4xl font-extrabold text-amber-600">{{ $totalMembers }}</p>
                <p class="text-xs text-gray-400 mt-1">tous ménages confondus</p>
            </div>
        </section>

        {{-- Répartitions : Village | Bras | Cohorte × Bras --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Par village --}}
            <div class="card p-6">
                <p class="section-title">Par village</p>
                <canvas id="chartVillage" height="180"></canvas>
                <table class="w-full text-sm mt-3">
                    <thead><tr class="text-xs text-gray-400 uppercase border-b">
                        <th class="pb-1 text-left">Village</th>
                        <th class="pb-1 text-right">Visités</th>
                        <th class="pb-1 text-right">Consentis</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($byVillage as $row)
                            <tr><td class="py-1.5 font-medium">{{ $row['label'] }}</td>
                                <td class="py-1.5 text-right text-gray-500">{{ $row['total'] }}</td>
                                <td class="py-1.5 text-right font-semibold text-green-600">{{ $row['consented'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Par bras (chart) --}}
            <div class="card p-6">
                <p class="section-title">Par bras d'étude</p>
                <canvas id="chartBras" height="180"></canvas>
                <div class="mt-3 space-y-2">
                    @foreach ($byBras as $row)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-700">{{ $row['label'] }}</p>
                                <p class="text-xs text-gray-400">{{ $row['consented'] }} consentis</p>
                            </div>
                            <p class="text-2xl font-black" style="color:var(--red)">{{ $row['total'] }}</p>
                        </div>
                        @if(!$loop->last)<hr class="border-gray-100">@endif
                    @endforeach
                    @if ($byBras->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-4 italic">Données non disponibles</p>
                    @endif
                </div>
            </div>

            {{-- Cohorte × Bras --}}
            <div class="card p-6">
                <p class="section-title">Cohortes par bras</p>
                <div class="space-y-4">
                    @foreach ($cohortByBras as $brasRow)
                        <div class="rounded-lg border border-gray-100 overflow-hidden">
                            <div class="px-3 py-2 flex items-center justify-between"
                                 style="{{ $brasRow['label'] === 'PERMANET DUAL' ? 'background:var(--red-lite)' : 'background:#F3F4F6' }}">
                                <span class="text-xs font-bold uppercase tracking-wide"
                                      style="{{ $brasRow['label'] === 'PERMANET DUAL' ? 'color:var(--red)' : 'color:#374151' }}">
                                    {{ $brasRow['label'] }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    <span class="font-semibold text-gray-700">{{ $brasRow['consented'] }}</span> consentis
                                    <span class="text-gray-400">/ {{ $brasRow['total'] }}</span>
                                </span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                @foreach ($brasRow['cohorts'] as $cohort)
                                    <div class="flex items-center justify-between px-3 py-2">
                                        <div>
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ $cohort['label'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                                {{ $cohort['label'] }}
                                            </span>
                                        </div>
                                        <div class="text-right flex items-center gap-3">
                                            <span class="text-xs text-gray-400">sur {{ $cohort['total'] }}</span>
                                            <span class="text-base font-bold text-gray-800">{{ $cohort['consented'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if ($brasRow['cohorts']->isEmpty())
                                    <p class="text-xs text-gray-400 text-center py-3 italic">Aucune cohorte</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($cohortByBras->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-4 italic">Aucune donnée</p>
                    @endif
                </div>
            </div>

        </section>

        {{-- Grappe + Carte (carte 2/3, grappe 1/3) --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Grappe (1/3) --}}
            <div class="card p-6">
                <p class="section-title">Par grappe</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2 px-2 text-left">Grappe</th>
                            <th class="py-2 px-2 text-right">Vis.</th>
                            <th class="py-2 px-2 text-right">Cons.</th>
                            <th class="py-2 px-2 text-right">%</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($byCluster as $row)
                                @php $r = $row['total'] > 0 ? round($row['consented'] / $row['total'] * 100) : 0; @endphp
                                <tr class="hov-row">
                                    <td class="py-2 px-2 font-medium text-xs text-gray-700">{{ $row['label'] }}</td>
                                    <td class="py-2 px-2 text-right text-gray-500">{{ $row['total'] }}</td>
                                    <td class="py-2 px-2 text-right font-semibold text-green-600">{{ $row['consented'] }}</td>
                                    <td class="py-2 px-2 text-right">
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-semibold
                                            {{ $r>=80?'bg-green-100 text-green-700':($r>=50?'bg-amber-100 text-amber-700':'bg-red-100 text-red-700') }}">
                                            {{ $r }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-400 text-xs italic">Aucune donnée</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Carte (2/3) --}}
            <div class="card p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <p class="section-title mb-0">Répartition spatiale</p>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                        {{ $gpsData->count() }} ménages géolocalisés
                    </span>
                </div>
                <div id="map"></div>
            </div>

        </section>

        {{-- Performance agents : chart tablettes × dates --}}
        <section class="card p-6">
            <p class="section-title">Performance des agents — données envoyées par tablette</p>
            @if ($allDates->count())
                <canvas id="chartTablets" height="90"></canvas>
            @else
                <p class="text-xs text-gray-400 text-center py-8 italic">Aucune donnée de visite enregistrée</p>
            @endif
        </section>

        {{-- Tableau ménages dépliable + recherche --}}
        <section class="card overflow-hidden">
            <details open>
                <summary class="flex items-center justify-between px-6 py-4 bg-white hover:bg-gray-50 transition select-none">
                    <div class="flex items-center gap-3">
                        <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span class="section-title mb-0">Détail par ménage</span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ $householdDetails->count() }} ménages
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">Cliquer pour replier</span>
                </summary>

                <div class="px-6 pb-6">
                    {{-- Barre de filtre --}}
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2 my-4">
                        <div class="relative flex-1 min-w-[180px]">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                            <input id="searchHH" type="text" placeholder="Rechercher un ménage (ID, village, bras, code filet...)"
                                   oninput="filterHH()"
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                                   style="--tw-ring-color:var(--red)">
                        </div>
                        <select id="filterBras" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les bras</option>
                            <option>PERMANET DUAL</option>
                            <option>INTERCEPTOR G2</option>
                        </select>
                        <select id="filterCohort" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Toutes les cohortes</option>
                            <option>Cohorte A</option>
                            <option>Cohorte B</option>
                        </select>
                        <select id="filterHHVillage" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les villages</option>
                            <option>Djigbe</option>
                            <option>Gbonou</option>
                            <option>Miniffi</option>
                        </select>
                        <select id="filterHHGrappe" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Toutes les grappes</option>
                            <option>Grappe 1</option>
                            <option>Grappe 2</option>
                            <option>Grappe 3</option>
                        </select>
                        <select id="filterHHDag" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les DAGs</option>
                            @foreach ($householdDetails->pluck('dag')->filter()->unique()->sort() as $dagOpt)
                                <option value="{{ $dagOpt }}">{{ $dagOpt }}</option>
                            @endforeach
                        </select>
                        <select id="filterConsent" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les ménages</option>
                            <option value="1">Consentis</option>
                            <option value="0">Non consentis</option>
                        </select>
                        <span id="hhCount" class="flex items-center text-xs text-gray-400 whitespace-nowrap px-2">
                            {{ $householdDetails->count() }} lignes
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="hhTable">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                    <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                    <th class="py-2.5 px-3 text-left">Village</th>
                                    <th class="py-2.5 px-3 text-left">Bras</th>
                                    <th class="py-2.5 px-3 text-left">Cohorte</th>
                                    <th class="py-2.5 px-3 text-left">Grappe</th>
                                    <th class="py-2.5 px-3 text-center">Consent.</th>
                                    <th class="py-2.5 px-3 text-right">Membres</th>
                                    <th class="py-2.5 px-3 text-right">Esp. couchage</th>
                                    <th class="py-2.5 px-3 text-right">Anc. filets</th>
                                    <th class="py-2.5 px-3 text-right">Filets étude</th>
                                    <th class="py-2.5 px-3 text-left">Codes</th>
                                    <th class="py-2.5 px-3 text-right">Date visite</th>
                                    <th class="py-2.5 px-3 text-right">Tablette</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($householdDetails as $hh)
                                    <tr class="hov-row transition-colors" data-bras="{{ $hh['bras'] }}" data-village="{{ $hh['village'] }}" data-cohort="{{ $hh['cohort'] }}" data-cluster="{{ $hh['cluster'] }}" data-dag="{{ $hh['dag'] }}" data-consented="{{ $hh['consented'] ? '1' : '0' }}">
                                        <td class="py-2.5 px-3 font-mono text-xs">
                                            <span class="{{ $hh['id_valid'] ? 'text-gray-500' : 'text-red-500' }}">{{ $hh['id'] }}</span>
                                            @if (!$hh['id_valid'])
                                                <span class="ml-1 text-red-400" title="Identifiant invalide — format attendu : VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE">⚠</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-gray-700">{{ $hh['village'] }}</td>
                                        <td class="py-2.5 px-3">
                                            @if ($hh['bras']==='PERMANET DUAL')
                                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold badge-red whitespace-nowrap">PERMANET DUAL</span>
                                            @elseif ($hh['bras']==='INTERCEPTOR G2')
                                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold badge-gray whitespace-nowrap">INTERCEPTOR G2</span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ $hh['cohort']==='Cohorte A'?'badge-amb':'badge-slt' }}">
                                                {{ $hh['cohort'] }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-xs text-gray-500">{{ $hh['cluster'] }}</td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if ($hh['consented'])
                                                <span class="inline-flex items-center gap-1 text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Oui
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs badge-no px-2 py-0.5 rounded-full font-semibold">
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>Non
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-gray-700">{{ $hh['members']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-gray-600">{{ $hh['sleep_spaces'] }}</td>
                                        <td class="py-2.5 px-3 text-right text-gray-600">{{ $hh['old_nets']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right font-black" style="color:var(--red)">{{ $hh['study_nets']?:'—' }}</td>
                                        <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $hh['net_codes']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-xs text-gray-500">{{ $hh['date_visit']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-xs text-gray-500">
                                            {{ $hh['tablette'] ? "T{$hh['tablette']}" : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="13" class="py-10 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        </section>

        {{-- Moustiquaires de l'étude --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Chart nets --}}
            <div class="card p-6">
                <p class="section-title">Filets distribués</p>
                @if ($studyNetsDetail->count())
                    <canvas id="chartNets" height="180"></canvas>

                    {{-- Par bras --}}
                    <div class="mt-3 space-y-1.5">
                        @foreach ($netsByBras as $bras => $count)
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600">{{ $bras }}</span>
                                <span class="text-base font-black" style="color:var(--red)">{{ $count }}</span>
                            </div>
                        @endforeach
                        <hr class="border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-700 uppercase">Total</span>
                            <span class="text-xl font-black text-gray-800">{{ $studyNetsDetail->count() }}</span>
                        </div>
                    </div>

                    {{-- Par DAG --}}
                    @if ($byDag->count())
                        <p class="section-title mt-5 mb-2">Par DAG</p>
                        <div class="space-y-1.5">
                            @foreach ($byDag as $row)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-600">{{ $row['dag'] }}</span>
                                    <span class="text-base font-black text-gray-700">{{ $row['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucune moustiquaire enregistrée</p>
                @endif
            </div>

            {{-- Tableau moustiquaires (dépliable, 2/3) --}}
            <div class="card overflow-hidden lg:col-span-2">
                <details open>
                    <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="section-title mb-0">Détail des moustiquaires de l'étude</span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                                {{ $studyNetsDetail->count() }} filets
                            </span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 my-4">
                            <div class="relative flex-1 min-w-[160px]">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                                <input id="searchNets" type="text" placeholder="Rechercher un code, ménage..."
                                       oninput="filterNets()"
                                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none">
                            </div>
                            <select id="filterNetsBras" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les bras</option>
                                <option>PERMANET DUAL</option>
                                <option>INTERCEPTOR G2</option>
                            </select>
                            <select id="filterNetsVillage" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les villages</option>
                                <option>Djigbe</option>
                                <option>Gbonou</option>
                                <option>Miniffi</option>
                            </select>
                            <select id="filterNetsGrappe" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Toutes les grappes</option>
                                <option>Grappe 1</option>
                                <option>Grappe 2</option>
                                <option>Grappe 3</option>
                            </select>
                            <select id="filterNetsCohort" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Toutes les cohortes</option>
                                <option>Cohorte A</option>
                                <option>Cohorte B</option>
                            </select>
                            <select id="filterNetsDag" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les DAGs</option>
                                @foreach ($byDag as $row)
                                    <option value="{{ $row['dag'] }}">{{ $row['dag'] }}</option>
                                @endforeach
                            </select>
                            <span id="netsCount" class="flex items-center text-xs text-gray-400 whitespace-nowrap px-2">
                                {{ $studyNetsDetail->count() }} filets
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="netsTable">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                        <th class="py-2.5 px-3 text-right">#</th>
                                        <th class="py-2.5 px-3 text-left">Code filet</th>
                                        <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                        <th class="py-2.5 px-3 text-left">Bras</th>
                                        <th class="py-2.5 px-3 text-left">Cohorte</th>
                                        <th class="py-2.5 px-3 text-left">Village</th>
                                        <th class="py-2.5 px-3 text-left">Grappe</th>
                                        <th class="py-2.5 px-3 text-left">DAG</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($studyNetsDetail as $i => $net)
                                        <tr class="hov-row transition-colors" data-bras="{{ $net['bras'] }}" data-village="{{ $net['village'] }}" data-cohort="{{ $net['cohort'] }}" data-cluster="{{ $net['cluster'] }}" data-dag="{{ $net['dag'] }}">
                                            <td class="py-2.5 px-3 text-right text-xs text-gray-400">{{ $i+1 }}</td>
                                            <td class="py-2.5 px-3 font-mono font-bold" style="color:var(--red)">
                                                {{ $net['net_code'] }}
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $net['household_id'] }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($net['bras']==='PERMANET DUAL')
                                                    <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold">PERMANET DUAL</span>
                                                @elseif ($net['bras']==='INTERCEPTOR G2')
                                                    <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold">INTERCEPTOR G2</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ $net['bras'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ $net['cohort']==='Cohorte A'?'badge-amb':'badge-slt' }}">
                                                    {{ $net['cohort'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-gray-600">{{ $net['village'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $net['cluster'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $net['dag'] ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="py-8 text-center text-gray-400 italic">Aucune moustiquaire enregistrée</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </div>

        </section>

    </div>{{-- /tab-baseline --}}

    {{-- ══ TAB 2 : User Acceptability ══ --}}
    <div id="tab-ua" class="tab-panel space-y-6">

        @php
            $uaTotal     = $ua['total']       ?? 0;
            $uaLike      = $ua['like']         ?? 0;
            $uaSatisfied = $ua['satisfied']    ?? 0;
            $uaContinue  = $ua['continueUse']  ?? 0;
            $uaRecommend = $ua['recommend']    ?? 0;
            $uaEasy      = $ua['easyUse']      ?? 0;
            $uaSatDist   = collect($ua['satisfactionDist'] ?? []);
            $uaRows      = collect($ua['rows'] ?? []);

            $pctUA = fn($n) => $uaTotal > 0 ? round($n / $uaTotal * 100, 1) : 0;
        @endphp

        {{-- KPI --}}
        <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="kpi-card card border-t-2 p-4" style="border-color:var(--red)">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Ménages</p>
                <p class="text-3xl font-extrabold" style="color:var(--red)">{{ $uaTotal }}</p>
            </div>
            @foreach ([
                ['Aiment le filet',       $uaLike,      'text-green-600', 'border-green-400'],
                ['Satisfaits (≥ sat.)',    $uaSatisfied, 'text-blue-600',  'border-blue-400'],
                ['Continueraient',         $uaContinue,  'text-teal-600',  'border-teal-400'],
                ['Recommanderaient',       $uaRecommend, 'text-violet-600','border-violet-400'],
                ['Utilisation facile',     $uaEasy,      'text-amber-600', 'border-amber-400'],
            ] as [$lbl, $val, $textCls, $borderCls])
                <div class="kpi-card card border-t-2 p-4 {{ $borderCls }}">
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">{{ $lbl }}</p>
                    <p class="text-3xl font-extrabold {{ $textCls }}">{{ $val }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $pctUA($val) }}%</p>
                </div>
            @endforeach
        </section>

        {{-- Satisfaction dist + table --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="card p-6">
                <p class="section-title">Distribution satisfaction globale</p>
                @if ($uaSatDist->isNotEmpty())
                    <canvas id="chartUASat" height="220"></canvas>
                    <div class="mt-4 space-y-2">
                        @foreach ($uaSatDist as $label => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-bold text-gray-800">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucune donnée</p>
                @endif
            </div>

            <div class="card overflow-hidden lg:col-span-2">
                <details open>
                    <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition select-none">
                        <div class="flex items-center gap-3">
                            <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="section-title mb-0">Détail par ménage — User Acceptability</span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $uaRows->count() }}</span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="relative my-4">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                            <input type="text" placeholder="Rechercher..."
                                   oninput="filterFwTable(this, 'ua')"
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="fwTable-ua">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                        <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                        <th class="py-2.5 px-3 text-left">Bras</th>
                                        <th class="py-2.5 px-3 text-left">Cohorte</th>
                                        <th class="py-2.5 px-3 text-left">Village</th>
                                        <th class="py-2.5 px-3 text-left">Grappe</th>
                                        <th class="py-2.5 px-3 text-center">Aime</th>
                                        <th class="py-2.5 px-3 text-left">Satisfaction</th>
                                        <th class="py-2.5 px-3 text-center">Continuer</th>
                                        <th class="py-2.5 px-3 text-center">Recommande</th>
                                        <th class="py-2.5 px-3 text-center">Facile</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($uaRows as $row)
                                        <tr class="hov-row transition-colors">
                                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $row['id'] }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($row['bras'] === 'PERMANET DUAL')
                                                    <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold">PERMANET DUAL</span>
                                                @elseif ($row['bras'] === 'INTERCEPTOR G2')
                                                    <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold">INTERCEPTOR G2</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ $row['bras'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ $row['cohort'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                                    {{ $row['cohort'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-xs text-gray-600">{{ $row['village'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $row['cluster'] }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['net_like']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['net_like'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-xs">
                                                @php
                                                    $satClass = match($row['satisfaction']) {
                                                        'Très satisfait' => 'badge-ok',
                                                        'Satisfait'      => 'bg-blue-50 text-blue-700',
                                                        'Insatisfait'    => 'badge-amb',
                                                        'Très insatisfait'=> 'badge-no',
                                                        default          => 'badge-gray',
                                                    };
                                                @endphp
                                                <span class="text-xs {{ $satClass }} px-2 py-0.5 rounded-full font-medium">
                                                    {{ $row['satisfaction'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['continue_use']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['continue_use'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['recommend']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['recommend'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['easy_use']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['easy_use'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="py-8 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </div>

        </section>

    </div>{{-- /tab-ua --}}

    {{-- ══ TAB 3 : Adverse Events ══ --}}
    <div id="tab-ae" class="tab-panel space-y-6">

        @php
            $aeTotal     = $ae['total']         ?? 0;
            $aeSymptom   = $ae['anySymptom']     ?? 0;
            $aeContinue  = $ae['continueUse']    ?? 0;
            $aeBedbugs   = $ae['bedbugs']        ?? 0;
            $aeBbOnNet   = $ae['bbOnNet']        ?? 0;
            $aeSymCounts = collect($ae['symptomCounts'] ?? []);
            $aeRows      = collect($ae['rows'] ?? []);

            $pctAE = fn($n) => $aeTotal > 0 ? round($n / $aeTotal * 100, 1) : 0;
        @endphp

        {{-- KPI --}}
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            <div class="kpi-card card border-t-2 p-5" style="border-color:var(--red)">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Ménages enquêtés</p>
                <p class="text-4xl font-extrabold" style="color:var(--red)">{{ $aeTotal }}</p>
            </div>
            <div class="kpi-card card border-t-2 border-amber-500 p-5">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Avec symptôme(s)</p>
                <p class="text-4xl font-extrabold text-amber-600">{{ $aeSymptom }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $pctAE($aeSymptom) }}% des ménages</p>
            </div>
            <div class="kpi-card card border-t-2 border-green-500 p-5">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Continueraient l'ITN</p>
                <p class="text-4xl font-extrabold text-green-600">{{ $aeContinue }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $pctAE($aeContinue) }}% des ménages</p>
            </div>
            <div class="kpi-card card border-t-2 border-rose-400 p-5">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Punaises actuelles</p>
                <p class="text-4xl font-extrabold text-rose-600">{{ $aeBedbugs }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $pctAE($aeBedbugs) }}% des ménages</p>
            </div>
        </section>

        {{-- Symptom chart + table --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="card p-6">
                <p class="section-title">Symptômes signalés (adultes)</p>
                @if ($aeSymCounts->sum('count') > 0)
                    <canvas id="chartAESym" height="280"></canvas>
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucun symptôme signalé</p>
                @endif
                <div class="mt-4 flex items-center justify-between text-sm border-t pt-3">
                    <span class="text-gray-500">Punaises sur filet (jamais)</span>
                    <span class="font-bold" style="color:var(--red)">{{ $aeBbOnNet }}</span>
                </div>
            </div>

            <div class="card overflow-hidden lg:col-span-2">
                <details open>
                    <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition select-none">
                        <div class="flex items-center gap-3">
                            <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="section-title mb-0">Détail par ménage — Adverse Events</span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $aeRows->count() }}</span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="relative my-4">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                            <input type="text" placeholder="Rechercher..."
                                   oninput="filterFwTable(this, 'ae')"
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none">
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="fwTable-ae">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                        <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                        <th class="py-2.5 px-3 text-left">Bras</th>
                                        <th class="py-2.5 px-3 text-left">Cohorte</th>
                                        <th class="py-2.5 px-3 text-left">Village</th>
                                        <th class="py-2.5 px-3 text-left">Grappe</th>
                                        <th class="py-2.5 px-3 text-left">Date</th>
                                        <th class="py-2.5 px-3 text-center">Symptôme</th>
                                        <th class="py-2.5 px-3 text-center">Continue ITN</th>
                                        <th class="py-2.5 px-3 text-center">Punaises</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($aeRows as $row)
                                        <tr class="hov-row transition-colors">
                                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $row['id'] }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($row['bras'] === 'PERMANET DUAL')
                                                    <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold">PERMANET DUAL</span>
                                                @elseif ($row['bras'] === 'INTERCEPTOR G2')
                                                    <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold">INTERCEPTOR G2</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ $row['bras'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ $row['cohort'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                                    {{ $row['cohort'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-xs text-gray-600">{{ $row['village'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $row['cluster'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $row['date'] }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                @if ($row['any_symptom'])
                                                    <span class="text-xs badge-amb px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                                @else
                                                    <span class="text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">Non</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['continue_use'] ? 'badge-ok' : 'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['continue_use'] ? 'Oui' : 'Non' }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                @if ($row['bedbugs'])
                                                    <span class="text-xs badge-no px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                                @else
                                                    <span class="text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">Non</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="py-8 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </div>

        </section>

    </div>{{-- /tab-ae --}}

    {{-- ══ TAB : Suivi Cohorte A (6, 12, 24, 36 mois) ══ --}}
    <div id="tab-suivi_a" class="tab-panel">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden suivi-bloc">

            <div class="flex items-center overflow-x-auto border-b border-gray-100 px-4 gap-0">
                @foreach ([
                    'a-suivi6'  => '6 mois',
                    'a-suivi12' => '12 mois',
                    'a-suivi24' => '24 mois',
                    'a-suivi36' => '36 mois',
                ] as $tpKey => $tpLabel)
                    <button class="suivi-tp-btn {{ $loop->first ? 'active' : '' }}"
                            onclick="showSuiviTab('{{ $tpKey }}', this)">
                        {{ $tpLabel }}
                    </button>
                @endforeach
            </div>

            @foreach ([
                'a-suivi6'  => ['fk' => 'suivi6',  'label' => 'Suivi 6 mois'],
                'a-suivi12' => ['fk' => 'suivi12', 'label' => 'Suivi 12 mois'],
                'a-suivi24' => ['fk' => 'suivi24', 'label' => 'Suivi 24 mois'],
                'a-suivi36' => ['fk' => 'suivi36', 'label' => 'Suivi 36 mois'],
            ] as $tpId => $tp)
                <div id="suivitp-{{ $tpId }}"
                     class="suivi-tp-panel {{ $loop->first ? 'active' : '' }} p-6 space-y-6">
                    @include('partials.tab-followup', [
                        'data'   => $followUp[$tp['fk']] ?? [],
                        'label'  => $tp['label'],
                        'tabKey' => str_replace('-', '_', $tpId),
                        'cohort' => 'A',
                    ])
                </div>
            @endforeach

        </div>

    </div>{{-- /tab-suivi_a --}}

    {{-- ══ TAB : Suivi Cohorte B (6, 12, 18, 24, 36 mois) ══ --}}
    <div id="tab-suivi" class="tab-panel">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden suivi-bloc">

            <div class="flex items-center overflow-x-auto border-b border-gray-100 px-4 gap-0">
                @foreach ([
                    'b-suivi6'  => '6 mois',
                    'b-suivi12' => '12 mois',
                    'b-suivi18' => '18 mois',
                    'b-suivi24' => '24 mois',
                    'b-suivi36' => '36 mois',
                ] as $tpKey => $tpLabel)
                    <button class="suivi-tp-btn {{ $loop->first ? 'active' : '' }}"
                            onclick="showSuiviTab('{{ $tpKey }}', this)">
                        {{ $tpLabel }}
                    </button>
                @endforeach
            </div>

            @foreach ([
                'b-suivi6'  => ['fk' => 'suivi6',  'label' => 'Suivi 6 mois'],
                'b-suivi12' => ['fk' => 'suivi12', 'label' => 'Suivi 12 mois'],
                'b-suivi18' => ['fk' => 'suivi18', 'label' => 'Suivi 18 mois'],
                'b-suivi24' => ['fk' => 'suivi24', 'label' => 'Suivi 24 mois'],
                'b-suivi36' => ['fk' => 'suivi36', 'label' => 'Suivi 36 mois'],
            ] as $tpId => $tp)
                <div id="suivitp-{{ $tpId }}"
                     class="suivi-tp-panel {{ $loop->first ? 'active' : '' }} p-6 space-y-6">
                    @include('partials.tab-followup', [
                        'data'   => $followUp[$tp['fk']] ?? [],
                        'label'  => $tp['label'],
                        'tabKey' => str_replace('-', '_', $tpId),
                        'cohort' => 'B',
                    ])
                </div>
            @endforeach

        </div>

    </div>{{-- /tab-suivi --}}

    {{-- ══ TAB : Rapport d'activités ══ --}}
    <div id="tab-rapport" class="tab-panel space-y-6">

        <div class="card p-6">
            <p class="section-title">Rapport d'activités des binômes</p>
            <p class="text-sm text-gray-500 mb-5">
                Sélectionnez la période et les filtres souhaités, puis téléchargez le rapport PDF.
            </p>
            <form method="GET" action="{{ route('rapport.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                    {{-- Date début --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de début
                        </label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    {{-- Date fin --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de fin
                        </label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    {{-- Tablettes (= binômes) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Tablettes / Binômes
                        </label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, count($reportTablets))) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>

                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            style="background:var(--red)"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le rapport PDF
                    </button>
                    <p class="text-xs text-gray-400">Sans filtre = toutes les données</p>
                </div>
            </form>
        </div>

        {{-- ── Moustiquaires par bras / cohorte ── --}}
        @if ($byBrasCohortNets->count())
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <p class="section-title mb-0">Moustiquaires distribuées par bras et par cohorte</p>
                {{-- Onglets Résumé / Détail --}}
                <div class="flex rounded-lg overflow-hidden border border-gray-200 text-xs font-bold">
                    <button id="nettab-btn-resume" onclick="showNetTab('resume')"
                            class="px-4 py-1.5 transition"
                            style="background:#1A1A1A;color:#fff">
                        Résumé
                    </button>
                    <button id="nettab-btn-detail" onclick="showNetTab('detail')"
                            class="px-4 py-1.5 transition bg-white text-gray-500 hover:bg-gray-50">
                        Détail par village &amp; grappe
                    </button>
                </div>
            </div>

            {{-- ─ Vue Résumé ─ --}}
            <div id="nettab-resume">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide rounded-tl-lg" style="background:#1A1A1A">Bras / Cohorte</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A">Distribuées</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide rounded-tr-lg" style="background:#1A1A1A">Marquées</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalNetsDisplay = $byBrasCohortNets->sum('nets'); $totalMarkedDisplay = $byBrasCohortNets->sum('marked'); @endphp
                            @foreach ($byBrasCohortNets as $bi => $b)
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-2 font-bold text-gray-800">
                                        <span class="inline-block w-2.5 h-2.5 rounded-sm mr-1.5 align-middle" style="background:{{ $bi === 0 ? '#C41230' : '#374151' }}"></span>
                                        {{ $b['bras'] }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-800">{{ $b['nets'] }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-800">{{ $b['marked'] }}</td>
                                </tr>
                                @foreach ($b['cohorts'] as $c)
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <td class="px-3 py-2 pl-8 text-gray-500 text-xs">{{ $c['cohort'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">{{ $c['nets'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">{{ $c['marked'] }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                            <tr style="background:#C41230">
                                <td class="px-3 py-2 text-white font-bold text-xs uppercase">Total général</td>
                                <td class="px-3 py-2 text-right text-white font-bold">{{ $totalNetsDisplay }}</td>
                                <td class="px-3 py-2 text-right text-white font-bold">{{ $totalMarkedDisplay }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─ Vue Détail (pivot : Village/Grappe × Cohortes) ─ --}}
            <div id="nettab-detail" style="display:none">
                <div class="overflow-x-auto">
                    @php
                        $colCount = $allCohorts->count() + 2; // label + cohorts + Total
                        $grandByCohort = [];
                        $grandTotal    = 0;
                        $grandMarkedTot= 0;
                        foreach ($allCohorts as $c) $grandByCohort[$c] = 0;
                    @endphp
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide rounded-tl-lg"
                                    style="background:#1A1A1A">Bras / Village / Grappe</th>
                                @foreach ($allCohorts as $c)
                                <th class="text-right px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide"
                                    style="background:#1A1A1A">{{ $c }}</th>
                                @endforeach
                                <th class="text-right px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide rounded-tr-lg"
                                    style="background:#1A1A1A">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($byBrasCohortDetail as $bi => $b)
                            @php
                                $grandTotal     += $b['total'];
                                $grandMarkedTot += $b['marked'];
                                foreach ($allCohorts as $c)
                                    $grandByCohort[$c] = ($grandByCohort[$c] ?? 0) + ($b['by_cohort'][$c] ?? 0);
                            @endphp
                            {{-- ── Section bras ── --}}
                            <tr>
                                <td colspan="{{ $colCount }}"
                                    class="px-3 py-2 text-center font-bold text-xs uppercase tracking-widest border-b border-gray-200"
                                    style="background:#F1F5F9;color:#1A1A1A">
                                    <span class="inline-block w-2 h-2 rounded-sm mr-1.5 align-middle"
                                          style="background:{{ $bi === 0 ? '#C41230' : '#374151' }}"></span>
                                    {{ $b['bras'] }}
                                </td>
                            </tr>
                            {{-- ── Lignes Village / Grappe ── --}}
                            @foreach ($b['grappes'] as $gi => $g)
                            <tr class="border-b border-gray-100 {{ $gi % 2 === 0 ? '' : 'bg-gray-50' }} hover:bg-red-50 transition-colors">
                                <td class="px-3 py-2 text-gray-700 text-xs">{{ $g['label'] }}</td>
                                @foreach ($allCohorts as $c)
                                <td class="px-3 py-2 text-right text-gray-800 text-xs font-medium">
                                    {{ $g['by_cohort'][$c] ?? 0 }}
                                </td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-xs font-bold text-gray-900">{{ $g['total'] }}</td>
                            </tr>
                            @endforeach
                            {{-- ── Sous-total bras ── --}}
                            <tr style="background:#374151">
                                <td class="px-3 py-2 text-white text-xs font-bold">
                                    Sous-total — {{ $b['bras'] }}
                                </td>
                                @foreach ($allCohorts as $c)
                                <td class="px-3 py-2 text-right text-white text-xs font-bold">{{ $b['by_cohort'][$c] ?? 0 }}</td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-white text-xs font-bold">{{ $b['total'] }}</td>
                            </tr>
                        @endforeach
                        {{-- ── Total général ── --}}
                        <tr style="background:#C41230">
                            <td class="px-3 py-2.5 text-white font-bold text-xs uppercase">Total général</td>
                            @foreach ($allCohorts as $c)
                            <td class="px-3 py-2.5 text-right text-white font-bold text-xs">{{ $grandByCohort[$c] ?? 0 }}</td>
                            @endforeach
                            <td class="px-3 py-2.5 text-right text-white font-bold">{{ $grandTotal }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-400 mt-2">Valeurs = moustiquaires distribuées · Marquées (code saisi) : {{ $grandMarkedTot }} / {{ $grandTotal }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="card p-5" style="background:#EFF6FF;border:1px solid #BFDBFE">
            <p class="text-sm font-semibold text-blue-700 mb-2">Le rapport d'activités PDF contient :</p>
            <ul class="text-sm text-blue-600 space-y-1 list-disc list-inside">
                <li>Activité quotidienne par binôme : ménages visités, enrôlés et moustiquaires distribuées</li>
                <li>Sous-totaux par tablette par jour et totaux de la période</li>
                <li>Résumé cumulatif à ce jour : enrôlements et moustiquaires par bras/cohorte, par village et par grappe</li>
            </ul>
        </div>

        {{-- ── Performance des binômes — graphique interactif ── --}}
        <div class="card p-6">
            <p class="section-title">Performance des binômes — enrôlements journaliers</p>

            @if (!empty($performanceChart['datasets']))
                <canvas id="chartPerformance" height="90" class="mb-6"></canvas>
            @else
                <p class="text-xs text-gray-400 italic mb-4">Aucune donnée disponible.</p>
            @endif

            <hr class="border-gray-100 mb-5">
            <p class="text-sm font-semibold text-gray-600 mb-3">Exporter en PDF</p>
            <form method="POST" id="performancePdfForm" action="{{ route('performance.pdf') }}" target="_blank" class="space-y-5">
                @csrf
                <input type="hidden" name="chart_image" id="perfChartImage">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de début
                        </label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de fin
                        </label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Tablettes / Binômes
                        </label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, count($reportTablets))) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>

                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="button" onclick="downloadPerformancePdf()"
                            style="background:#374151"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Télécharger le rapport de performance PDF
                    </button>
                    <p class="text-xs text-gray-400">Format paysage A4 · courbes journalières par tablette</p>
                </div>
            </form>
        </div>

        <div class="card p-5" style="background:#F0FDF4;border:1px solid #BBF7D0">
            <p class="text-sm font-semibold text-green-700 mb-2">Le rapport de performance PDF contient :</p>
            <ul class="text-sm text-green-600 space-y-1 list-disc list-inside">
                <li>Tableau synthèse : jours d'activité, total enrôlés, moyenne par jour actif et par jour calendaire</li>
                <li>Courbes journalières d'enrôlements par binôme (une couleur par tablette)</li>
            </ul>
        </div>

    </div>{{-- /tab-rapport --}}

    {{-- ══ TAB: Distribution des moustiquaires ══ --}}
    <div id="tab-distribution" class="tab-panel space-y-6">

        {{-- ── Filters ── --}}
        <div class="card p-5">
            <p class="section-title">Distribution des moustiquaires par binôme</p>

            <div class="flex flex-wrap items-end gap-4 mb-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                    <input type="date" id="distFrom"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"
                           onchange="applyDistFilter()">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                    <input type="date" id="distTo"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"
                           onchange="applyDistFilter()">
                </div>
                <button onclick="resetDistFilter()"
                        class="text-xs text-gray-500 underline hover:text-red-600 transition pb-1.5">
                    Réinitialiser
                </button>
                <div id="distSummary" class="text-sm text-gray-500 pb-1.5">
                    <span class="font-bold text-gray-800">{{ $netsByTablet->sum('visited') }}</span> visités ·
                    <span class="font-bold text-gray-800">{{ $netsByTablet->sum('consented') }}</span> enrôlés ·
                    <span id="distTotalNets" class="font-bold text-gray-800">{{ $netsByTablet->sum('total') }}</span> moustiquaires
                    (<span id="distTotalHH">{{ $netsByTablet->sum(fn($t) => count($t['households'])) }}</span> ménages)
                </div>
            </div>

            @if ($netsByTablet->isEmpty())
                <p class="text-xs text-gray-400 italic">Aucune moustiquaire enregistrée.</p>
            @else

            @php
                $distColors = ['#C41230','#374151','#D97706','#16A34A','#7C3AED','#0891B2','#DB2777','#059669','#EA580C','#4338CA','#0D9488','#9333EA'];
            @endphp

            @foreach ($netsByTablet as $dti => $tabletGroup)
            <div class="dist-tablet-section mb-6" data-tablet="{{ $tabletGroup['tablet'] }}">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block w-3 h-3 rounded-sm flex-shrink-0"
                          style="background:{{ $distColors[$dti % count($distColors)] }}"></span>
                    <p class="font-bold text-sm text-gray-800">
                        Tablette {{ $tabletGroup['tablet'] }}
                        <span class="font-normal text-gray-500 text-xs">
                            — {{ $tabletGroup['visited'] }} visité(s)
                            · {{ $tabletGroup['consented'] }} enrôlé(s)
                            · <span class="dist-tablet-nets">{{ $tabletGroup['total'] }}</span> moustiquaire(s)
                            sur <span class="dist-tablet-hh">{{ count($tabletGroup['households']) }}</span> ménage(s)
                        </span>
                    </p>
                </div>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:36%">Code Ménage</th>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:18%">Date visite</th>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A">Codes Moustiquaires</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:10%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tabletGroup['households'] as $hi => $hh)
                            <tr class="dist-hh-row border-b border-gray-100 {{ $hi % 2 === 1 ? 'bg-gray-50' : '' }}"
                                data-date="{{ $hh['date_visit'] }}"
                                data-nets="{{ $hh['total'] }}">
                                <td class="px-3 py-2 font-mono">{{ $hh['household_id'] }}</td>
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $hh['date_visit'] ? \Carbon\Carbon::parse($hh['date_visit'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ $hh['nets']->implode(' · ') }}</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-800">{{ $hh['total'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#C41230">
                                <td colspan="3" class="px-3 py-2 text-white font-bold text-xs">Total Tablette {{ $tabletGroup['tablet'] }}</td>
                                <td class="px-3 py-2 text-right text-white font-bold dist-tablet-total">{{ $tabletGroup['total'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach

            @endif
        </div>

        {{-- ── PDF Export : détail ── --}}
        <div class="card p-5">
            <p class="section-title">Exporter en PDF — Détail par ménage</p>
            <form method="GET" action="{{ route('distribution.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Tablettes / Binômes</label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, $reportTablets->count())) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            style="background:#C41230"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger PDF distribution
                    </button>
                    <p class="text-xs text-gray-400">Format portrait A4 · détail par ménage et par tablette</p>
                </div>
            </form>
        </div>

        {{-- ── PDF Export : résumé ── --}}
        <div class="card p-5" style="background:#F0FDF4;border:1px solid #BBF7D0">
            <p class="section-title" style="color:#166534">Exporter en PDF — Résumé par binôme</p>
            <p class="text-xs text-green-700 mb-4">
                Tableau synthèse par tablette et par date : ménages visités, enrôlés, moustiquaires distribuées et marquées.
            </p>
            <form method="GET" action="{{ route('distribution.resume.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Tablettes / Binômes</label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, $reportTablets->count())) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            style="background:#16A34A"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le résumé PDF
                    </button>
                    <p class="text-xs text-gray-400">Format portrait A4 · une ligne par date et par binôme</p>
                </div>
            </form>
        </div>

    </div>{{-- /tab-distribution --}}

</div>{{-- /max-w --}}

<footer class="text-center text-xs text-gray-400 py-6 mt-2">
    AIRID · ENDURE-Net · REDCap #{{ config('redcap.project_id') }} · Dassa-Zoumè, Bénin
</footer>

{{-- ══════════════════ SCRIPTS ══════════════════ --}}
<script>
// ── Palette ──
const RED      = '#C41230';
const RED_DARK = '#8B0D21';
const RED_LT   = '#F9A8B4';
const GRAY     = '#374151';
const GREEN    = '#16A34A';
const GREEN_LT = '#86EFAC';

// ── Tabs ──
function showTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── Moustiquaires par bras/cohorte : onglets Résumé / Détail ──
function showNetTab(name) {
    ['resume', 'detail'].forEach(t => {
        const panel = document.getElementById('nettab-' + t);
        const btn   = document.getElementById('nettab-btn-' + t);
        if (!panel || !btn) return;
        const active = t === name;
        panel.style.display = active ? '' : 'none';
        btn.style.background = active ? '#1A1A1A' : '#fff';
        btn.style.color      = active ? '#fff'    : '#6B7280';
    });
}

// ── Pagination system ──────────────────────────────────────────────
const _pg = {};

function setupPagination(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    _pg[tableId] = { page: 1, size: 50 };

    const wrap = table.closest('.overflow-x-auto') || table.parentElement;
    const ctrl = document.createElement('div');
    ctrl.id = 'pgctrl-' + tableId;
    ctrl.className = 'flex items-center justify-between mt-2 px-1 text-xs text-gray-500 flex-wrap gap-2';
    ctrl.innerHTML =
        `<label class="flex items-center gap-1.5"><span class="text-gray-400">Lignes&nbsp;:</span>` +
        `<select onchange="setPgSize('${tableId}',+this.value)" class="border border-gray-200 rounded px-1.5 py-0.5 text-xs focus:outline-none">` +
        `<option value="10">10</option><option value="25">25</option>` +
        `<option value="50" selected>50</option><option value="100">100</option>` +
        `<option value="9999">Tout</option></select></label>` +
        `<span class="flex items-center gap-1.5">` +
        `<span id="pginfo-${tableId}" class="tabular-nums text-gray-400"></span>` +
        `<button onclick="pgGo('${tableId}',-1)" id="pgprev-${tableId}" class="px-2 py-0.5 border border-gray-200 rounded hover:bg-gray-50">‹</button>` +
        `<button onclick="pgGo('${tableId}',1)"  id="pgnext-${tableId}" class="px-2 py-0.5 border border-gray-200 rounded hover:bg-gray-50">›</button>` +
        `</span>`;
    wrap.insertAdjacentElement('afterend', ctrl);
    applyPg(tableId);
}

function applyPg(tableId) {
    if (!_pg[tableId]) return;
    const { page, size } = _pg[tableId];
    const allRows = Array.from(document.querySelectorAll(`#${tableId} tbody tr`));
    const visible = allRows.filter(r => !r._searchHidden);
    const total   = visible.length;
    const showAll = size >= 9999;
    const start   = showAll ? 0 : (page - 1) * size;
    const end     = showAll ? total : start + size;

    allRows.forEach(r => { r.style.display = r._searchHidden ? 'none' : ''; });
    if (!showAll) {
        visible.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    }

    const info = document.getElementById('pginfo-' + tableId);
    if (info) {
        const from = total > 0 ? start + 1 : 0;
        const to   = Math.min(end, total);
        info.textContent = total > 0 ? `${from}–${to} / ${total}` : '0 / 0';
    }
    const maxPage = Math.max(1, Math.ceil(total / (showAll ? total || 1 : size)));
    const prev = document.getElementById('pgprev-' + tableId);
    const next = document.getElementById('pgnext-' + tableId);
    if (prev) prev.disabled = page <= 1;
    if (next) next.disabled = page >= maxPage || showAll;
}

function setPgSize(tableId, size) {
    if (!_pg[tableId]) return;
    _pg[tableId].size = size;
    _pg[tableId].page = 1;
    applyPg(tableId);
}

function pgGo(tableId, dir) {
    if (!_pg[tableId]) return;
    const { size } = _pg[tableId];
    const total   = Array.from(document.querySelectorAll(`#${tableId} tbody tr`)).filter(r => !r._searchHidden).length;
    const maxPage = Math.max(1, Math.ceil(total / size));
    _pg[tableId].page = Math.max(1, Math.min(_pg[tableId].page + dir, maxPage));
    applyPg(tableId);
}

// ── Search ménages ──
function filterHH() {
    const q       = document.getElementById('searchHH').value.toLowerCase();
    const bras    = document.getElementById('filterBras').value;
    const coh     = document.getElementById('filterCohort').value;
    const village = document.getElementById('filterHHVillage').value;
    const grappe  = document.getElementById('filterHHGrappe').value;
    const dag     = document.getElementById('filterHHDag').value;
    const consent = document.getElementById('filterConsent').value;
    let count = 0;
    document.querySelectorAll('#hhTable tbody tr').forEach(row => {
        const d = row.dataset;
        const show = row.textContent.toLowerCase().includes(q)
            && (!bras    || d.bras      === bras)
            && (!coh     || d.cohort    === coh)
            && (!village || d.village   === village)
            && (!grappe  || (d.cluster || '').includes(grappe))
            && (!dag     || d.dag       === dag)
            && (!consent || d.consented === consent);
        row._searchHidden = !show;
        if (show) count++;
    });
    document.getElementById('hhCount').textContent = count + ' lignes';
    if (_pg['hhTable']) { _pg['hhTable'].page = 1; applyPg('hhTable'); }
}

// ── Search moustiquaires ──
function filterNets() {
    const q       = document.getElementById('searchNets').value.toLowerCase();
    const bras    = document.getElementById('filterNetsBras').value;
    const village = document.getElementById('filterNetsVillage').value;
    const grappe  = document.getElementById('filterNetsGrappe').value;
    const cohort  = document.getElementById('filterNetsCohort').value;
    const dag     = document.getElementById('filterNetsDag').value;
    let count = 0;
    document.querySelectorAll('#netsTable tbody tr').forEach(row => {
        const d = row.dataset;
        const show = row.textContent.toLowerCase().includes(q)
            && (!bras    || d.bras    === bras)
            && (!village || d.village === village)
            && (!grappe  || (d.cluster || '').includes(grappe))
            && (!cohort  || d.cohort  === cohort)
            && (!dag     || d.dag     === dag);
        row._searchHidden = !show;
        if (show) count++;
    });
    const el = document.getElementById('netsCount');
    if (el) el.textContent = count + ' filets';
    if (_pg['netsTable']) { _pg['netsTable'].page = 1; applyPg('netsTable'); }
}

// ── Chart Village ──
@if ($byVillage->count())
new Chart(document.getElementById('chartVillage'), {
    type: 'bar',
    data: {
        labels: {!! $byVillage->pluck('label')->toJson() !!},
        datasets: [
            { label:'Visités',  data:{!! $byVillage->pluck('total')->toJson() !!},    backgroundColor:RED_LT, borderColor:RED,   borderWidth:1.5, borderRadius:4 },
            { label:'Consentis',data:{!! $byVillage->pluck('consented')->toJson() !!},backgroundColor:GREEN_LT,borderColor:GREEN, borderWidth:1.5, borderRadius:4 }
        ]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});
@endif

// ── Chart Bras ──
@if ($byBras->count())
new Chart(document.getElementById('chartBras'), {
    type:'doughnut',
    data:{ labels:{!! $byBras->pluck('label')->toJson() !!}, datasets:[{data:{!! $byBras->pluck('total')->toJson() !!},backgroundColor:[RED,GRAY],borderWidth:2}] },
    options:{ responsive:true, cutout:'68%', plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}} }
});
@endif

// ── Chart Tablettes × Dates ──
@if ($allDates->count() && $tabletDatasets->count())
new Chart(document.getElementById('chartTablets'), {
    type: 'bar',
    data: {
        labels: {!! $allDates->toJson() !!},
        datasets: {!! $tabletDatasets->map(fn($d) => [
            'label'           => $d['label'],
            'data'            => $d['data'],
            'backgroundColor' => $d['color'],
            'borderColor'     => $d['color'],
            'borderWidth'     => 1,
            'borderRadius'    => 3,
        ])->toJson() !!}
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'bottom', labels:{ boxWidth:12, font:{ size:11 } } },
            tooltip: { mode:'index', intersect:false }
        },
        scales: {
            x: { stacked:false },
            y: { beginAtZero:true, ticks:{ stepSize:1 }, title:{ display:true, text:'Soumissions' } }
        }
    }
});
@endif

// ── Chart Performance (ménages enrôlés / jour / tablette) ──
@if (!empty($performanceChart['datasets']))
new Chart(document.getElementById('chartPerformance'), {
    type: 'line',
    data: {
        labels: {!! json_encode($performanceChart['labels']) !!},
        datasets: {!! json_encode($performanceChart['datasets']) !!},
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    title: (items) => 'Date : ' + items[0].label,
                    label: (item) => ' ' + item.dataset.label + ' : ' + item.parsed.y + ' enrôlé(s)',
                }
            },
        },
        scales: {
            x: {
                ticks: { font: { size: 10 }, maxRotation: 45, autoSkip: true, maxTicksLimit: 30 },
                grid: { color: '#F1F5F9' },
            },
            y: {
                beginAtZero: true,
                ticks: { font: { size: 10 }, stepSize: 1 },
                grid: { color: '#F1F5F9' },
                title: { display: true, text: 'Enrôlés / jour', font: { size: 10 } },
            },
        },
    },
});
@endif

// ── Distribution filter ──
function applyDistFilter() {
    const from = document.getElementById('distFrom').value;
    const to   = document.getElementById('distTo').value;
    let totalNets = 0, totalHH = 0;

    document.querySelectorAll('.dist-tablet-section').forEach(section => {
        let tNets = 0, tHH = 0;
        section.querySelectorAll('.dist-hh-row').forEach(row => {
            const date = row.dataset.date || '';
            const show = (!from || date >= from) && (!to || date <= to);
            row.style.display = show ? '' : 'none';
            if (show) { tNets += parseInt(row.dataset.nets || 0); tHH++; }
        });
        section.style.display = tNets > 0 ? '' : 'none';
        const elNets  = section.querySelector('.dist-tablet-nets');
        const elHH    = section.querySelector('.dist-tablet-hh');
        const elTotal = section.querySelector('.dist-tablet-total');
        if (elNets)  elNets.textContent  = tNets;
        if (elHH)    elHH.textContent    = tHH;
        if (elTotal) elTotal.textContent = tNets;
        totalNets += tNets; totalHH += tHH;
    });

    const elTN = document.getElementById('distTotalNets');
    const elTH = document.getElementById('distTotalHH');
    if (elTN) elTN.textContent = totalNets;
    if (elTH) elTH.textContent = totalHH;
}

function resetDistFilter() {
    document.getElementById('distFrom').value = '';
    document.getElementById('distTo').value   = '';
    applyDistFilter();
}

// ── Performance PDF export (canvas capture) ──
function downloadPerformancePdf() {
    const canvas = document.getElementById('chartPerformance');
    if (canvas) {
        document.getElementById('perfChartImage').value = canvas.toDataURL('image/png');
    }
    document.getElementById('performancePdfForm').submit();
}

// ── Chart Nets ──
@if ($netsByBras->count())
new Chart(document.getElementById('chartNets'), {
    type:'doughnut',
    data:{ labels:{!! $netsByBras->keys()->toJson() !!}, datasets:[{data:{!! $netsByBras->values()->toJson() !!},backgroundColor:[RED,GRAY],borderWidth:2}] },
    options:{ responsive:true, cutout:'60%', plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}} }
});
@endif

// ── Leaflet ──
@php $gpsJson = $gpsData->toJson(); @endphp
(function(){
    const pts = {!! $gpsJson !!};
    if (!pts.length) {
        document.getElementById('map').innerHTML='<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9CA3AF;font-size:.875rem">Aucune coordonnée GPS</div>';
        return;
    }
    const map = L.map('map').setView([pts.reduce((s,p)=>s+p.lat,0)/pts.length, pts.reduce((s,p)=>s+p.lng,0)/pts.length], 14);

    const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri, Maxar, Earthstar Geographics',
        maxZoom: 19, maxNativeZoom: 17,
    });
    const esriLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, maxNativeZoom: 17, opacity: 0.85,
    });

    // Masquer les tuiles en erreur plutôt que d'afficher le placeholder gris
    [esriSat, esriLabels].forEach(layer => {
        layer.on('tileerror', e => { e.tile.style.opacity = '0'; });
    });

    const tileLayers = {
        'OpenStreetMap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap', maxZoom: 19
        }),
        'Satellite (Esri)': esriSat,
        'Satellite + Labels': L.layerGroup([esriSat, esriLabels]),
        'Topographique': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenTopoMap', maxZoom: 17
        }),
        'CartoDB Clair': L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© CartoDB', maxZoom: 19
        }),
        'CartoDB Sombre': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© CartoDB', maxZoom: 19
        }),
    };
    tileLayers['OpenStreetMap'].addTo(map);
    L.control.layers(tileLayers, {}, { position: 'topleft', collapsed: false }).addTo(map);

    const MAP_COLORS = {
        'PERMANET DUAL':  { 'Cohorte A': '#C41230', 'Cohorte B': '#F97316' },
        'INTERCEPTOR G2': { 'Cohorte A': '#374151', 'Cohorte B': '#2563EB' },
    };

    function makeIcon(color) {
        return L.divIcon({
            html: `<div style="background:${color};width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
            iconSize: [12, 12],
            className: ''
        });
    }

    pts.forEach(p => {
        const color = ((MAP_COLORS[p.bras] || {})[p.cohort]) || '#9CA3AF';
        L.marker([p.lat, p.lng], { icon: makeIcon(color) })
         .addTo(map)
         .bindPopup(`<strong>${p.id}</strong><br><small>${p.bras || '—'} · ${p.cohort || '—'}</small><br><small>📍 ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)}</small>`);
    });

    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function() {
        const div = L.DomUtil.create('div', '');
        div.style.cssText = 'background:white;padding:8px 10px;border-radius:8px;font-size:11px;box-shadow:0 2px 8px rgba(0,0,0,.15);line-height:1.8';
        div.innerHTML = [
            '<b style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:3px">Légende</b>',
            '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#C41230;display:inline-block;flex-shrink:0"></span>PERMANET DUAL — Cohorte A</div>',
            '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#F97316;display:inline-block;flex-shrink:0"></span>PERMANET DUAL — Cohorte B</div>',
            '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#374151;display:inline-block;flex-shrink:0"></span>INTERCEPTOR G2 — Cohorte A</div>',
            '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#2563EB;display:inline-block;flex-shrink:0"></span>INTERCEPTOR G2 — Cohorte B</div>',
        ].join('');
        return div;
    };
    legend.addTo(map);

    // Fullscreen control
    const FsControl = L.Control.extend({
        options: { position: 'topright' },
        onAdd() {
            const btn = L.DomUtil.create('button');
            btn.title = 'Plein écran';
            btn.style.cssText = 'width:32px;height:32px;background:white;border:2px solid rgba(0,0,0,.2);border-radius:4px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.12)';
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>';
            L.DomEvent.disableClickPropagation(btn);
            btn.addEventListener('click', () => {
                const el = map.getContainer();
                if (!document.fullscreenElement) {
                    (el.requestFullscreen || el.webkitRequestFullscreen).call(el);
                } else {
                    (document.exitFullscreen || document.webkitExitFullscreen).call(document);
                }
            });
            const exitIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M8 3v3a2 2 0 01-2 2H3m18 0h-3a2 2 0 01-2-2V3m0 18v-3a2 2 0 012-2h3M3 16h3a2 2 0 012 2v3"/></svg>';
            const enterIcon = btn.innerHTML;
            document.addEventListener('fullscreenchange', () => {
                btn.innerHTML = document.fullscreenElement ? exitIcon : enterIcon;
                setTimeout(() => map.invalidateSize(), 100);
            });
            document.addEventListener('webkitfullscreenchange', () => {
                btn.innerHTML = (document.fullscreenElement || document.webkitFullscreenElement) ? exitIcon : enterIcon;
                setTimeout(() => map.invalidateSize(), 100);
            });
            return btn;
        }
    });
    new FsControl().addTo(map);

    if (pts.length > 1) map.fitBounds(L.latLngBounds(pts.map(p => [p.lat, p.lng])).pad(0.15), { maxZoom: 15 });
})();

// ── Inner suivi timepoint tabs (scoped per bloc) ──
function showSuiviTab(key, btn) {
    const bloc = btn.closest('.suivi-bloc');
    bloc.querySelectorAll('.suivi-tp-panel').forEach(p => p.classList.remove('active'));
    bloc.querySelectorAll('.suivi-tp-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('suivitp-' + key)?.classList.add('active');
    btn.classList.add('active');
}

// ── Generic follow-up / UA / AE table search ──
function filterFwTable(input, key) {
    const q   = input.value.toLowerCase();
    const tid = 'fwTable-' + key;
    document.querySelectorAll('#' + tid + ' tbody tr').forEach(row => {
        row._searchHidden = !row.textContent.toLowerCase().includes(q);
    });
    if (_pg[tid]) { _pg[tid].page = 1; applyPg(tid); }
}

// ── Chart UA Satisfaction ──
@if (!empty($ua['satisfactionDist']) && collect($ua['satisfactionDist'])->isNotEmpty())
(function(){
    const el = document.getElementById('chartUASat');
    if (!el) return;
    const labels = {!! collect($ua['satisfactionDist'])->keys()->toJson() !!};
    const data   = {!! collect($ua['satisfactionDist'])->values()->toJson() !!};
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [RED, '#2563EB', '#D97706', '#374151'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
    });
})();
@endif

// ── Chart AE Symptômes ──
@if (!empty($ae['symptomCounts']) && collect($ae['symptomCounts'])->sum('count') > 0)
(function(){
    const el = document.getElementById('chartAESym');
    if (!el) return;
    const syms = {!! collect($ae['symptomCounts'])->toJson() !!};
    new Chart(el, {
        type: 'bar',
        data: {
            labels: syms.map(s => s.label),
            datasets: [{
                label: 'Cas signalés',
                data: syms.map(s => s.count),
                backgroundColor: RED,
                borderColor: RED_DARK,
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
})();
@endif

// ── PWA ──
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(()=>{});

// ── Init paginators ──
['hhTable', 'netsTable'].forEach(setupPagination);
document.querySelectorAll('[id^="fwTable-"]').forEach(t => setupPagination(t.id));
</script>
</body>
</html>
