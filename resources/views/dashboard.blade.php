@php $renderSection = $renderSection ?? null; @endphp
@if(!$renderSection)
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
    {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">

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
            <a href="{{ route('rapport.bras') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Rapport bras
            </a>
            <a href="{{ route('inspections') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Inspections
            </a>
            <a href="{{ route('docs') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Docs
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

@endif
{{-- ══════════════════ TAB PANELS ══════════════════ --}}
@if(!$renderSection)
<div class="max-w-screen-2xl mx-auto px-6 py-8">

    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-xl text-sm font-medium mb-6">
            ✓ {{ session('success') }}
        </div>
    @endif
@endif

    {{-- ══ TAB 1 : Baseline & Distribution ══ --}}
    @if(!$renderSection || $renderSection === 'baseline')
        @include('partials.tab-baseline')
    @endif

    {{-- ══ TAB 2 : User Acceptability ══ --}}
    @if(!$renderSection || $renderSection === 'ua')
        @include('partials.tab-ua')
    @endif

    {{-- ══ TAB 3 : Adverse Events ══ --}}
    @if(!$renderSection || $renderSection === 'ae')
        @include('partials.tab-ae')
    @endif

    {{-- ══ TAB : Suivi Cohorte A (6, 12, 24, 36 mois) ══ --}}
    @if(!$renderSection || $renderSection === 'suivi_a' || $renderSection === 'followup')
        @include('partials.tab-suivi_a')
    @endif

    {{-- ══ TAB : Suivi Cohorte B (6, 12, 18, 24, 36 mois) ══ --}}
    @if(!$renderSection || $renderSection === 'suivi' || $renderSection === 'followup')
        @include('partials.tab-suivi')
    @endif

    {{-- ══ TAB : Rapport d'activités ══ --}}
    @if(!$renderSection || $renderSection === 'rapport')
        @include('partials.tab-rapport')
    @endif

    {{-- ══ TAB: Distribution des moustiquaires ══ --}}
    @if(!$renderSection || $renderSection === 'distribution')
        @include('partials.tab-distribution')
    @endif

@if(!$renderSection)
</div>{{-- /max-w --}}

<footer class="text-center text-xs text-gray-400 py-6 mt-2">
    AIRID · ENDURE-Net · REDCap #{{ config('redcap.project_id') }} · Dassa-Zoumè, Bénin
</footer>
@endif

{{-- ══════════════════ SCRIPTS ══════════════════ --}}
@if(!$renderSection)
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

// ── PWA ──
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(()=>{});

// ── Init paginators (non-DataTables tables) ──
['hhTable', 'netsTable'].forEach(setupPagination);
</script>

{{-- DataTables libraries (jQuery required) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>
<style>
    /* DataTables — style harmonisé avec le dashboard */
    table.dataTable thead th, table.dataTable thead td { border-bottom: 1px solid #E5E7EB; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9CA3AF; padding: 0.625rem 0.75rem; }
    table.dataTable tbody tr:hover td { background: #FDF4F5; }
    div.dt-container { font-size: 0.8rem; }
    div.dt-container .dt-search input { border: 1px solid #E5E7EB; border-radius: 0.5rem; padding: 0.375rem 0.75rem; font-size: 0.8rem; outline: none; }
    div.dt-container .dt-search input:focus { border-color: #C41230; box-shadow: 0 0 0 2px rgba(196,18,48,.15); }
    div.dt-container .dt-info, div.dt-container .dt-paging { margin-top: 0.5rem; font-size: 0.75rem; color: #6B7280; }
    div.dt-container .dt-paging .dt-paging-button { border: 1px solid #E5E7EB; border-radius: 0.375rem; padding: 0.2rem 0.5rem; margin: 0 1px; cursor: pointer; background: white; }
    div.dt-container .dt-paging .dt-paging-button.current { background: #C41230; color: white !important; border-color: #C41230; }
    div.dt-container .dt-paging .dt-paging-button:hover:not(.disabled) { background: #F9FAFB; }
    div.dt-container select { border: 1px solid #E5E7EB; border-radius: 0.375rem; padding: 0.2rem 0.4rem; font-size: 0.75rem; outline: none; }
    button.dt-export-btn { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.85rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.5rem; border: 1px solid #E5E7EB; background: white; color: #374151; cursor: pointer; transition: background 0.15s; }
    button.dt-export-btn:hover { background: #F9FAFB; border-color: #C41230; color: #C41230; }
    div.dt-buttons { display: flex; gap: 0.375rem; flex-wrap: wrap; }
    div.dt-layout-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem; }
    div.dt-layout-row:last-child { margin-bottom: 0; margin-top: 0.5rem; }
</style>
</body>
</html>
@endif
