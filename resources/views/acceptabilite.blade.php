<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceptabilité & AE — Cohorte A | ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red:#C41230; --red-dark:#8B0D21; --red-lite:#FDECED; }
        body  { background:#F1F5F9; font-family:system-ui,sans-serif; }
        .card { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }

        /* Badges */
        .badge { border-radius:9999px; padding:.18rem .65rem; font-size:.68rem; font-weight:700; white-space:nowrap; display:inline-flex; align-items:center; gap:.3rem; }
        .badge-ua-ok  { background:#DCFCE7; color:#15803D; }
        .badge-ua-no  { background:#F1F5F9; color:#94A3B8; }
        .badge-ae-ok  { background:#DCFCE7; color:#15803D; }
        .badge-ae-sel { background:#FEF3C7; color:#92400E; }
        .badge-ae-no  { background:#F1F5F9; color:#CBD5E1; }
        .badge-pd     { background:#EDE9FE; color:#5B21B6; }
        .badge-g2     { background:#DBEAFE; color:#1D4ED8; }

        /* Progress */
        .prog { background:#E2E8F0; border-radius:9999px; height:.45rem; overflow:hidden; }
        .prog-fill { border-radius:9999px; height:.45rem; }

        /* Table */
        .tbl { width:100%; border-collapse:collapse; font-size:.78rem;
               border:1.5px solid #CBD5E1; }
        .tbl thead th {
            background:#F1F5F9; color:#475569; font-size:.68rem; font-weight:700;
            letter-spacing:.06em; text-transform:uppercase; padding:.65rem .75rem;
            border-bottom:2px solid #94A3B8;
            border-right:1px solid #CBD5E1; white-space:nowrap;
        }
        .tbl thead th:last-child { border-right:none; }
        .tbl thead th.th-center { text-align:center; }
        .tbl tbody tr { border-bottom:1px solid #CBD5E1; transition:background .1s; }
        .tbl tbody tr:last-child { border-bottom:none; }
        .tbl tbody tr:hover { background:#F8FAFC; }
        .tbl tbody td { padding:.55rem .75rem; vertical-align:middle;
                        border-right:1px solid #E2E8F0; }
        .tbl tbody td:last-child { border-right:none; }
        .tbl tbody td.td-center { text-align:center; }

        /* Filters */
        .form-label { font-size:.72rem; font-weight:700; color:#475569; margin-bottom:.25rem; display:block; }
        .form-select {
            width:100%; border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.45rem .65rem;
            font-size:.82rem; color:#1A1A1A; background:#fff; outline:none;
        }
        .form-select:focus { border-color:var(--red); }
        .form-input {
            width:100%; border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.45rem .65rem;
            font-size:.82rem; color:#1A1A1A; outline:none; transition:border-color .15s;
        }
        .form-input:focus { border-color:var(--red); }

        /* Pagination */
        .pagination-links nav { display:flex; align-items:center; gap:.25rem; }
        .pagination-links span[aria-current] span,
        .pagination-links a {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:2rem; height:2rem; padding:0 .6rem;
            font-size:.75rem; font-weight:600; border-radius:.4rem;
            border:1.5px solid #E2E8F0; background:#fff; color:#475569;
            text-decoration:none; transition:background .12s,color .12s;
        }
        .pagination-links a:hover { background:var(--red-lite); color:var(--red); border-color:var(--red); }
        .pagination-links span[aria-current] span { background:var(--red); color:#fff; border-color:var(--red); }
        .pagination-links span:not([aria-current]) span { color:#CBD5E1; border-color:#F1F5F9; }

        /* Rows visitées */
        .tbl tbody tr.ua-visited { background:#F0FDF4 !important; }
        .tbl tbody tr.ua-visited:hover { background:#DCFCE7 !important; }
        .tbl tbody tr.ae-visited-only { background:#EFF6FF !important; }
        .tbl tbody tr.ae-visited-only:hover { background:#DBEAFE !important; }
        /* Rows absentes */
        .tbl tbody tr.row-absent { background:#FFF7ED !important; }
        .tbl tbody tr.row-absent:hover { background:#FFEDD5 !important; }
        .badge-absent { background:#FED7AA; color:#9A3412; }
        .btn-absent { background:#FFF7ED; color:#C2410C; border:1.5px solid #FDBA74; }
        .btn-absent:hover { background:#FFEDD5; }

        /* Section title */
        .section-title {
            font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
            color:#1A1A1A; border-left:4px solid var(--red); padding-left:.6rem; margin-bottom:.8rem;
        }
    </style>
</head>
<body class="min-h-screen">

{{-- ── Header ────────────────────────────────────────────────────────────────── --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <svg class="w-7 h-7 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-base font-extrabold leading-tight">Acceptabilité & Adverse Events — Cohorte A</h1>
                <p class="text-red-200 text-xs mt-0.5">ENDURE-Net · Suivi des visites par ménage</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('acceptabilite.pdf', request()->only(['arm','cluster','ua_status','ae_status','visit_status','household_type','search','tablet'])) }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                PDF
            </a>
            <a href="{{ route('acceptabilite.pdf', array_merge(request()->only(['arm','cluster']), ['visit_status'=>'absent'])) }}"
               title="Exporter la liste des ménages absents (UA ou AE)"
               class="flex items-center gap-1.5 text-xs bg-orange-500/80 hover:bg-orange-500 px-3 py-1.5 rounded-lg border border-orange-300/50 transition font-medium text-white">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                PDF Absents
                @php $totalAbsents = ($stats['ua_absent'] ?? 0) + ($stats['ae_absent'] ?? 0); @endphp
                @if($totalAbsents > 0)
                <span class="bg-white/30 rounded-full px-1.5 py-0.5 text-xs font-bold">{{ $totalAbsents }}</span>
                @endif
            </a>
            <button onclick="document.getElementById('rapport-modal').classList.remove('hidden')"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Rapport Journalier
            </button>
            <a href="{{ route('ae.selection.index') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                Sélection AE
            </a>
            <a href="{{ route('inspections') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Inspections
            </a>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                Dashboard
            </a>
        </div>
    </div>
</header>

<div class="max-w-screen-2xl mx-auto px-6 py-6 space-y-5">

    {{-- ── Statistiques sommaires ──────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- UA --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span class="font-bold text-sm text-slate-800">Acceptabilité (UA)</span>
                </div>
                <span class="text-2xl font-extrabold text-emerald-600">{{ $stats['ua_rate'] }}%</span>
            </div>
            <div class="prog mb-3">
                <div class="prog-fill bg-emerald-500" style="width:{{ $stats['ua_rate'] }}%"></div>
            </div>
            <div class="grid grid-cols-5 gap-2 text-center">
                <div class="bg-slate-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-slate-700">{{ $stats['ua_total'] }}</div>
                    <div class="text-xs text-slate-400 mt-0.5">Total</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-emerald-600">{{ $stats['ua_visited'] }}</div>
                    <div class="text-xs text-emerald-500 mt-0.5">Visités</div>
                </div>
                <div class="bg-orange-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-orange-600">{{ $stats['ua_pending'] }}</div>
                    <div class="text-xs text-orange-500 mt-0.5">Restants</div>
                </div>
                <div class="bg-green-50 rounded-lg p-2" style="border:1px solid #BBF7D0">
                    <div class="text-lg font-extrabold text-green-700">{{ $stats['ua_present'] }}</div>
                    <div class="text-xs text-green-600 mt-0.5">Présents</div>
                </div>
                <div class="bg-red-50 rounded-lg p-2" style="border:1px solid #FECACA">
                    <div class="text-lg font-extrabold text-red-700">{{ $stats['ua_absent'] }}</div>
                    <div class="text-xs text-red-500 mt-0.5">Absents</div>
                </div>
            </div>
        </div>
        {{-- AE --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="font-bold text-sm text-slate-800">Adverse Events (AE)</span>
                </div>
                <span class="text-2xl font-extrabold text-blue-600">{{ $stats['ae_rate'] }}%</span>
            </div>
            <div class="prog mb-3">
                <div class="prog-fill bg-blue-500" style="width:{{ $stats['ae_rate'] }}%"></div>
            </div>
            <div class="grid grid-cols-5 gap-2 text-center">
                <div class="bg-slate-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-slate-700">{{ $stats['ae_total'] }}</div>
                    <div class="text-xs text-slate-400 mt-0.5">Sélectionnés</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-blue-600">{{ $stats['ae_visited'] }}</div>
                    <div class="text-xs text-blue-500 mt-0.5">Visités</div>
                </div>
                <div class="bg-orange-50 rounded-lg p-2">
                    <div class="text-lg font-extrabold text-orange-600">{{ $stats['ae_pending'] }}</div>
                    <div class="text-xs text-orange-500 mt-0.5">Restants</div>
                </div>
                <div class="bg-green-50 rounded-lg p-2" style="border:1px solid #BBF7D0">
                    <div class="text-lg font-extrabold text-green-700">{{ $stats['ae_present'] }}</div>
                    <div class="text-xs text-green-600 mt-0.5">Présents</div>
                </div>
                <div class="bg-red-50 rounded-lg p-2" style="border:1px solid #FECACA">
                    <div class="text-lg font-extrabold text-red-700">{{ $stats['ae_absent'] }}</div>
                    <div class="text-xs text-red-500 mt-0.5">Absents</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filtres ──────────────────────────────────────────────────────────── --}}
    <div class="card p-4">
        <div class="section-title">Filtres</div>
        <form id="filter-form" method="GET" action="{{ route('acceptabilite.index') }}"
              class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-9 gap-3 items-end">
            <div>
                <label class="form-label">Bras</label>
                <select name="arm" class="form-select">
                    <option value="">Tous les bras</option>
                    <option value="PD" {{ request('arm')=='PD'?'selected':'' }}>PERMANET DUAL</option>
                    <option value="G2" {{ request('arm')=='G2'?'selected':'' }}>INTERCEPTOR G2</option>
                </select>
            </div>
            <div>
                <label class="form-label">Grappe</label>
                <select name="cluster" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($clusterOptions as $c)
                    <option value="{{ $c }}" {{ request('cluster')==$c?'selected':'' }}>
                        {{ $clusterDisplay[$c] ?? 'Grappe '.$c }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Statut Acceptabilité</label>
                <select name="ua_status" class="form-select">
                    <option value="">Tous</option>
                    <option value="visited" {{ request('ua_status')=='visited'?'selected':'' }}>✅ Visité</option>
                    <option value="pending" {{ request('ua_status')=='pending'?'selected':'' }}>⏳ À visiter</option>
                    <option value="absent"  {{ request('ua_status')=='absent'?'selected':'' }}>🚫 Absent</option>
                </select>
            </div>
            <div>
                <label class="form-label">Statut AE</label>
                <select name="ae_status" class="form-select">
                    <option value="">Tous</option>
                    <option value="selected" {{ request('ae_status')=='selected'?'selected':'' }}>Sélectionnés</option>
                    <option value="visited"  {{ request('ae_status')=='visited'?'selected':'' }}>✅ Visité</option>
                    <option value="pending"  {{ request('ae_status')=='pending'?'selected':'' }}>⏳ À visiter</option>
                    <option value="absent"   {{ request('ae_status')=='absent'?'selected':'' }}>🚫 Absent</option>
                    <option value="none"     {{ request('ae_status')=='none'?'selected':'' }}>— Non sélectionné</option>
                </select>
            </div>
            <div>
                <label class="form-label">Visites en attente</label>
                <select name="visit_status" class="form-select">
                    <option value="">Tous les ménages</option>
                    <option value="pending" {{ request('visit_status')=='pending'?'selected':'' }}>⏳ Non visités (UA ou AE)</option>
                    <option value="done"    {{ request('visit_status')=='done'?'selected':'' }}>✅ Tout visité</option>
                    <option value="absent"  {{ request('visit_status')=='absent'?'selected':'' }}>🚫 Absents (UA ou AE)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Type de ménage</label>
                <select name="household_type" class="form-select">
                    <option value="">Tous les ménages</option>
                    <option value="ua"      {{ request('household_type')=='ua'      ?'selected':'' }}>Ménages UA (Cohorte A)</option>
                    <option value="ae"      {{ request('household_type')=='ae'      ?'selected':'' }}>Ménages AE sélectionnés</option>
                    <option value="ua_ae"   {{ request('household_type')=='ua_ae'   ?'selected':'' }}>Ménages UA &amp; AE</option>
                    <option value="ua_only" {{ request('household_type')=='ua_only' ?'selected':'' }}>UA uniquement (sans AE)</option>
                    <option value="ae_only" {{ request('household_type')=='ae_only' ?'selected':'' }}>AE uniquement (sans UA)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Tablette</label>
                <select name="tablet" class="form-select">
                    <option value="">Toutes</option>
                    @foreach(range(1, 15) as $t)
                    <option value="{{ str_pad($t, 2, '0', STR_PAD_LEFT) }}"
                            {{ request('tablet') == str_pad($t, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                        Tablette {{ str_pad($t, 2, '0', STR_PAD_LEFT) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ID, nom…" class="form-input">
            </div>
            <div class="flex flex-col gap-2">
                <span id="filter-spinner" class="hidden items-center gap-1 text-xs text-slate-400">
                    <svg class="animate-spin w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Filtrage…
                </span>
                <a href="{{ route('acceptabilite.index') }}"
                   class="flex items-center justify-center gap-1.5 text-xs border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-lg px-3 py-2 text-slate-600 font-medium transition">
                    Effacer
                </a>
            </div>
        </form>
    </div>

    {{-- ── Tableau ───────────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="section-title mb-0">
                Ménages Cohorte A
                <span class="text-slate-400 font-normal normal-case tracking-normal">
                    ({{ $households->total() }} ménage{{ $households->total() > 1 ? 's' : '' }})
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('acceptabilite.pdf', request()->only(['arm','cluster','ua_status','ae_status','visit_status','household_type','search','tablet'])) }}"
                   class="flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    Imprimer cette sélection
                </a>
                @if(request('visit_status') !== 'absent')
                <a href="{{ route('acceptabilite.pdf', array_merge(request()->only(['arm','cluster']), ['visit_status'=>'absent'])) }}"
                   title="Exporter uniquement les ménages absents"
                   class="flex items-center gap-1.5 text-xs btn-absent px-3 py-1.5 rounded-lg font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    PDF Absents
                    @php $absentCount = ($stats['ua_absent'] ?? 0) + ($stats['ae_absent'] ?? 0); @endphp
                    @if($absentCount > 0)
                    <span class="bg-orange-200 text-orange-800 rounded-full px-1.5 font-bold">{{ $absentCount }}</span>
                    @endif
                </a>
                @endif
            </div>
        </div>

        @if($households->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="font-semibold text-sm">Aucun résultat</p>
            <p class="text-xs mt-1">Modifiez les filtres ou effacez la recherche.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bras</th>
                        <th class="th-center">Cohorte</th>
                        <th>Grappe</th>
                        <th>Code ménage</th>
                        <th>Chef de ménage / Contact</th>
                        <th>Meilleur moment</th>
                        <th>Adresse</th>
                        <th class="th-center" style="min-width:90px">Acceptabilité</th>
                        <th class="th-center" style="min-width:110px">Adverse Event</th>
                    </tr>
                </thead>
                <tbody>
                @php $rowNum = ($households->currentPage() - 1) * $households->perPage(); @endphp
                @foreach($households as $hh)
                @php
                    $rowNum++;
                    $isAbsent = ($hh['ua_absent'] ?? false) || ($hh['ae_absent'] ?? false);
                    $trClass = $isAbsent
                        ? 'row-absent'
                        : ($hh['ua_visited']
                            ? 'ua-visited'
                            : ($hh['ae_visited'] ? 'ae-visited-only' : ''));
                @endphp
                <tr class="{{ $trClass }}" >
                    <td class="text-slate-400 text-xs">{{ $rowNum }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower($hh['arm']) }}">{{ $hh['arm'] }}</span>
                    </td>
                    <td class="td-center">
                        @php $coh = $hh['study_cohort'] ?? ''; @endphp
                        @if($coh)
                        <span class="badge" style="background:{{ $coh=='1'?'#DCFCE7':'#FEF3C7' }};color:{{ $coh=='1'?'#15803D':'#92400E' }}">
                            {{ $coh=='1'?'A':($coh=='2'?'B':$coh) }}
                        </span>
                        @else <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-700 text-sm">
                        {{ $hh['cluster_id'] ? ($clusterDisplay[$hh['cluster_id']] ?? 'Grappe '.$hh['cluster_id']) : '—' }}
                    </td>
                    <td class="font-mono text-xs font-bold text-slate-800">{{ $hh['household_id'] }}</td>
                    <td style="min-width:150px">
                        <div class="text-slate-700 font-medium text-xs">{{ $hh['head_name'] ?? '—' }}</div>
                        @if($hh['head_phone'])
                        <div class="text-slate-400 font-mono text-xs">{{ $hh['head_phone'] }}</div>
                        @endif
                        @if($hh['contact_name'] || $hh['contact_phone'])
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <div class="text-slate-600 text-xs">{{ $hh['contact_name'] ?? '—' }}</div>
                            @if($hh['contact_phone'])
                            <div class="text-slate-400 font-mono text-xs">{{ $hh['contact_phone'] }}</div>
                            @endif
                        </div>
                        @endif
                    </td>
                    <td class="text-slate-500 text-xs">{{ $hh['moment_visite'] ?? '—' }}</td>
                    <td class="text-slate-500 text-xs" style="max-width:130px">{{ $hh['adresse'] ?? '—' }}</td>

                    {{-- Acceptabilité --}}
                    <td class="td-center">
                        @if(!($hh['in_acceptabilite'] ?? true))
                        <span class="badge badge-ua-no" style="color:#CBD5E1">—</span>
                        @elseif($hh['ua_absent'])
                        <span class="badge badge-absent">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            Absent
                        </span>
                        @elseif($hh['ua_visited'])
                        <span class="badge badge-ua-ok">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            Visité
                        </span>
                        @else
                        <span class="badge badge-ua-no">À visiter</span>
                        @endif
                    </td>

                    {{-- Adverse Event --}}
                    <td class="td-center">
                        @if($hh['is_ae_selected'])
                            @if($hh['ae_absent'])
                            <span class="badge badge-absent">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Absent
                            </span>
                            @elseif($hh['ae_visited'])
                            <span class="badge badge-ae-ok">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                                Visité
                            </span>
                            @else
                            <span class="badge badge-ae-sel">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                À visiter
                            </span>
                            @endif
                        @else
                        <span class="text-slate-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($households->hasPages())
        <div class="px-5 py-4 flex items-center justify-between gap-4 flex-wrap border-t border-slate-100">
            <p class="text-xs text-slate-500">
                Page {{ $households->currentPage() }} / {{ $households->lastPage() }}
                &nbsp;·&nbsp;
                Ménages {{ $households->firstItem() }}–{{ $households->lastItem() }} sur {{ $households->total() }}
            </p>
            <div class="pagination-links">
                {{ $households->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
        @endif

    </div>


    {{-- ── Symptômes AE par bras et groupe d'âge ─────────────────────────── --}}
    @if(!empty($symptomStats) && collect($symptomStats)->some(fn($s) => $s['total_with_symptoms'] > 0))
    <div class="card p-5">
        <div class="section-title">Symptômes Adverse Events — par bras et groupe d'âge</div>
        <p class="text-xs text-slate-500 mb-4">
            Ménages Cohorte A ayant déclaré au moins un symptôme lors de la visite AE.
            Comptage par ménage unique (pas par moustiquaire).
        </p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($symptomStats as $arm => $armData)
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="badge {{ $arm === 'PD' ? 'badge-pd' : 'badge-g2' }}">{{ $arm }}</span>
                    <span class="font-bold text-sm text-slate-700">{{ $armData['label'] }}</span>
                    <span class="text-xs text-slate-400 ml-auto">
                        {{ $armData['total_with_symptoms'] }} ménage(s) avec symptôme(s)
                        @if($armData['total_records'] > 0)
                        / {{ $armData['total_records'] }} visités
                        @endif
                    </span>
                </div>
                @if(empty($armData['symptoms']))
                <p class="text-xs text-slate-400 italic">Aucun symptôme signalé pour ce bras.</p>
                @else
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Symptôme</th>
                                <th class="th-center" style="min-width:80px">Adultes</th>
                                <th class="th-center" style="min-width:80px">Enfants</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($armData['symptoms'] as $sym)
                        <tr>
                            <td class="font-medium text-slate-700">{{ $sym['label'] }}</td>
                            <td class="td-center">
                                @if($sym['adult'] > 0)
                                <span class="badge" style="background:#FEF3C7;color:#92400E">
                                    {{ $sym['adult'] }}
                                </span>
                                @else
                                <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="td-center">
                                @if($sym['child'] > 0)
                                <span class="badge" style="background:#EDE9FE;color:#5B21B6">
                                    {{ $sym['child'] }}
                                </span>
                                @else
                                <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- ── Modal : Rapport Journalier ─────────────────────────────────────────── --}}
<div id="rapport-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.45)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm" onclick="event.stopPropagation()">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div>
                <h2 class="font-extrabold text-slate-800 text-sm">Rapport Journalier</h2>
                <p class="text-xs text-slate-400 mt-0.5">Activité par tablette — UA &amp; AE</p>
            </div>
            <button onclick="document.getElementById('rapport-modal').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <form id="rapport-form" action="{{ route('acceptabilite.rapport.pdf') }}" method="GET" target="_blank"
              class="px-5 py-5 space-y-4">
            <div>
                <label class="form-label" for="rapport-date-debut">Date de début</label>
                <input type="date" id="rapport-date-debut" name="date_debut"
                       class="form-input" required>
            </div>
            <div>
                <label class="form-label" for="rapport-date-fin">Date de fin</label>
                <input type="date" id="rapport-date-fin" name="date_fin"
                       class="form-input" required>
            </div>
            <p class="text-xs text-slate-400">
                Le PDF listera chaque date de la période avec le détail par tablette.
            </p>
            <div class="flex gap-2 pt-1">
                <button type="button"
                        onclick="document.getElementById('rapport-modal').classList.add('hidden')"
                        class="flex-1 text-xs border border-slate-200 bg-slate-50 hover:bg-slate-100 rounded-lg px-3 py-2 text-slate-600 font-medium transition">
                    Annuler
                </button>
                <button type="submit"
                        class="flex-1 text-xs text-white rounded-lg px-3 py-2 font-bold transition"
                        style="background:#C41230">
                    <svg class="w-3.5 h-3.5 inline mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    Générer le PDF
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form    = document.getElementById('filter-form');
    const spinner = document.getElementById('filter-spinner');
    if (!form) return;

    function submit() {
        spinner.classList.remove('hidden');
        spinner.classList.add('inline-flex');
        form.submit();
    }

    form.querySelectorAll('select').forEach(sel => sel.addEventListener('change', submit));

    let debTimer;
    const searchInput = form.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debTimer);
            debTimer = setTimeout(submit, 400);
        });
    }
})();

(function () {
    // Pré-remplir les dates du modal avec les 7 derniers jours
    const today = new Date();
    const fmt   = d => d.toISOString().slice(0, 10);
    const minus7 = new Date(today); minus7.setDate(today.getDate() - 6);

    const inputDebut = document.getElementById('rapport-date-debut');
    const inputFin   = document.getElementById('rapport-date-fin');
    if (inputDebut) inputDebut.value = fmt(minus7);
    if (inputFin)   inputFin.value   = fmt(today);

    // Fermer le modal en cliquant sur le backdrop
    const modal = document.getElementById('rapport-modal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.classList.add('hidden');
        });
    }

    // Empêcher date_fin < date_debut
    if (inputDebut && inputFin) {
        inputDebut.addEventListener('change', function () {
            if (inputFin.value && inputFin.value < inputDebut.value) {
                inputFin.value = inputDebut.value;
            }
        });
    }
})();
</script>
</body>
</html>
