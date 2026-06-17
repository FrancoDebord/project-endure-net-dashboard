<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AE — Sélection des ménages | ENDURE-Net</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red:#C41230; --red-dark:#8B0D21; --red-lite:#FDECED; --dark:#1A1A1A; }
        body { background:#F1F5F9; font-family: system-ui, sans-serif; }

        .card  { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }
        .section-title {
            font-size:.78rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
            color:var(--dark); border-left:4px solid var(--red); padding-left:.65rem; margin-bottom:.9rem;
        }
        .btn-primary {
            background:var(--red); color:#fff; border:none; border-radius:.5rem;
            padding:.5rem 1.2rem; font-size:.8rem; font-weight:700; cursor:pointer;
            transition:background .15s; display:inline-flex; align-items:center; gap:.4rem;
        }
        .btn-primary:hover { background:var(--red-dark); }
        .btn-secondary {
            background:#fff; color:var(--dark); border:1.5px solid #E2E8F0; border-radius:.5rem;
            padding:.5rem 1.2rem; font-size:.8rem; font-weight:600; cursor:pointer;
            transition:background .15s; display:inline-flex; align-items:center; gap:.4rem;
        }
        .btn-secondary:hover { background:#F8FAFC; }
        .btn-danger {
            background:#FEE2E2; color:#991B1B; border:none; border-radius:.5rem;
            padding:.5rem 1.2rem; font-size:.8rem; font-weight:700; cursor:pointer;
            transition:background .15s; display:inline-flex; align-items:center; gap:.4rem;
        }
        .btn-danger:hover { background:#FECACA; }

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
        .tbl tbody tr { border-bottom:1px solid #CBD5E1; transition:background .1s; }
        .tbl tbody tr:last-child { border-bottom:none; }
        .tbl tbody tr:hover { background:#F8FAFC; }
        .tbl tbody td { padding:.6rem .75rem; vertical-align:middle;
                        border-right:1px solid #E2E8F0; }
        .tbl tbody td:last-child { border-right:none; }

        /* Badges */
        .badge { border-radius:9999px; padding:.18rem .65rem; font-size:.68rem; font-weight:700; white-space:nowrap; }
        .badge-ok   { background:#DCFCE7; color:#15803D; }
        .badge-no   { background:#FEE2E2; color:#991B1B; }
        .badge-pd   { background:#EDE9FE; color:#5B21B6; }
        .badge-g2   { background:#DBEAFE; color:#1D4ED8; }

        /* Progress */
        .prog { background:#E2E8F0; border-radius:9999px; height:.5rem; overflow:hidden; }
        .prog-fill { border-radius:9999px; height:.5rem; transition:width .5s; }

        /* Modal */
        .modal-bg {
            position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50;
            display:none; align-items:center; justify-content:center;
        }
        .modal-bg.open { display:flex; }
        .modal-box {
            background:#fff; border-radius:1rem; padding:2rem; width:100%; max-width:480px;
            box-shadow:0 20px 60px rgba(0,0,0,.25); position:relative;
        }
        .modal-title { font-size:1rem; font-weight:800; color:var(--dark); margin-bottom:1.2rem; }
        .form-label  { font-size:.75rem; font-weight:700; color:#475569; margin-bottom:.3rem; display:block; }
        .form-input  {
            width:100%; border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.5rem .75rem;
            font-size:.85rem; color:var(--dark); outline:none; transition:border-color .15s;
        }
        .form-input:focus { border-color:var(--red); }
        .form-select {
            width:100%; border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.5rem .75rem;
            font-size:.85rem; color:var(--dark); background:#fff; outline:none;
        }

        /* Toggle completed */
        .toggle-btn { cursor:pointer; width:1.5rem; height:1.5rem; border-radius:.35rem; border:2px solid #E2E8F0;
            background:#fff; display:inline-flex; align-items:center; justify-content:center;
            transition:all .15s; flex-shrink:0; }
        .toggle-btn.done { background:#15803D; border-color:#15803D; }
        .toggle-btn svg  { width:.9rem; height:.9rem; }

        /* Net codes */
        .net-code { display:inline-block; background:#F1F5F9; color:#334155; font-size:.65rem;
            font-weight:600; padding:.1rem .4rem; border-radius:.25rem; margin:.1rem .1rem 0 0; }

        /* Sticky header */
        .sticky-header { position:sticky; top:0; z-index:20; }
        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body class="min-h-screen">

{{-- ── Header ────────────────────────────────────────────────────────────────── --}}
<header class="sticky-header" style="background:linear-gradient(135deg,#C41230 0%,#8B0D21 100%);">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}"
               class="text-white/70 hover:text-white text-sm flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Dashboard
            </a>
            <span class="text-white/40">|</span>
            <div>
                <div class="text-white font-bold text-base leading-tight">Adverse Events — Sélection des ménages</div>
                <div class="text-white/60 text-xs">ENDURE-Net · REDCap Projet 46</div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Bouton PDF principal (avec filtres actifs) --}}
            <a href="{{ route('ae.selection.excel', request()->only(['arm','village','cluster','dag','cohort','status'])) }}"
               class="btn-secondary text-xs" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.25);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter Excel
            </a>
            <a href="{{ route('ae.selection.pdf', request()->only(['arm','village','cluster','dag','status'])) }}"
               class="btn-secondary text-xs" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.25);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                Exporter PDF
            </a>
        </div>
    </div>
</header>

{{-- ── Flash messages ──────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="max-w-screen-2xl mx-auto px-6 mt-4">
    <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
</div>
@endif
@if(session('error'))
<div class="max-w-screen-2xl mx-auto px-6 mt-4">
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{ session('error') }}
    </div>
</div>
@endif

<div class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

    {{-- ── KPI globaux ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <div class="text-xs font-700 text-slate-500 uppercase tracking-wide mb-2">Total sélectionnés</div>
            <div class="font-extrabold text-3xl" style="color:#1E293B">{{ $stats['total'] }}</div>
            <div class="text-xs text-slate-400 mt-1">ménages</div>
        </div>
        <div class="card p-5" style="border-left:4px solid #15803D">
            <div class="text-xs font-700 text-slate-500 uppercase tracking-wide mb-2">Visités (REDCap)</div>
            <div class="font-extrabold text-3xl" style="color:#15803D">{{ $stats['redcap_visited'] }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $stats['redcap_rate'] }}% · formulaire AE soumis</div>
        </div>
        <div class="card p-5" style="border-left:4px solid #C41230">
            <div class="text-xs font-700 text-slate-500 uppercase tracking-wide mb-2">En attente</div>
            <div class="font-extrabold text-3xl" style="color:#C41230">{{ $stats['redcap_pending'] }}</div>
            <div class="text-xs text-slate-400 mt-1">pas encore de données REDCap</div>
        </div>
        <div class="card p-5" style="border-left:4px solid #0369A1">
            <div class="text-xs font-700 text-slate-500 uppercase tracking-wide mb-2">Taux de couverture</div>
            <div class="font-extrabold text-3xl" style="color:#0369A1">{{ $stats['redcap_rate'] }}%</div>
            <div class="text-xs text-slate-400 mt-1">basé sur formulaires REDCap</div>
        </div>
    </div>

    {{-- ── KPI par bras ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($stats['by_arm'] as $armKey => $arm)
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background:{{ $arm['color'] }}"></span>
                    <span class="font-bold text-sm text-slate-800">{{ $arm['label'] }}</span>
                    <span class="badge {{ $armKey === 'PD' ? 'badge-pd' : 'badge-g2' }}">{{ $armKey }}</span>
                </div>
                <span class="text-2xl font-extrabold" style="color:{{ $arm['color'] }}">{{ $arm['redcap_rate'] }}%</span>
            </div>
            <div class="prog mb-2">
                <div class="prog-fill" style="width:{{ $arm['redcap_rate'] }}%;background:{{ $arm['color'] }}"></div>
            </div>
            <div class="flex justify-between text-xs text-slate-500">
                <span><b class="text-green-700">{{ $arm['redcap_visited'] }}</b> visités (REDCap)</span>
                <span><b class="text-red-700">{{ $arm['redcap_pending'] }}</b> en attente</span>
                <span><b class="text-slate-700">{{ $arm['total'] }}</b> total</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Boutons d'action ─────────────────────────────────────────────────── --}}
    <div class="card p-4">
        <div class="section-title">Actions de sélection</div>
        <div class="flex flex-wrap gap-3">
            <button disabled title="La sélection est verrouillée — ne pas modifier"
                    class="btn-primary" style="opacity:.35;cursor:not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Sélection verrouillée
            </button>
            <button onclick="openModal('modal-add')" class="btn-secondary" {{ $stats['total'] == 0 ? 'disabled style=opacity:.4;cursor:not-allowed' : '' }}>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Ajouter des ménages
            </button>
            <button disabled title="La réinitialisation est verrouillée — ne pas modifier la sélection existante"
                    class="btn-danger" style="opacity:.35;cursor:not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Réinitialisation verrouillée
            </button>
            @if($stats['total'] > 0)
            <div class="ml-auto flex gap-2">
                <a href="{{ route('ae.selection.excel', request()->only(['arm','village','cluster','dag','cohort','status'])) }}"
                   class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    Exporter Excel
                </a>
                <a href="{{ route('ae.selection.pdf', request()->only(['arm','village','cluster','dag','status'])) }}"
                   class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Imprimer PDF
                </a>
            </div>
            @endif
        </div>

        {{-- Historique des tirages --}}
        @if($runs->isNotEmpty())
        <details class="mt-4">
            <summary class="text-xs font-600 text-slate-500 cursor-pointer hover:text-slate-700">
                Historique des tirages ({{ $runs->count() }} derniers)
            </summary>
            <div class="mt-2 space-y-1">
                @foreach($runs as $run)
                <div class="flex items-center gap-3 text-xs text-slate-600 py-1 border-b border-slate-100">
                    <span class="badge {{ $run->arm === 'PD' ? 'badge-pd' : ($run->arm === 'G2' ? 'badge-g2' : 'badge no') }}">
                        {{ $run->arm ?? 'Les deux' }}
                    </span>
                    <span>{{ $run->selected_count }} ménages sélectionnés</span>
                    <span class="text-slate-400">cible : {{ $run->target_count }}</span>
                    <span class="text-slate-400">|</span>
                    <span>{{ $run->created_at->format('d/m/Y H:i') }}</span>
                    @if($run->notes)
                    <span class="text-slate-400 italic truncate max-w-xs">{{ $run->notes }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </details>
        @endif
    </div>

    {{-- ── Filtres ──────────────────────────────────────────────────────────── --}}
    <div class="card p-4">
        <div class="section-title">Filtres</div>
        <form id="filter-form" method="GET" action="{{ route('ae.selection.index') }}"
              class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 items-end">
            <div>
                <label class="form-label">Bras</label>
                <select name="arm" class="form-select">
                    <option value="">Tous les bras</option>
                    <option value="PD" {{ request('arm')=='PD'?'selected':'' }}>PERMANET DUAL (PD)</option>
                    <option value="G2" {{ request('arm')=='G2'?'selected':'' }}>INTERCEPTOR G2 (G2)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Cohorte</label>
                <select name="cohort" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($filterOptions['cohorts'] as $co)
                    <option value="{{ $co }}" {{ request('cohort')==$co?'selected':'' }}>
                        Cohorte {{ $co == '1' ? 'A' : ($co == '2' ? 'B' : $co) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Village</label>
                <select name="village" class="form-select">
                    <option value="">Tous</option>
                    @foreach($filterOptions['villages'] as $v)
                    <option value="{{ $v }}" {{ request('village')==$v?'selected':'' }}>{{ $villageLabels[$v] ?? $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Grappe</label>
                <select name="cluster" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($filterOptions['clusters'] as $c)
                    <option value="{{ $c }}" {{ request('cluster')==$c?'selected':'' }}>{{ $clusterLabels[$c] ?? 'Grappe '.$c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">DAG</label>
                <select name="dag" class="form-select">
                    <option value="">Tous</option>
                    @foreach($filterOptions['dags'] as $d)
                    <option value="{{ $d }}" {{ request('dag')==$d?'selected':'' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Statut REDCap</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <option value="visited" {{ request('status')=='visited'?'selected':'' }}>Visités (REDCap)</option>
                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>En attente</option>
                </select>
            </div>
            <div>
                <label class="form-label">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ID, nom…" class="form-input">
            </div>
            <div class="col-span-2 md:col-span-4 lg:col-span-7 flex gap-2 justify-end items-center">
                <span id="filter-spinner" class="hidden items-center gap-1.5 text-xs text-slate-500">
                    <svg class="animate-spin w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Application…
                </span>
                <a href="{{ route('ae.selection.index') }}" class="btn-secondary text-xs">Effacer les filtres</a>
            </div>
        </form>
    </div>

    {{-- ── Tableau ───────────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="section-title mb-0">
                Liste des ménages sélectionnés
                <span class="text-slate-400 font-normal normal-case tracking-normal">
                    ({{ $households->total() }} {{ Str::plural('ménage', $households->total()) }})
                </span>
            </div>
        </div>

        @if($households->isEmpty())
        <div class="text-center py-16 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <p class="font-600 text-sm">Aucun ménage sélectionné</p>
            <p class="text-xs mt-1">Lancez une sélection aléatoire pour commencer.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:2.5rem">✓</th>
                        <th>Bras</th>
                        <th>Cohorte</th>
                        <th>Village</th>
                        <th>Grappe</th>
                        <th>DAG</th>
                        <th>Code ménage</th>
                        <th>Chef de ménage / Contact</th>
                        <th>Meilleur moment</th>
                        <th>Adresse</th>
                        <th style="text-align:center">Moustiq.</th>
                        <th>Codes moustiquaires</th>
                        <th style="text-align:center">Statut AE (REDCap)</th>
                        <th style="text-align:center">Complétion</th>
                    </tr>
                </thead>
                <tbody id="households-tbody">
                @php $rowNum = 0; @endphp
                @foreach($households as $hh)
                @php
                    $rowNum++;
                    $isVisited = $aeVisitedIds->has($hh->household_id);
                @endphp
                <tr id="row-{{ $hh->id }}" class="{{ $hh->form_completed ? 'opacity-60' : '' }}"
                    style="{{ $isVisited ? 'background:#F0FDF4;' : '' }}">
                    {{-- Toggle complétion --}}
                    <td class="text-center">
                        <button class="toggle-btn {{ $hh->form_completed ? 'done' : '' }}"
                                onclick="toggleCompleted({{ $hh->id }}, this)"
                                title="{{ $hh->form_completed ? 'Marquer non complété' : 'Marquer complété' }}">
                            @if($hh->form_completed)
                            <svg fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            @endif
                        </button>
                    </td>
                    <td>
                        <span class="badge {{ $hh->arm === 'PD' ? 'badge-pd' : 'badge-g2' }}">
                            {{ $hh->arm }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php $coh = $hh->study_cohort; @endphp
                        @if($coh)
                        <span class="badge" style="background:{{ $coh=='1'?'#DCFCE7':'#FEF3C7' }};color:{{ $coh=='1'?'#15803D':'#92400E' }}">
                            {{ $coh == '1' ? 'A' : ($coh == '2' ? 'B' : $coh) }}
                        </span>
                        @else <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="font-medium text-slate-700">{{ $villageLabels[$hh->village] ?? $hh->village ?? '—' }}</td>
                    <td class="text-slate-600">{{ $hh->cluster_id ? ($clusterLabels[$hh->cluster_id] ?? 'Grappe '.$hh->cluster_id) : '—' }}</td>
                    <td class="text-slate-500 text-xs">{{ $hh->dag ?? '—' }}</td>
                    <td class="font-mono text-xs font-bold text-slate-800">{{ $hh->household_id }}</td>
                    <td style="min-width:160px">
                        <div class="text-slate-700 font-medium text-xs">{{ $hh->head_name ?? '—' }}</div>
                        @if($hh->head_phone)
                        <div class="text-slate-400 font-mono text-xs">{{ $hh->head_phone }}</div>
                        @endif
                        @if($hh->contact_name || $hh->contact_phone)
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <div class="text-slate-600 text-xs">{{ $hh->contact_name ?? '—' }}</div>
                            @if($hh->contact_phone)
                            <div class="text-slate-400 font-mono text-xs">{{ $hh->contact_phone }}</div>
                            @endif
                        </div>
                        @endif
                    </td>
                    <td class="text-slate-600 text-xs">{{ $hh->moment_visite ?? '—' }}</td>
                    <td class="text-slate-600 text-xs" style="max-width:140px">{{ $hh->adresse ?? '—' }}</td>
                    <td class="text-center font-bold text-slate-700">{{ $hh->nb_moustiquaires_etude ?? '—' }}</td>
                    <td style="max-width:200px">
                        @if(!empty($hh->net_codes))
                            @foreach($hh->net_codes as $code)
                                <span class="net-code">{{ $code }}</span>
                            @endforeach
                        @else
                            <span class="text-slate-400 text-xs">—</span>
                        @endif
                    </td>
                    {{-- Statut REDCap (basé sur formulaire AE soumis) --}}
                    <td class="text-center">
                        @if($isVisited)
                            <span class="badge badge-ok text-xs">Visité</span>
                        @else
                            <span class="badge badge-no text-xs">En attente</span>
                        @endif
                    </td>
                    {{-- Complétion manuelle (toggle) --}}
                    <td class="text-center" id="status-{{ $hh->id }}">
                        @if($hh->form_completed)
                            <span class="badge badge-ok text-xs">
                                ✓<br>
                                <span class="font-normal" style="font-size:.6rem">
                                    {{ $hh->form_completed_at?->format('d/m/Y') }}
                                </span>
                            </span>
                        @else
                            <span class="badge" style="background:#F1F5F9;color:#94A3B8;font-size:.65rem">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($households->hasPages())
        <div class="mt-4 flex items-center justify-between gap-4 flex-wrap">
            <p class="text-xs text-slate-500">
                Page {{ $households->currentPage() }} / {{ $households->lastPage() }}
                &nbsp;·&nbsp;
                Ménages {{ $households->firstItem() }}–{{ $households->lastItem() }}
                sur {{ $households->total() }}
            </p>
            <div class="pagination-links">
                {{ $households->links('pagination::tailwind') }}
            </div>
        </div>
        @endif

        @endif
    </div>

</div>{{-- /container --}}

{{-- ═══════════════════════════ MODALES ═══════════════════════════════════════ --}}

{{-- Modal : Nouvelle sélection aléatoire --}}
<div id="modal-select" class="modal-bg" onclick="closeOnBg(event, 'modal-select')">
    <div class="modal-box">
        <button onclick="closeModal('modal-select')"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        <div class="modal-title">🎲 Nouvelle sélection aléatoire</div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-xs text-amber-800">
            <b>Attention :</b> Cette action supprime la sélection existante pour le(s) bras choisi(s)
            et en génère une nouvelle. Les données de complétion seront perdues.
        </div>
        <form method="POST" action="{{ route('ae.selection.select') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Bras à sélectionner</label>
                <select name="arm" class="form-select">
                    <option value="">Les deux bras (PD + G2)</option>
                    <option value="PD">PERMANET DUAL (PD) uniquement</option>
                    <option value="G2">INTERCEPTOR G2 (G2) uniquement</option>
                </select>
            </div>
            <div>
                <label class="form-label">Nombre de ménages par bras</label>
                <input type="number" name="count" value="331" min="1" max="500" class="form-input" required>
                <p class="text-xs text-slate-400 mt-1">Protocole : 331 ménages par bras</p>
            </div>
            <div>
                <label class="form-label">Notes (optionnel)</label>
                <input type="text" name="notes" class="form-input" placeholder="Ex: Sélection initiale — juin 2026">
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeModal('modal-select')" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">Lancer la sélection</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal : Ajouter des ménages --}}
<div id="modal-add" class="modal-bg" onclick="closeOnBg(event, 'modal-add')">
    <div class="modal-box">
        <button onclick="closeModal('modal-add')"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        <div class="modal-title">➕ Ajouter des ménages à la sélection</div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-xs text-blue-800">
            Les ménages ajoutés seront pris <b>hors de la sélection existante</b> (pas de doublons).
        </div>
        <form method="POST" action="{{ route('ae.selection.add-more') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Bras</label>
                <select name="arm" class="form-select" required>
                    <option value="">-- Choisir un bras --</option>
                    <option value="PD">PERMANET DUAL (PD)</option>
                    <option value="G2">INTERCEPTOR G2 (G2)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Nombre de ménages à ajouter</label>
                <input type="number" name="count" value="20" min="1" max="200" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Notes (optionnel)</label>
                <input type="text" name="notes" class="form-input" placeholder="Ex: Remplacement de ménages inaccessibles">
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeModal('modal-add')" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal : Réinitialiser --}}
<div id="modal-reset" class="modal-bg" onclick="closeOnBg(event, 'modal-reset')">
    <div class="modal-box">
        <button onclick="closeModal('modal-reset')"
                class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        <div class="modal-title">⚠️ Réinitialiser la sélection</div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-xs text-red-800">
            <b>Action irréversible.</b> Toutes les données de sélection et de complétion pour le(s)
            bras choisi(s) seront définitivement supprimées.
        </div>
        <form method="POST" action="{{ route('ae.selection.reset') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Réinitialiser pour</label>
                <select name="arm" class="form-select">
                    <option value="">Les deux bras (tout supprimer)</option>
                    <option value="PD">PERMANET DUAL (PD) uniquement</option>
                    <option value="G2">INTERCEPTOR G2 (G2) uniquement</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="confirm-reset" onchange="document.getElementById('btn-reset').disabled=!this.checked"
                       class="w-4 h-4 accent-red-600">
                <label for="confirm-reset" class="text-sm text-slate-700">
                    Je confirme vouloir réinitialiser la sélection
                </label>
            </div>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeModal('modal-reset')" class="btn-secondary">Annuler</button>
                <button type="submit" id="btn-reset" disabled class="btn-danger" style="opacity:.4">
                    Réinitialiser
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── JavaScript ──────────────────────────────────────────────────────────── --}}
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Auto-filtrage ──────────────────────────────────────────────────────────────
(function () {
    const form    = document.getElementById('filter-form');
    const spinner = document.getElementById('filter-spinner');
    if (!form) return;

    function submit() {
        spinner.classList.remove('hidden');
        spinner.classList.add('inline-flex');
        // Remettre la pagination à la page 1 à chaque nouveau filtre
        const pageInput = form.querySelector('input[name="page"]');
        if (pageInput) pageInput.remove();
        form.submit();
    }

    // Soumission immédiate sur changement de select
    form.querySelectorAll('select').forEach(function (sel) {
        sel.addEventListener('change', submit);
    });

    // Debounce 400 ms sur le champ texte
    var debTimer;
    var searchInput = form.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debTimer);
            debTimer = setTimeout(submit, 400);
        });
    }
})();

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function closeOnBg(e, id) { if (e.target.id === id) closeModal(id); }

function toggleCompleted(id, btn) {
    btn.disabled = true;
    fetch(`/adverse-events/selection/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { btn.disabled = false; return; }

        const done = data.form_completed;
        const row  = document.getElementById(`row-${id}`);
        const stat = document.getElementById(`status-${id}`);

        // Update toggle button
        btn.classList.toggle('done', done);
        btn.innerHTML = done
            ? `<svg fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
               </svg>`
            : '';
        btn.title = done ? 'Marquer non complété' : 'Marquer complété';

        // Update row opacity
        row.style.opacity = done ? '.6' : '1';

        // Update status badge
        if (done) {
            stat.innerHTML = `<span class="badge badge-ok text-xs">Complété<br>
                <span style="font-size:.6rem;font-weight:400">${data.completed_at}</span></span>`;
        } else {
            stat.innerHTML = `<span class="badge badge-no text-xs">En attente</span>`;
        }

        btn.disabled = false;
    })
    .catch(() => { btn.disabled = false; });
}

// Activer le bouton Reset si checkbox cochée
document.getElementById('confirm-reset')?.addEventListener('change', function() {
    document.getElementById('btn-reset').style.opacity = this.checked ? '1' : '.4';
});
</script>
</body>
</html>
