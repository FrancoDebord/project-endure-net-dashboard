<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections physiques — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        tr.hidden-row { display: none; }

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

            <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 no-print">
                <span id="footer-count">{{ $total }} soumissions</span>
                <span>Actualisé le {{ now()->format('d/m/Y à H:i') }}</span>
            </div>
        @endif
    </div>

</div>

<script>
function filterTable() {
    const search      = document.getElementById('search-input').value.toLowerCase().trim();
    const enumFilter  = document.getElementById('filter-enumerator').value.toLowerCase();
    const brandFilter = document.getElementById('filter-brand').value.toLowerCase();
    const lieuFilter  = document.getElementById('filter-lieu').value.toLowerCase();
    const lotFilter   = document.getElementById('filter-lot').value.toLowerCase();
    const dateFrom    = document.getElementById('filter-date-from').value;
    const dateTo      = document.getElementById('filter-date-to').value;

    const rows  = document.querySelectorAll('#inspections-body tr');
    let visible = 0;

    rows.forEach(row => {
        const rowSearch = row.dataset.search     || '';
        const rowEnum   = row.dataset.enumerator || '';
        const rowBrand  = row.dataset.brand      || '';
        const rowLieu   = row.dataset.lieu       || '';
        const rowLot    = row.dataset.lot        || '';
        const rowDate   = row.dataset.date       || '';

        const matchSearch = !search      || rowSearch.includes(search);
        const matchEnum   = !enumFilter  || rowEnum === enumFilter;
        const matchBrand  = !brandFilter || rowBrand === brandFilter;
        const matchLieu   = !lieuFilter  || rowLieu === lieuFilter;
        const matchLot    = !lotFilter   || rowLot  === lotFilter;
        const matchFrom   = !dateFrom    || rowDate >= dateFrom;
        const matchTo     = !dateTo      || rowDate <= dateTo;

        const show = matchSearch && matchEnum && matchBrand && matchLieu && matchLot && matchFrom && matchTo;
        row.classList.toggle('hidden-row', !show);
        if (show) visible++;
    });

    const cnt    = document.getElementById('result-count');
    const footer = document.getElementById('footer-count');
    if (cnt)    cnt.textContent    = visible + ' résultat' + (visible > 1 ? 's' : '');
    if (footer) footer.textContent = visible + ' soumission' + (visible > 1 ? 's' : '') + ' affichée' + (visible > 1 ? 's' : '');
}

function refreshLotFilter(brandValue) {
    const sel        = document.getElementById('filter-lot');
    const currentLot = sel.value;
    const validLots  = brandValue ? (BRAND_LOTS[brandValue] || []) : null;

    sel.innerHTML = '<option value="">Tous</option>';
    ALL_LOTS.forEach(({ value, label }) => {
        if (!validLots || validLots.includes(value)) {
            const opt    = document.createElement('option');
            opt.value    = value;
            opt.textContent = label;
            sel.appendChild(opt);
        }
    });

    // Conserve la sélection si elle est encore valide, sinon réinitialise
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

document.addEventListener('DOMContentLoaded', () => {
    const total = document.querySelectorAll('#inspections-body tr').length;
    const cnt   = document.getElementById('result-count');
    if (cnt) cnt.textContent = total + ' résultat' + (total > 1 ? 's' : '');
});
</script>

</body>
</html>
