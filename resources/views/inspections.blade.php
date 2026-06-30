<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections physiques — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        :root { --red: #C41230; --red-dark: #8B0D21; --red-lite: #FDECED; }
        body { background: #F1F5F9; }

        .card { background: #fff; border-radius: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,.09); }
        .kpi-card { transition: transform .15s, box-shadow .15s; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.14); }

        .section-title {
            font-size: .80rem; font-weight: 800; letter-spacing: .07em;
            text-transform: uppercase; color: #1A1A1A;
            border-left: 4px solid var(--red); padding-left: .65rem; margin-bottom: .9rem;
        }

        .hov-row:hover { background: #FDF4F5; }

        tr.hidden-row   { display: none; }
        tr.page-hidden  { display: none; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 3px; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .card { box-shadow: none !important; border: 1px solid #e5e7eb; }
            header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <img src="/icons/icon.svg" alt="AIRID" class="w-8 h-8">
            </div>
            <div>
                <h1 class="text-base font-extrabold leading-tight tracking-wide">Inspections physiques</h1>
                <p class="text-red-200 text-xs mt-0.5">AIRID · ENDURE-Net · KoboToolbox</p>
            </div>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Dashboard
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
            <a href="{{ route('queries') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Queries
            </a>
            <a href="{{ route('rapport.bras') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Rapport bras
            </a>
            @if(auth()->user()->email === 'francis.houeha@airid-africa.com')
            <a href="{{ route('inspections.export') }}"
               class="flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-lg border border-emerald-500 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Export CSV
            </a>
            @endif
            <button onclick="openVisualInspectionModal()"
                    class="flex items-center gap-1.5 text-xs bg-teal-600 hover:bg-teal-700 px-3 py-1.5 rounded-lg border border-teal-500 transition font-medium text-white">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Fiche inspection
            </button>
            <form method="POST" action="{{ route('inspections.refresh') }}">
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
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/20">
                <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->prenom ?: auth()->user()->nom, 0, 1)) }}
                </div>
                <span class="text-xs font-medium leading-tight">
                    {{ trim(auth()->user()->prenom . ' ' . auth()->user()->nom) }}
                </span>
            </div>
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

<div class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ══ KPI CARDS ══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="card kpi-card p-5">
            <p class="text-xs font-700 uppercase tracking-widest text-slate-400 mb-1">Total inspections</p>
            <p class="text-3xl font-extrabold" style="color:var(--red)">{{ number_format($total) }}</p>
            <p class="text-xs text-slate-400 mt-1">soumissions collectées</p>
        </div>

        <div class="card kpi-card p-5">
            <p class="text-xs font-700 uppercase tracking-widest text-slate-400 mb-1">7 derniers jours</p>
            <p class="text-3xl font-extrabold text-emerald-600">{{ number_format($last7days) }}</p>
            <p class="text-xs text-slate-400 mt-1">nouvelles soumissions</p>
        </div>

        <div class="card kpi-card p-5">
            <p class="text-xs font-700 uppercase tracking-widest text-slate-400 mb-1">30 derniers jours</p>
            <p class="text-3xl font-extrabold text-blue-600">{{ number_format($last30days) }}</p>
            <p class="text-xs text-slate-400 mt-1">nouvelles soumissions</p>
        </div>

        <div class="card kpi-card p-5">
            <p class="text-xs font-700 uppercase tracking-widest text-slate-400 mb-1">Enquêteurs</p>
            <p class="text-3xl font-extrabold text-amber-600">{{ count($enumerators) }}</p>
            <p class="text-xs text-slate-400 mt-1">utilisateurs actifs</p>
        </div>

    </div>

    {{-- ══ KPI PAR MARQUE ══ --}}
    @if(!empty($brandCounts))
    <div>
        <p class="section-title">Répartition par marque de moustiquaire</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @php
                $brandColors = ['bg-violet-100 text-violet-700','bg-sky-100 text-sky-700','bg-teal-100 text-teal-700','bg-orange-100 text-orange-700','bg-pink-100 text-pink-700','bg-lime-100 text-lime-700','bg-indigo-100 text-indigo-700','bg-amber-100 text-amber-700'];
                $ci = 0;
            @endphp
            @foreach($brandCounts as $brand => $count)
                @if($brand)
                @php $label = $brandLabels[$brand] ?? $brand; @endphp
                <div class="card kpi-card p-4 cursor-pointer"
                     onclick="applyBrandFilter('{{ $brand }}')"
                     title="Filtrer sur {{ $label }}">
                    <p class="inline-block text-xs font-700 px-2 py-0.5 rounded-full mb-2 {{ $brandColors[$ci % count($brandColors)] }}">
                        {{ $brand }}
                    </p>
                    <p class="text-sm font-semibold text-slate-600 mb-1 leading-tight">{{ $label }}</p>
                    <p class="text-2xl font-extrabold text-slate-800">{{ number_format($count) }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ $total > 0 ? number_format($count / $total * 100, 1) : 0 }}%
                    </p>
                </div>
                @php $ci++; @endphp
                @endif
            @endforeach
            @if(isset($brandCounts['']) || isset($brandCounts[null]))
            <div class="card p-4">
                <p class="inline-block text-xs font-700 px-2 py-0.5 rounded-full mb-2 bg-slate-100 text-slate-500">Non renseigné</p>
                <p class="text-2xl font-extrabold text-slate-400">{{ number_format($brandCounts[''] ?? $brandCounts[null] ?? 0) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">&nbsp;</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ══ GRAPHIQUES COMPARATIFS PMD vs IG2 ══ --}}
    <div class="card overflow-hidden no-print">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <p class="section-title mb-0">Analyse comparative — PMD vs IG2</p>

            {{-- Filtre bras --}}
            <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden text-xs font-semibold">
                <button id="vf-all" onclick="filterViz('ALL')"
                        class="px-3 py-1.5 bg-slate-700 text-white transition">Tous les deux</button>
                <button id="vf-pmd" onclick="filterViz('PMD')"
                        class="px-3 py-1.5 text-violet-700 hover:bg-violet-50 border-l border-slate-200 transition">PMD uniquement</button>
                <button id="vf-ig2" onclick="filterViz('IG2')"
                        class="px-3 py-1.5 text-sky-700 hover:bg-sky-50 border-l border-slate-200 transition">IG2 uniquement</button>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('inspections.charts.excel') }}"
                   class="flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded-lg text-white font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    Exporter Excel
                </a>
                <button onclick="printCharts()"
                        class="flex items-center gap-1.5 text-xs bg-teal-600 hover:bg-teal-700 px-3 py-1.5 rounded-lg text-white font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimer les graphiques
                </button>
                <button onclick="this.closest('.card').querySelector('#charts-grid').classList.toggle('hidden');this.querySelector('svg').classList.toggle('rotate-180')"
                        class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1.5 transition-colors border border-slate-200 px-3 py-1.5 rounded-lg hover:bg-slate-50">
                    <svg class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    Réduire / Développer
                </button>
            </div>
        </div>
        <div id="charts-grid" class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

            {{-- Chart 1 : Intégrité du tissu --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">Intégrité du tissu</p>
                <div class="relative" style="height:210px"><canvas id="chart-fabric"></canvas></div>
            </div>

            {{-- Chart 2 : Trous par taille --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">Trous par taille (nb cumulé)</p>
                <div class="relative" style="height:210px"><canvas id="chart-taille"></canvas></div>
            </div>

            {{-- Chart 3 : Couleur --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">Couleur</p>
                <div class="relative" style="height:210px"><canvas id="chart-colour"></canvas></div>
            </div>

            {{-- Chart 4 : Symétrie --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">Symétrie</p>
                <div class="relative" style="height:210px"><canvas id="chart-symmetry"></canvas></div>
            </div>

            {{-- Chart 5 : Tache --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">Présence de tache</p>
                <div class="relative" style="height:210px"><canvas id="chart-tache"></canvas></div>
            </div>

            {{-- Chart 6 : État général 1-5 (stacked horizontal) --}}
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="section-title">État général (1–5)</p>
                <div class="relative" style="height:210px"><canvas id="chart-overall"></canvas></div>
            </div>

        </div>
    </div>

    {{-- ══ FILTRES ══ --}}
    <div class="card p-4 no-print">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Recherche</label>
                <input type="text" id="search-input" placeholder="ID, initiales, valeur…"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2"
                       oninput="filterTable()">
            </div>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Enquêteur (initiales)</label>
                <select id="filter-enumerator" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="filterTable()">
                    <option value="">Tous</option>
                    @foreach($enumerators as $enum)
                        <option value="{{ strtolower($enum) }}">{{ $enum }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Marque moustiquaire</label>
                <select id="filter-brand" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="refreshLotFilter(this.value); filterTable()">
                    <option value="">Toutes</option>
                    @foreach($brands as $brand)
                        <option value="{{ strtolower($brand) }}">{{ $brandLabels[$brand] ?? $brand }} ({{ $brand }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Lieu entrepôt</label>
                <select id="filter-lieu" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="filterTable()">
                    <option value="">Tous</option>
                    @foreach($lieux as $lieu)
                        <option value="{{ strtolower($lieu) }}">{{ $lieu }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">moustiquaires/lot_number</label>
                <select id="filter-lot" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="filterTable()">
                    <option value="">Tous</option>
                    @foreach($lots as $lot)
                        <option value="{{ strtolower($lot) }}">{{ $lot }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Mapping marque → lots pour le JS --}}
            <script>
                const BRAND_LOTS = @json(
                    collect($brandLots)->mapWithKeys(fn($lots, $brand) => [
                        strtolower($brand) => collect($lots)->map(fn($l) => strtolower($l))->toArray()
                    ])->toArray()
                );
                const ALL_LOTS = @json(collect($lots)->map(fn($l) => ['value' => strtolower($l), 'label' => $l])->toArray());
            </script>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Depuis</label>
                <input type="date" id="filter-date-from" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="filterTable()">
            </div>
            <div>
                <label class="block text-xs font-700 text-slate-500 uppercase tracking-wider mb-1">Jusqu'au</label>
                <input type="date" id="filter-date-to" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none" onchange="filterTable()">
            </div>
            <button onclick="resetFilters()"
                    class="px-4 py-2 rounded-lg text-sm font-600 border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
                Réinitialiser
            </button>
            <span id="result-count" class="text-xs text-slate-400 self-center"></span>
        </div>
    </div>

    {{-- ══ TABLEAU ══ --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <p class="section-title mb-0">Liste des inspections</p>
            <span class="text-xs text-slate-400">Cache 30 min · KoboToolbox UID : {{ config('kobotoolbox.form_uid') }}</span>
        </div>

        @if(empty($rows))
            <div class="py-16 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-semibold">Aucune soumission trouvée</p>
                <p class="text-sm mt-1">Vérifiez la connexion à KoboToolbox ou actualisez le cache.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs" id="inspections-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-4 py-3 text-left font-800 uppercase tracking-wider text-slate-500 whitespace-nowrap">_id</th>
                            <th class="px-4 py-3 text-left font-800 uppercase tracking-wider text-slate-500 whitespace-nowrap">initiales</th>
                            <th class="px-4 py-3 text-left font-800 uppercase tracking-wider text-slate-500 whitespace-nowrap">itn_brand</th>
                            @foreach($displayColumns as $col)
                                <th class="px-4 py-3 text-left font-800 uppercase tracking-wider text-slate-500 whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-left font-800 uppercase tracking-wider text-slate-500 whitespace-nowrap">_geolocation</th>
                        </tr>
                    </thead>
                    <tbody id="inspections-body">
                        @foreach($rows as $row)
                            @php
                                $brandLabel = $brandLabels[$row['itn_brand'] ?? ''] ?? ($row['itn_brand'] ?? '');
                                $brandBadge = match($row['itn_brand'] ?? '') {
                                    'PMD'   => 'bg-violet-100 text-violet-700',
                                    'IG2'   => 'bg-sky-100 text-sky-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp
                            <tr class="hov-row border-b border-slate-50 transition"
                                data-enumerator="{{ strtolower($row['initiales']) }}"
                                data-brand="{{ strtolower($row['itn_brand'] ?? '') }}"
                                data-lieu="{{ strtolower($row['lieu_entrepot'] ?? '') }}"
                                data-lot="{{ strtolower($row['lot_number'] ?? '') }}"
                                data-date="{{ $row['date_for_filter'] }}"
                                data-search="{{ strtolower($row['id'] . ' ' . $row['initiales'] . ' ' . ($row['itn_brand'] ?? '') . ' ' . $brandLabel . ' ' . implode(' ', array_values($row['data']))) }}">
                                <td class="px-4 py-2.5 font-mono text-slate-400 whitespace-nowrap">{{ $row['id'] }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ $row['initiales'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if($row['itn_brand'])
                                        <span class="inline-block text-xs font-700 px-2 py-0.5 rounded-full {{ $brandBadge }}" title="{{ $brandLabel }}">
                                            {{ $row['itn_brand'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                @foreach($displayColumns as $col)
                                    <td class="px-4 py-2.5 text-slate-700 max-w-xs truncate" title="{{ $row['data'][$col] ?? '' }}">
                                        @php $val = $row['data'][$col] ?? ''; @endphp
                                        @if($val === '' || $val === null)
                                            <span class="text-slate-300">—</span>
                                        @elseif(in_array(strtolower((string)$val), ['yes','oui','true']))
                                            <span class="inline-block bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                        @elseif(in_array(strtolower((string)$val), ['no','non','false']))
                                            <span class="inline-block bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold">Non</span>
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if(!empty($row['geolocation']) && is_array($row['geolocation']) && count($row['geolocation']) >= 2)
                                        <a href="https://maps.google.com/?q={{ $row['geolocation'][0] }},{{ $row['geolocation'][1] }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ number_format($row['geolocation'][0], 4) }}, {{ number_format($row['geolocation'][1], 4) }}
                                        </a>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-100 no-print">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span id="footer-count" class="text-xs text-slate-400">{{ $total }} soumissions</span>

                    {{-- Contrôles de pagination --}}
                    <div id="pagination-bar" class="flex items-center gap-2">
                        <select id="page-size-select" onchange="changePageSize(this.value)"
                                class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs text-slate-600 focus:outline-none focus:ring-1 focus:ring-slate-300">
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                            <option value="200">200 / page</option>
                            <option value="0">Tout afficher</option>
                        </select>
                        <button id="page-prev" onclick="changePage(-1)" disabled
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition">
                            &#8592;
                        </button>
                        <span id="page-info" class="text-xs text-slate-500 min-w-24 text-center font-medium">Page 1 / 1</span>
                        <button id="page-next" onclick="changePage(1)" disabled
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition">
                            &#8594;
                        </button>
                    </div>

                    <span class="text-xs text-slate-400">Actualisé le {{ now()->format('d/m/Y à H:i') }}</span>
                </div>
            </div>
        @endif
    </div>

</div>

<script>
// ── État de pagination ────────────────────────────────────────────────────────
let _pPage = 1;
let _pSize = 25;

// ── Filtres ───────────────────────────────────────────────────────────────────
function filterTable() {
    const search      = document.getElementById('search-input').value.toLowerCase().trim();
    const enumFilter  = document.getElementById('filter-enumerator').value.toLowerCase();
    const brandFilter = document.getElementById('filter-brand').value.toLowerCase();
    const lieuFilter  = document.getElementById('filter-lieu').value.toLowerCase();
    const lotFilter   = document.getElementById('filter-lot').value.toLowerCase();
    const dateFrom    = document.getElementById('filter-date-from').value;
    const dateTo      = document.getElementById('filter-date-to').value;

    let total = 0;
    document.querySelectorAll('#inspections-body tr').forEach(row => {
        const show =
            (!search      || (row.dataset.search     || '').includes(search))   &&
            (!enumFilter  || (row.dataset.enumerator || '') === enumFilter)      &&
            (!brandFilter || (row.dataset.brand      || '') === brandFilter)     &&
            (!lieuFilter  || (row.dataset.lieu       || '') === lieuFilter)      &&
            (!lotFilter   || (row.dataset.lot        || '') === lotFilter)       &&
            (!dateFrom    || (row.dataset.date       || '') >= dateFrom)         &&
            (!dateTo      || (row.dataset.date       || '') <= dateTo);

        row.classList.toggle('hidden-row', !show);
        row.classList.remove('page-hidden');
        if (show) total++;
    });

    const cnt = document.getElementById('result-count');
    if (cnt) cnt.textContent = total + ' résultat' + (total > 1 ? 's' : '');

    _pPage = 1;
    paginate();
}

// ── Pagination ────────────────────────────────────────────────────────────────
function paginate() {
    const allRows    = Array.from(document.querySelectorAll('#inspections-body tr'));
    const matching   = allRows.filter(r => !r.classList.contains('hidden-row'));
    const total      = matching.length;
    const totalPages = _pSize === 0 ? 1 : Math.max(1, Math.ceil(total / _pSize));

    _pPage = Math.max(1, Math.min(_pPage, totalPages));

    const start = (_pPage - 1) * (_pSize === 0 ? total : _pSize);
    const end   = _pSize === 0 ? total : start + _pSize;

    allRows.forEach(r => r.classList.remove('page-hidden'));
    matching.forEach((r, i) => {
        if (i < start || i >= end) r.classList.add('page-hidden');
    });

    const from = total === 0 ? 0 : start + 1;
    const to   = Math.min(end, total);

    const footerEl  = document.getElementById('footer-count');
    const prevBtn   = document.getElementById('page-prev');
    const nextBtn   = document.getElementById('page-next');
    const pageInfo  = document.getElementById('page-info');

    if (footerEl) footerEl.textContent  = `${from}–${to} sur ${total} soumission${total !== 1 ? 's' : ''}`;
    if (prevBtn)  prevBtn.disabled      = _pPage <= 1;
    if (nextBtn)  nextBtn.disabled      = _pPage >= totalPages;
    if (pageInfo) pageInfo.textContent  = _pSize === 0
        ? `Tout (${total})`
        : `Page ${_pPage} / ${totalPages}`;
}

function changePage(delta) {
    _pPage += delta;
    paginate();
}

function changePageSize(val) {
    _pSize = parseInt(val);
    _pPage = 1;
    paginate();
}

// ── Filtres lot/marque ────────────────────────────────────────────────────────
function refreshLotFilter(brandValue) {
    const sel        = document.getElementById('filter-lot');
    const currentLot = sel.value;
    const validLots  = brandValue ? (BRAND_LOTS[brandValue] || []) : null;

    sel.innerHTML = '<option value="">Tous</option>';
    ALL_LOTS.forEach(({ value, label }) => {
        if (!validLots || validLots.includes(value)) {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;
            sel.appendChild(opt);
        }
    });
    sel.value = (validLots === null || validLots.includes(currentLot)) ? currentLot : '';
}

function applyBrandFilter(brand) {
    const sel = document.getElementById('filter-brand');
    if (sel) {
        sel.value = brand.toLowerCase();
        refreshLotFilter(sel.value);
        filterTable();
        sel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function resetFilters() {
    document.getElementById('search-input').value      = '';
    document.getElementById('filter-enumerator').value = '';
    document.getElementById('filter-brand').value      = '';
    refreshLotFilter('');
    document.getElementById('filter-lieu').value       = '';
    document.getElementById('filter-lot').value        = '';
    document.getElementById('filter-date-from').value  = '';
    document.getElementById('filter-date-to').value    = '';
    filterTable();
}

// ── Impression des graphiques + interprétations ──────────────────────────────
function printCharts() {
    const IDS    = ['chart-fabric','chart-taille','chart-colour','chart-symmetry','chart-tache','chart-overall'];
    const TITLES = ['Intégrité du tissu','Trous par taille (nb cumulé)','Couleur','Symétrie','Présence de tache','État général (1–5)'];

    const cards = IDS.map((id, i) => {
        const canvas = document.getElementById(id);
        if (!canvas) return '';
        const imgSrc = canvas.toDataURL('image/png');
        // Récupère l'interprétation injectée par writeInterpretations()
        const interpEl = canvas.closest('.bg-slate-50')?.querySelector('.chart-interp');
        const interp   = interpEl ? interpEl.textContent.trim() : '';
        return (
            '<div class="box">' +
                '<p class="title">' + TITLES[i] + '</p>' +
                '<img src="' + imgSrc + '">' +
                (interp ? '<p class="interp">' + interp + '</p>' : '') +
            '</div>'
        );
    }).join('');

    const now = new Date().toLocaleDateString('fr-FR', { day:'2-digit', month:'long', year:'numeric' });

    const win = window.open('', '_blank', 'width=1200,height=850');
    win.document.write(
        '<!DOCTYPE html><html lang="fr"><head>' +
        '<meta charset="UTF-8">' +
        '<title>Graphiques comparatifs PMD vs IG2 — ENDURE-Net</title>' +
        '<style>' +
            'body{font-family:Arial,sans-serif;padding:24px;background:#fff;margin:0;color:#1e293b;}' +
            /* En-tête */
            '.header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px;' +
                'border-bottom:2px solid #e5e7eb;padding-bottom:12px;}' +
            '.header-left h1{font-size:14px;font-weight:bold;border-left:4px solid #C41230;' +
                'padding-left:10px;margin:0 0 4px;}' +
            '.header-left p{font-size:10px;color:#64748b;margin:0 0 0 14px;}' +
            '.header-right{font-size:9px;color:#94a3b8;text-align:right;white-space:nowrap;}' +
            /* Grille */
            '.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}' +
            /* Carte */
            '.box{border:1px solid #e2e8f0;border-radius:8px;padding:12px;break-inside:avoid;}' +
            '.title{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;' +
                'color:#64748b;margin:0 0 8px;padding-bottom:5px;border-bottom:1px solid #f1f5f9;}' +
            '.box img{width:100%;height:auto;display:block;border-radius:4px;}' +
            /* Interprétation */
            '.interp{font-size:9px;color:#475569;line-height:1.55;margin:8px 0 0;' +
                'padding:7px 8px;background:#f8fafc;border-radius:5px;' +
                'border-left:3px solid #009688;}' +
            /* Impression */
            '@page{size:A4 landscape;margin:1.2cm;}' +
            '@media print{body{padding:0;}.box{page-break-inside:avoid;}}' +
        '</style></head><body>' +
        '<div class="header">' +
            '<div class="header-left">' +
                '<h1>Analyse comparative — PMD (PermaNet Dual) vs IG2 (Interceptor G2)</h1>' +
                '<p>ENDURE-Net · AIRID · Inspections physiques des moustiquaires</p>' +
            '</div>' +
            '<div class="header-right">Généré le ' + now + '</div>' +
        '</div>' +
        '<div class="grid">' + cards + '</div>' +
        '<scr' + 'ipt>window.onload=function(){setTimeout(function(){window.print();},500);};<\/scr' + 'ipt>' +
        '</body></html>'
    );
    win.document.close();
}

// ── Initialisation ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    paginate();
});
</script>


{{-- ══ MODAL FICHE INSPECTION VISUELLE ══ --}}
<div id="visual-inspection-modal"
     class="hidden fixed inset-0 z-[9999] bg-black/60 flex items-start justify-center overflow-y-auto py-8 px-4"
     onclick="closeVisualInspectionModal(event)">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl" onclick="event.stopPropagation()">

        {{-- En-tête modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 gap-4 flex-wrap">
            <div>
                <h3 class="font-bold text-slate-800 text-sm">Fiche d'inspection visuelle — contrôle qualité de base</h3>
                <p class="text-xs text-slate-400 mt-0.5">Section 16.1.3 · ENDURE-Net</p>
            </div>
            {{-- Filtre bras (synchronisé avec le filtre des graphiques) --}}
            <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden text-xs font-semibold">
                <button id="tf-all" onclick="filterViz('ALL')"
                        class="px-3 py-1.5 bg-slate-700 text-white transition">Tous les deux</button>
                <button id="tf-pmd" onclick="filterViz('PMD')"
                        class="px-3 py-1.5 text-violet-700 hover:bg-violet-50 border-l border-slate-200 transition">PMD</button>
                <button id="tf-ig2" onclick="filterViz('IG2')"
                        class="px-3 py-1.5 text-sky-700 hover:bg-sky-50 border-l border-slate-200 transition">IG2</button>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="printVisualInspection()"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimer / Exporter PDF
                </button>
                <button onclick="closeVisualInspectionModal()"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Fermer
                </button>
            </div>
        </div>

        {{-- Contenu du tableau --}}
        <div class="p-6 overflow-x-auto" id="visual-inspection-content">
            @php
                $eTxt = [1=>'Excellent', 2=>'Bon', 3=>'Moyen', 4=>'Médiocre', 5=>'Inacceptable'];
                $eClr = [1=>'#15803D',   2=>'#65A30D', 3=>'#B45309', 4=>'#C2410C', 5=>'#DC2626'];
                $td   = 'border:1px solid #d1d5db;padding:5px 7px;vertical-align:top;font-size:11px;';
                $tdK  = 'border:1px solid #d1d5db;padding:5px 7px;vertical-align:top;font-size:11px;font-weight:600;white-space:nowrap;';
                $nPmd = collect($visualInspectionData['PMD'] ?? [])->sum('total');
                $nIg2 = collect($visualInspectionData['IG2'] ?? [])->sum('total');
            @endphp
            <table style="width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:11px;">
                <thead>
                    <tr>
                        <td colspan="8" style="background:#009688;color:#fff;padding:8px 10px;font-weight:bold;border:1px solid #007a6f;font-size:12px;">
                            Table x. Baseline quality check visual inspection results for test and reference items.
                        </td>
                    </tr>
                    <tr>
                        <th rowspan="2" style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;vertical-align:middle;min-width:80px;">Lot<br>number</th>
                        <th rowspan="2" style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;vertical-align:middle;min-width:70px;">Bale<br>number</th>
                        <th rowspan="2" style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;vertical-align:middle;min-width:70px;">Sampling<br>position</th>
                        <th colspan="5" style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:center;">Visual inspection</th>
                    </tr>
                    <tr>
                        <th style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;min-width:140px;">Fabric integrity,<br>including seams</th>
                        <th style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;min-width:100px;">Colour</th>
                        <th style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;min-width:100px;">Symmetry</th>
                        <th style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;min-width:100px;">Tache</th>
                        <th style="background:#009688;color:#fff;border:1px solid #007a6f;padding:6px 8px;text-align:left;min-width:130px;">Overall damage<br>present?</th>
                    </tr>
                </thead>
                <tbody>

                {{-- ══ TEST PRODUCT — PMD (PermaNet Dual) ══ --}}
                <tr class="viz-pmd">
                    <td colspan="8" style="background:#B2DFDB;text-align:center;padding:5px 8px;font-weight:bold;border:1px solid #9ECFCB;letter-spacing:.04em;">
                        Test product — PermaNet Dual (PMD)
                        <span style="font-weight:400;font-size:10px;margin-left:8px;">n = {{ $nPmd }}</span>
                    </td>
                </tr>
                @forelse($visualInspectionData['PMD'] ?? [] as $r)
                <tr class="viz-pmd">
                    <td style="{{ $tdK }}">{{ $r['lot'] ?: '—' }}</td>
                    <td style="{{ $td }}">{{ $r['bale'] ?: '—' }}</td>
                    <td style="{{ $td }}">{{ $r['pos'] ?: '—' }}</td>

                    {{-- Fabric integrity --}}
                    <td style="{{ $td }}">
                        @if($r['fabric']['sans_trous'] > 0)
                            <div style="color:#15803D;">Intactes : <strong>{{ $r['fabric']['sans_trous'] }}</strong></div>
                        @endif
                        @if($r['fabric']['avec_trous'] > 0)
                            <div style="color:#DC2626;">Avec trous : <strong>{{ $r['fabric']['avec_trous'] }}</strong></div>
                            <div style="color:#64748B;font-size:9px;padding-left:4px;">
                                T1:{{ $r['fabric']['taille1'] }} · T2:{{ $r['fabric']['taille2'] }} · T3:{{ $r['fabric']['taille3'] }} · T4:{{ $r['fabric']['taille4'] }}
                            </div>
                        @endif
                        <div style="color:#94A3B8;font-size:9px;border-top:1px dotted #e5e7eb;margin-top:2px;">n={{ $r['total'] }}</div>
                    </td>

                    {{-- Colour --}}
                    <td style="{{ $td }}">
                        <div style="color:#15803D;">Bon : <strong>{{ $r['colour']['bon'] }}</strong></div>
                        @if($r['colour']['decolore'] > 0)
                            <div style="color:#B45309;">Décoloré : <strong>{{ $r['colour']['decolore'] }}</strong></div>
                        @endif
                    </td>

                    {{-- Symmetry --}}
                    <td style="{{ $td }}">
                        @forelse($r['symmetry'] as $val => $cnt)
                            <div>{{ $val }} : <strong>{{ $cnt }}</strong></div>
                        @empty
                            <div style="color:#94A3B8;font-style:italic;">—</div>
                        @endforelse
                    </td>

                    {{-- Tache --}}
                    <td style="{{ $td }}">
                        <div style="color:#15803D;">Sans : <strong>{{ $r['tache']['sans'] }}</strong></div>
                        @if($r['tache']['avec'] > 0)
                            <div style="color:#B45309;">Avec : <strong>{{ $r['tache']['avec'] }}</strong></div>
                        @endif
                    </td>

                    {{-- Overall damage — toutes les modalités 1-5 toujours affichées --}}
                    <td style="{{ $td }}">
                        @foreach($r['overall'] as $code => $cnt)
                            <div style="color:{{ $eClr[$code] }};{{ $cnt == 0 ? 'opacity:.28;' : '' }}line-height:1.7;">
                                <span style="font-size:9px;font-weight:700;display:inline-block;width:12px;text-align:right;margin-right:3px;">{{ $code }}</span>
                                {{ $eTxt[$code] }} : <strong>{{ $cnt }}</strong>
                            </div>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr class="viz-pmd">
                    <td colspan="8" style="{{ $td }}text-align:center;color:#94A3B8;font-style:italic;padding:14px;">
                        Aucune moustiquaire PMD dans le jeu de données
                    </td>
                </tr>
                @endforelse

                {{-- ══ REFERENCE PRODUCT — IG2 (Interceptor G2) ══ --}}
                <tr class="viz-ig2">
                    <td colspan="8" style="background:#B2DFDB;text-align:center;padding:5px 8px;font-weight:bold;border:1px solid #9ECFCB;letter-spacing:.04em;">
                        Reference product — Interceptor G2 (IG2)
                        <span style="font-weight:400;font-size:10px;margin-left:8px;">n = {{ $nIg2 }}</span>
                    </td>
                </tr>
                @forelse($visualInspectionData['IG2'] ?? [] as $r)
                <tr class="viz-ig2">
                    <td style="{{ $tdK }}">{{ $r['lot'] ?: '—' }}</td>
                    <td style="{{ $td }}">{{ $r['bale'] ?: '—' }}</td>
                    <td style="{{ $td }}">{{ $r['pos'] ?: '—' }}</td>

                    {{-- Fabric integrity --}}
                    <td style="{{ $td }}">
                        @if($r['fabric']['sans_trous'] > 0)
                            <div style="color:#15803D;">Intactes : <strong>{{ $r['fabric']['sans_trous'] }}</strong></div>
                        @endif
                        @if($r['fabric']['avec_trous'] > 0)
                            <div style="color:#DC2626;">Avec trous : <strong>{{ $r['fabric']['avec_trous'] }}</strong></div>
                            <div style="color:#64748B;font-size:9px;padding-left:4px;">
                                T1:{{ $r['fabric']['taille1'] }} · T2:{{ $r['fabric']['taille2'] }} · T3:{{ $r['fabric']['taille3'] }} · T4:{{ $r['fabric']['taille4'] }}
                            </div>
                        @endif
                        <div style="color:#94A3B8;font-size:9px;border-top:1px dotted #e5e7eb;margin-top:2px;">n={{ $r['total'] }}</div>
                    </td>

                    {{-- Colour --}}
                    <td style="{{ $td }}">
                        <div style="color:#15803D;">Bon : <strong>{{ $r['colour']['bon'] }}</strong></div>
                        @if($r['colour']['decolore'] > 0)
                            <div style="color:#B45309;">Décoloré : <strong>{{ $r['colour']['decolore'] }}</strong></div>
                        @endif
                    </td>

                    {{-- Symmetry --}}
                    <td style="{{ $td }}">
                        @forelse($r['symmetry'] as $val => $cnt)
                            <div>{{ $val }} : <strong>{{ $cnt }}</strong></div>
                        @empty
                            <div style="color:#94A3B8;font-style:italic;">—</div>
                        @endforelse
                    </td>

                    {{-- Tache --}}
                    <td style="{{ $td }}">
                        <div style="color:#15803D;">Sans : <strong>{{ $r['tache']['sans'] }}</strong></div>
                        @if($r['tache']['avec'] > 0)
                            <div style="color:#B45309;">Avec : <strong>{{ $r['tache']['avec'] }}</strong></div>
                        @endif
                    </td>

                    {{-- Overall damage — toutes les modalités 1-5 toujours affichées --}}
                    <td style="{{ $td }}">
                        @foreach($r['overall'] as $code => $cnt)
                            <div style="color:{{ $eClr[$code] }};{{ $cnt == 0 ? 'opacity:.28;' : '' }}line-height:1.7;">
                                <span style="font-size:9px;font-weight:700;display:inline-block;width:12px;text-align:right;margin-right:3px;">{{ $code }}</span>
                                {{ $eTxt[$code] }} : <strong>{{ $cnt }}</strong>
                            </div>
                        @endforeach
                    </td>
                </tr>
                @empty
                <tr class="viz-ig2">
                    <td colspan="8" style="{{ $td }}text-align:center;color:#94A3B8;font-style:italic;padding:14px;">
                        Aucune moustiquaire IG2 dans le jeu de données
                    </td>
                </tr>
                @endforelse

                {{-- Pied --}}
                <tr>
                    <td colspan="8" style="background:#009688;color:#fff;text-align:center;padding:6px 8px;font-size:10px;font-style:italic;border:1px solid #007a6f;">
                        Données extraites de KoboToolbox · PMD : {{ $nPmd }} moustiquaire(s) · IG2 : {{ $nIg2 }} moustiquaire(s) · * Adapted from (13)
                    </td>
                </tr>

                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openVisualInspectionModal() {
    document.getElementById('visual-inspection-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeVisualInspectionModal(event) {
    if (!event || event.target === document.getElementById('visual-inspection-modal')) {
        document.getElementById('visual-inspection-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function printVisualInspection() {
    const content = document.getElementById('visual-inspection-content').innerHTML;
    const win = window.open('', '_blank', 'width=960,height=720');
    win.document.write(
        '<!DOCTYPE html><html lang="fr"><head>' +
        '<meta charset="UTF-8">' +
        '<title>Fiche d\'inspection visuelle — ENDURE-Net</title>' +
        '<style>' +
            'body{font-family:Arial,sans-serif;padding:24px;background:#fff;margin:0;}' +
            'table{width:100%;border-collapse:collapse;}' +
            'th,td{padding:4px 6px;}' +
            '@page{size:A4 landscape;margin:1.5cm;}' +
            '@media print{body{padding:0;}}' +
        '</style>' +
        '</head><body>' +
        content +
        '<scr' + 'ipt>window.onload=function(){setTimeout(function(){window.print();},400);};<\/scr' + 'ipt>' +
        '</body></html>'
    );
    win.document.close();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeVisualInspectionModal();
});
</script>


<script>
(function initVizCharts() {
    if (typeof Chart === 'undefined') return;

    const VIZ = @json($visualInspectionData);

    // ── Agrégation par bras ──────────────────────────────────────────────────
    function sumBrand(brand) {
        const rows = VIZ[brand] || [];
        const a = {
            fabric_sans:0, fabric_avec:0,
            t1:0, t2:0, t3:0, t4:0,
            bon:0, decolore:0,
            tache_sans:0, tache_avec:0,
            symmetry:{}, overall:{}, total:0,
        };
        rows.forEach(r => {
            a.fabric_sans += r.fabric.sans_trous;
            a.fabric_avec += r.fabric.avec_trous;
            a.t1 += r.fabric.taille1; a.t2 += r.fabric.taille2;
            a.t3 += r.fabric.taille3; a.t4 += r.fabric.taille4;
            a.bon      += r.colour.bon;
            a.decolore += r.colour.decolore;
            a.tache_sans += r.tache.sans;
            a.tache_avec += r.tache.avec;
            Object.entries(r.symmetry).forEach(([k,v]) => { a.symmetry[k] = (a.symmetry[k]||0)+v; });
            Object.entries(r.overall ).forEach(([k,v]) => { a.overall[k]  = (a.overall[k] ||0)+v; });
            a.total += r.total;
        });
        return a;
    }

    const pmd = sumBrand('PMD');
    const ig2 = sumBrand('IG2');

    // ── Couleurs ─────────────────────────────────────────────────────────────
    const PMD_BG = 'rgba(124,58,237,.78)';
    const PMD_BD = '#7C3AED';
    const IG2_BG = 'rgba(2,132,199,.78)';
    const IG2_BD = '#0284C7';

    // ── Options de base (graphiques groupés) ─────────────────────────────────
    const BASE = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position:'top', labels:{ font:{size:10}, boxWidth:12, padding:8 } },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const sum = ctx.dataset.data.reduce((a,b) => a+b, 0);
                        const pct = sum > 0 ? Math.round(ctx.parsed.y / sum * 100) : 0;
                        return ` ${ctx.dataset.label}: ${ctx.parsed.y} (${pct}%)`;
                    }
                }
            }
        },
        scales: {
            x: { grid:{display:false}, ticks:{font:{size:10}} },
            y: { beginAtZero:true, ticks:{font:{size:10},stepSize:1}, grid:{color:'rgba(0,0,0,.04)'} },
        },
    };

    function mkDS(label, data, bg, bd) {
        return { label, data, backgroundColor:bg, borderColor:bd, borderWidth:1, borderRadius:4, borderSkipped:false };
    }

    // Stockage des instances Chart.js pour le filtre
    const _charts = {};

    function mkChart(id, labels, pmdData, ig2Data, extraOpts={}) {
        const el = document.getElementById(id);
        if (!el) return null;
        const chart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    mkDS('PMD — PermaNet Dual', pmdData, PMD_BG, PMD_BD),
                    mkDS('IG2 — Interceptor G2', ig2Data, IG2_BG, IG2_BD),
                ],
            },
            options: Object.assign({}, BASE, extraOpts),
        });
        _charts[id] = chart;
        return chart;
    }

    // ── 1 · Intégrité du tissu ───────────────────────────────────────────────
    mkChart('chart-fabric',
        ['Intactes', 'Avec trous'],
        [pmd.fabric_sans, pmd.fabric_avec],
        [ig2.fabric_sans, ig2.fabric_avec]
    );

    // ── 2 · Distribution des trous par taille ────────────────────────────────
    mkChart('chart-taille',
        ['Taille 1', 'Taille 2', 'Taille 3', 'Taille 4'],
        [pmd.t1, pmd.t2, pmd.t3, pmd.t4],
        [ig2.t1, ig2.t2, ig2.t3, ig2.t4]
    );

    // ── 3 · Couleur ──────────────────────────────────────────────────────────
    mkChart('chart-colour',
        ['Bon (intensité)', 'Décoloré'],
        [pmd.bon, pmd.decolore],
        [ig2.bon, ig2.decolore]
    );

    // ── 4 · Symétrie ─────────────────────────────────────────────────────────
    const symKeys = [...new Set([
        ...Object.keys(pmd.symmetry),
        ...Object.keys(ig2.symmetry),
    ])].sort();
    mkChart('chart-symmetry',
        symKeys,
        symKeys.map(k => pmd.symmetry[k] || 0),
        symKeys.map(k => ig2.symmetry[k] || 0)
    );

    // ── 5 · Présence de tache ────────────────────────────────────────────────
    mkChart('chart-tache',
        ['Sans tache', 'Avec tache'],
        [pmd.tache_sans, pmd.tache_avec],
        [ig2.tache_sans, ig2.tache_avec]
    );

    // ── 6 · État général — barres 100 % normalisées ──────────────────────────
    // Variables accessibles depuis filterViz (même scope IIFE)
    const ETAT_LABELS = ['1 — Excellent','2 — Bon','3 — Moyen','4 — Médiocre','5 — Inacceptable'];
    const ETAT_BG     = ['#15803D','#65A30D','#B45309','#C2410C','#DC2626'];
    const pmdTot = [1,2,3,4,5].reduce((s,c) => s + (pmd.overall[c]||0), 0) || 1;
    const ig2Tot = [1,2,3,4,5].reduce((s,c) => s + (ig2.overall[c]||0), 0) || 1;
    const rawPmd = [1,2,3,4,5].map(c => pmd.overall[c] || 0);
    const rawIg2 = [1,2,3,4,5].map(c => ig2.overall[c] || 0);
    const pctPmd = rawPmd.map(v => parseFloat((v / pmdTot * 100).toFixed(1)));
    const pctIg2 = rawIg2.map(v => parseFloat((v / ig2Tot * 100).toFixed(1)));

    const el6 = document.getElementById('chart-overall');
    if (el6) {
        _charts['chart-overall'] = new Chart(el6, {
            type: 'bar',
            data: {
                labels: [
                    'PMD — PermaNet Dual  (n=' + pmdTot + ')',
                    'IG2 — Interceptor G2  (n=' + ig2Tot + ')',
                ],
                datasets: [0,1,2,3,4].map(i => ({
                    label: ETAT_LABELS[i],
                    data:  [pctPmd[i], pctIg2[i]],
                    backgroundColor: ETAT_BG[i] + 'DD',
                    borderWidth: 0,
                    borderRadius: i === 4 ? 3 : 0,
                    borderSkipped: false,
                })),
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        min: 0, max: 100,
                        ticks: {
                            font: { size:10 },
                            callback: v => v + ' %',
                            stepSize: 25,
                        },
                        grid: { color:'rgba(0,0,0,.05)' },
                    },
                    y: {
                        stacked: true,
                        ticks: { font:{ size:10, weight:'600' } },
                        grid: { display:false },
                    },
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font:{size:9}, boxWidth:12, padding:7 },
                    },
                    tooltip: {
                        callbacks: {
                            // Titre : bras + total
                            title: ctx => ctx[0].label,
                            // Ligne : état + compte brut + %
                            label: ctx => {
                                const stateIdx = ctx.datasetIndex;
                                const brandIdx = ctx.dataIndex;
                                const raw = brandIdx === 0 ? rawPmd[stateIdx] : rawIg2[stateIdx];
                                const pct = ctx.parsed.x.toFixed(1);
                                return raw > 0
                                    ? ' ' + ETAT_LABELS[stateIdx] + ' : ' + raw + ' (' + pct + ' %)'
                                    : null; // masquer les lignes à 0
                            },
                        },
                        filter: item => item.parsed.x > 0,  // ne pas afficher les 0
                    },
                },
            },
        });
    }

    // ── Interprétations automatiques ─────────────────────────────────────────
    // Injectées sous chaque graphique après création des charts
    (function writeInterpretations() {

        const pctOf = (v, t) => t > 0 ? parseFloat((v / t * 100).toFixed(1)) : 0;
        const fmtPct = (v, t) => pctOf(v, t).toFixed(1) + ' %';

        function addInterp(canvasId, text) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const card = canvas.closest('.bg-slate-50');
            if (!card) return;
            const old = card.querySelector('.chart-interp');
            if (old) old.remove();
            const p = document.createElement('p');
            p.className = 'chart-interp mt-3 text-xs text-slate-500 leading-relaxed border-t border-slate-200 pt-2.5';
            p.textContent = text;
            card.appendChild(p);
        }

        // ── 1. Intégrité du tissu ────────────────────────────────────────────
        {
            const pp = pctOf(pmd.fabric_avec, pmd.total);
            const ip = pctOf(ig2.fabric_avec, ig2.total);
            if (pmd.fabric_avec === 0 && ig2.fabric_avec === 0) {
                addInterp('chart-fabric',
                    'Aucun trou détecté dans les deux bras. L\'intégrité structurelle est satisfaisante pour l\'ensemble des moustiquaires inspectées.');
            } else {
                let txt = `${pp} % des PMD et ${ip} % des IG2 présentent des trous. `;
                if (pp > ip)      txt += 'Les PermaNet Dual montrent une intégrité structurelle plus dégradée.';
                else if (ip > pp) txt += 'Les Interceptor G2 montrent une intégrité structurelle plus dégradée.';
                else              txt += 'Les deux bras présentent un taux de dommages équivalent.';
                addInterp('chart-fabric', txt);
            }
        }

        // ── 2. Trous par taille ──────────────────────────────────────────────
        {
            const totP = pmd.t1 + pmd.t2 + pmd.t3 + pmd.t4;
            const totI = ig2.t1 + ig2.t2 + ig2.t3 + ig2.t4;
            if (totP === 0 && totI === 0) {
                addInterp('chart-taille', 'Aucun trou enregistré dans les deux bras. Toutes les catégories de taille affichent un compte nul.');
            } else {
                const sizes = [
                    { lbl:'Taille 1', n: pmd.t1 + ig2.t1 },
                    { lbl:'Taille 2', n: pmd.t2 + ig2.t2 },
                    { lbl:'Taille 3', n: pmd.t3 + ig2.t3 },
                    { lbl:'Taille 4', n: pmd.t4 + ig2.t4 },
                ].filter(s => s.n > 0);
                const dom = sizes.length ? sizes.reduce((a, b) => b.n > a.n ? b : a) : null;
                let txt = `PMD : ${totP} trou(s) — IG2 : ${totI} trou(s). `;
                if (dom) txt += `${dom.lbl} est la plus représentée (${dom.n} au total).`;
                addInterp('chart-taille', txt);
            }
        }

        // ── 3. Couleur ───────────────────────────────────────────────────────
        {
            const pp = pctOf(pmd.decolore, pmd.total);
            const ip = pctOf(ig2.decolore, ig2.total);
            if (pmd.decolore === 0 && ig2.decolore === 0) {
                addInterp('chart-colour', 'Aucune décoloration observée dans les deux bras. La coloration est conforme pour l\'ensemble des moustiquaires inspectées.');
            } else {
                let txt = `${pp} % des PMD et ${ip} % des IG2 présentent une décoloration. `;
                if (pp > ip)      txt += 'Les PermaNet Dual sont davantage affectées.';
                else if (ip > pp) txt += 'Les Interceptor G2 sont davantage affectées.';
                else              txt += 'Taux de décoloration identique dans les deux bras.';
                addInterp('chart-colour', txt);
            }
        }

        // ── 4. Symétrie ──────────────────────────────────────────────────────
        {
            const getMax = obj => {
                const e = Object.entries(obj).filter(([,v]) => v > 0);
                return e.length ? e.reduce((a, b) => b[1] > a[1] ? b : a) : null;
            };
            const pmdMax = getMax(pmd.symmetry);
            const ig2Max = getMax(ig2.symmetry);
            if (!pmdMax && !ig2Max) {
                addInterp('chart-symmetry', 'Données de symétrie non renseignées ou absentes dans le jeu de données.');
            } else {
                let txt = '';
                if (pmdMax) {
                    const pmdSymTot = Object.values(pmd.symmetry).reduce((a,b)=>a+b,0);
                    txt += `PMD : modalité dominante « ${pmdMax[0]} » — ${pmdMax[1]} (${pctOf(pmdMax[1], pmdSymTot)} %). `;
                }
                if (ig2Max) {
                    const ig2SymTot = Object.values(ig2.symmetry).reduce((a,b)=>a+b,0);
                    txt += `IG2 : modalité dominante « ${ig2Max[0]} » — ${ig2Max[1]} (${pctOf(ig2Max[1], ig2SymTot)} %).`;
                }
                addInterp('chart-symmetry', txt.trim());
            }
        }

        // ── 5. Présence de tache ─────────────────────────────────────────────
        {
            const pp = pctOf(pmd.tache_avec, pmd.total);
            const ip = pctOf(ig2.tache_avec, ig2.total);
            if (pmd.tache_avec === 0 && ig2.tache_avec === 0) {
                addInterp('chart-tache', 'Aucune tache détectée dans les deux bras. L\'aspect visuel des moustiquaires est globalement propre.');
            } else {
                let txt = `${pp} % des PMD et ${ip} % des IG2 présentent des taches. `;
                if (pp > ip)      txt += 'Les PermaNet Dual sont davantage touchées.';
                else if (ip > pp) txt += 'Les Interceptor G2 sont davantage touchées.';
                else              txt += 'Taux de taches identique dans les deux bras.';
                addInterp('chart-tache', txt);
            }
        }

        // ── 6. État général ──────────────────────────────────────────────────
        {
            const pmdTot2 = [1,2,3,4,5].reduce((s,c) => s + (pmd.overall[c]||0), 0) || 1;
            const ig2Tot2 = [1,2,3,4,5].reduce((s,c) => s + (ig2.overall[c]||0), 0) || 1;
            const pmdExcPct  = pctOf(pmd.overall[1] || 0, pmdTot2);
            const ig2ExcPct  = pctOf(ig2.overall[1] || 0, ig2Tot2);
            const pmdBadCnt  = [3,4,5].reduce((s,c) => s + (pmd.overall[c]||0), 0);
            const ig2BadCnt  = [3,4,5].reduce((s,c) => s + (ig2.overall[c]||0), 0);
            const pmdBadPct  = pctOf(pmdBadCnt, pmdTot2);
            const ig2BadPct  = pctOf(ig2BadCnt, ig2Tot2);

            let txt = `${pmdExcPct} % des PMD et ${ig2ExcPct} % des IG2 sont en excellent état (grade 1). `;
            if (pmdBadCnt === 0 && ig2BadCnt === 0) {
                txt += 'Aucune moustiquaire ne présente de défauts visibles ou majeurs (grades 3–5) dans les deux bras.';
            } else {
                txt += `${pmdBadPct} % des PMD et ${ig2BadPct} % des IG2 présentent des défauts visibles ou majeurs (grades 3–5).`;
                if (pmdBadPct > ig2BadPct)      txt += ' Les PermaNet Dual concentrent davantage de dégradations.';
                else if (ig2BadPct > pmdBadPct) txt += ' Les Interceptor G2 concentrent davantage de dégradations.';
            }
            addInterp('chart-overall', txt);
        }

    })(); // end writeInterpretations

    // ── Filtre par bras (PMD / IG2 / Tous) ───────────────────────────────────
    // Exposé globalement pour les boutons HTML
    window.filterViz = function(brand) {

        // ── Boutons charts section ──
        const chartBtns = { 'ALL':'vf-all', 'PMD':'vf-pmd', 'IG2':'vf-ig2' };
        Object.entries(chartBtns).forEach(([b, id]) => {
            const btn = document.getElementById(id);
            if (!btn) return;
            const active = b === brand;
            btn.className = active
                ? btn.className.replace(/text-\w+-\d+|hover:bg-\w+-\d+/g, '').trim() + ' bg-slate-700 text-white'
                : btn.dataset.base || btn.className;
            if (active) btn.style.cssText = 'background:#334155;color:#fff;';
            else        btn.style.cssText = '';
        });

        // ── Boutons table modal ──
        const tableBtns = { 'ALL':'tf-all', 'PMD':'tf-pmd', 'IG2':'tf-ig2' };
        Object.entries(tableBtns).forEach(([b, id]) => {
            const btn = document.getElementById(id);
            if (!btn) return;
            btn.style.cssText = b === brand ? 'background:#334155;color:#fff;' : '';
        });

        // ── Graphiques groupés (1-5) : visibilité des datasets ──
        ['chart-fabric','chart-taille','chart-colour','chart-symmetry','chart-tache'].forEach(id => {
            const c = _charts[id];
            if (!c) return;
            c.setDatasetVisibility(0, brand !== 'IG2'); // PMD = dataset 0
            c.setDatasetVisibility(1, brand !== 'PMD'); // IG2 = dataset 1
            c.update('none');
        });

        // ── Graphique état général : mise à jour labels + données ──
        const oc = _charts['chart-overall'];
        if (oc) {
            if (brand === 'ALL') {
                oc.data.labels = [
                    'PMD — PermaNet Dual  (n=' + pmdTot + ')',
                    'IG2 — Interceptor G2  (n=' + ig2Tot + ')',
                ];
                oc.data.datasets.forEach((ds, i) => { ds.data = [pctPmd[i], pctIg2[i]]; });
            } else if (brand === 'PMD') {
                oc.data.labels = ['PMD — PermaNet Dual  (n=' + pmdTot + ')'];
                oc.data.datasets.forEach((ds, i) => { ds.data = [pctPmd[i]]; });
            } else {
                oc.data.labels = ['IG2 — Interceptor G2  (n=' + ig2Tot + ')'];
                oc.data.datasets.forEach((ds, i) => { ds.data = [pctIg2[i]]; });
            }
            oc.update('none');
        }

        // ── Tableau fiche inspection (modal) ──
        document.querySelectorAll('.viz-pmd').forEach(el => el.classList.toggle('hidden', brand === 'IG2'));
        document.querySelectorAll('.viz-ig2').forEach(el => el.classList.toggle('hidden', brand === 'PMD'));
    };

})();
</script>

</body>
</html>
