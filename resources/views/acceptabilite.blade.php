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
            <a href="{{ route('acceptabilite.pdf', request()->only(['arm','cluster'])) }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                PDF
            </a>
            <a href="{{ route('ae.selection.index') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                Sélection AE
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
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <span class="font-bold text-sm text-slate-800">Acceptabilité — Cohorte A</span>
            </div>
            <span class="text-2xl font-extrabold text-emerald-600">{{ $stats['ua_rate'] }}%</span>
        </div>
        <div class="prog mb-3">
            <div class="prog-fill bg-emerald-500" style="width:{{ $stats['ua_rate'] }}%"></div>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-slate-50 rounded-lg p-2">
                <div class="text-xl font-extrabold text-slate-700">{{ $stats['ua_total'] }}</div>
                <div class="text-xs text-slate-400 mt-0.5">Total ménages</div>
            </div>
            <div class="bg-emerald-50 rounded-lg p-2">
                <div class="text-xl font-extrabold text-emerald-600">{{ $stats['ua_visited'] }}</div>
                <div class="text-xs text-emerald-500 mt-0.5">Visités</div>
            </div>
            <div class="bg-orange-50 rounded-lg p-2">
                <div class="text-xl font-extrabold text-orange-600">{{ $stats['ua_pending'] }}</div>
                <div class="text-xs text-orange-500 mt-0.5">Restants</div>
            </div>
        </div>
    </div>

    {{-- ── Filtres ──────────────────────────────────────────────────────────── --}}
    <div class="card p-4">
        <div class="section-title">Filtres</div>
        <form id="filter-form" method="GET" action="{{ route('acceptabilite.index') }}"
              class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
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
                </select>
            </div>
            <div>
                <label class="form-label">Statut AE</label>
                <select name="ae_status" class="form-select">
                    <option value="">Tous</option>
                    <option value="selected" {{ request('ae_status')=='selected'?'selected':'' }}>Sélectionnés</option>
                    <option value="visited"  {{ request('ae_status')=='visited'?'selected':'' }}>✅ Visité</option>
                    <option value="pending"  {{ request('ae_status')=='pending'?'selected':'' }}>⏳ À visiter</option>
                    <option value="none"     {{ request('ae_status')=='none'?'selected':'' }}>— Non sélectionné</option>
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
            <a href="{{ route('acceptabilite.pdf', request()->only(['arm','cluster'])) }}"
               class="flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Imprimer cette sélection
            </a>
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
                    $trClass = $hh['ua_visited']
                        ? 'ua-visited'
                        : ($hh['ae_visited'] ? 'ae-visited-only' : '');
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
                        @if($hh['ua_visited'])
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
                            @if($hh['ae_visited'])
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
</script>
</body>
</html>
