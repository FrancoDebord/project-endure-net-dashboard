<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accumulation des MII — Suivi 6 mois | ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red:#C41230; --red-dark:#8B0D21; --dark:#1A1A1A;
                --series-1:#2a78d6; --series-2:#eb6834; --track:#EEF2F6; }
        body { background:#F1F5F9; font-family: system-ui, sans-serif; }
        .card  { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }
        .section-title { font-size:.78rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
                         color:var(--dark); border-left:4px solid var(--red); padding-left:.65rem; }
        .btn { border-radius:.5rem; padding:.4rem .9rem; font-size:.75rem; font-weight:700;
               display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; transition:background .15s; }
        .btn-primary { background:var(--red); color:#fff; } .btn-primary:hover { background:var(--red-dark); }
        .btn-secondary { background:#fff; color:var(--dark); border:1.5px solid #E2E8F0; } .btn-secondary:hover { background:#F8FAFC; }
        .tbl { width:100%; border-collapse:collapse; font-size:.75rem; }
        .tbl thead th { background:#F1F5F9; color:#475569; font-size:.65rem; font-weight:700; letter-spacing:.05em;
                        text-transform:uppercase; padding:.55rem .6rem; border-bottom:2px solid #CBD5E1; white-space:nowrap; text-align:left; }
        .tbl tbody tr { border-bottom:1px solid #E2E8F0; } .tbl tbody tr:hover { background:#F8FAFC; }
        .tbl tbody td { padding:.45rem .6rem; vertical-align:middle; }
        .num { text-align:right; font-variant-numeric: tabular-nums; }
        .st { border-radius:.35rem; padding:.1rem .45rem; font-size:.65rem; font-weight:700; white-space:nowrap; border:1px solid; }
        .st-realise     { background:#DCFCE7; color:#15803D; border-color:#BBF7D0; }
        .st-refus       { background:#FEE2E2; color:#991B1B; border-color:#FECACA; }
        .st-absent      { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
        .st-introuvable { background:#EDE9FE; color:#5B21B6; border-color:#DDD6FE; }
        .st-non_visite  { background:#F8FAFC; color:#94A3B8; border-color:#E2E8F0; }
        .sev-critical { background:#FEE2E2; color:#991B1B; border-color:#FECACA; }
        .sev-warning  { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
        .sev-info     { background:#E0F2FE; color:#075985; border-color:#BAE6FD; }
        .prog { background:#E2E8F0; border-radius:9999px; height:.45rem; overflow:hidden; display:flex; gap:2px; }
        .prog > span { height:.45rem; display:block; }
        .tp-tab { padding:.7rem 1.1rem; font-size:.8rem; font-weight:700; color:#64748B; border-bottom:3px solid transparent; white-space:nowrap; }
        .tp-tab.active { color:var(--red); border-color:var(--red); }
        .sel { border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.35rem .5rem; font-size:.75rem; background:#fff; }
        /* Barres de résultats (deux séries côte à côte) */
        .bar-track { background:var(--track); height:10px; border-radius:0 4px 4px 0; position:relative; }
        .bar-fill  { height:10px; border-radius:0 4px 4px 0; min-width:0; }
        .bar-row + .bar-row { margin-top:2px; }
        .legend-dot { width:.7rem; height:.7rem; border-radius:3px; display:inline-block; }
    </style>
</head>
<body>
@php
    $f = $filters ?? [];
    $url = fn(array $over = []) => route('accumulation.index', array_filter(array_merge(['tab' => $tab], $f, $over), fn($v) => $v !== '' && $v !== null));
    $pct = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
    $statusOrder = ['realise', 'refus', 'absent', 'introuvable', 'non_visite'];
@endphp

<header style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="text-white">
    <div class="max-w-screen-2xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-widest text-white/70">ENDURE-Net · Suivi 6 mois · KoboToolbox</p>
            <h1 class="text-lg font-extrabold">Accumulation, stockage et utilisation des MII</h1>
            @isset($data)
                <p class="text-[11px] text-white/70">Formulaire version {{ $data['formVersion'] }} · données Kobo du {{ $data['fetchedAt'] }}
                    · un entretien par ménage ayant d'anciennes moustiquaires à la baseline</p>
            @endisset
        </div>
        <div class="flex gap-2">
            <a href="{{ $url(['refresh' => 1]) }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30" title="Recharger les soumissions depuis KoboToolbox">⟳ Actualiser</a>
            <a href="{{ route('dashboard') }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30">← Retour au dashboard</a>
        </div>
    </div>
</header>

<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

@if (!empty($error))
    <div class="card p-6 border-l-4" style="border-color:var(--red)">
        <p class="font-bold text-red-700">Impossible de charger les données KoboToolbox</p>
        <p class="text-sm text-gray-600 mt-1">{{ $error }}</p>
    </div>
@else

    <nav class="card px-4 flex items-center gap-1 overflow-x-auto">
        @foreach ($tabs as $tk => $tl)
            <a href="{{ route('accumulation.index', ['tab' => $tk]) }}" class="tp-tab {{ $tab === $tk ? 'active' : '' }}">
                {{ $tl }}
                @if ($tk === 'queries' && count($data['queries']))<span class="ml-1 st sev-warning">{{ count($data['queries']) }}</span>@endif
            </a>
        @endforeach
    </nav>

{{-- ════════════════════════════ AVANCEMENT ════════════════════════════ --}}
@if ($tab === 'avancement')
    @php $s = $data['summary']; @endphp
    <section class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4">
        <div class="card p-4 border-t-4" style="border-color:var(--red)">
            <p class="text-[11px] text-gray-400 uppercase tracking-widest">Ménages attendus</p>
            <p class="text-3xl font-extrabold" style="color:var(--red)">{{ number_format($s['expected'], 0, ',', ' ') }}</p>
            <p class="text-xs text-gray-400">avec anciennes MII à la baseline</p>
        </div>
        @foreach ($statusOrder as $sk)
            <div class="card p-4 border-t-4" style="border-color:{{ $statusColors[$sk] }}">
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">{{ $statusLabels[$sk] }}</p>
                <p class="text-3xl font-extrabold text-gray-800">{{ number_format($s[$sk], 0, ',', ' ') }}</p>
                <p class="text-xs text-gray-400">{{ $pct($s[$sk], $s['expected']) }} %</p>
            </div>
        @endforeach
        <div class="card p-4 border-t-4" style="border-color:#0F766E">
            <p class="text-[11px] text-gray-400 uppercase tracking-widest">Stockent des MII</p>
            <p class="text-3xl font-extrabold text-gray-800">{{ $s['stores'] }}</p>
            <p class="text-xs text-gray-400">{{ $pct($s['stores'], $s['realise']) }} % des entretiens (B1 = Oui)</p>
        </div>
    </section>

    <section class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="section-title">Avancement global</h2>
            <p class="text-xs text-gray-500">{{ $s['submissions'] }} soumission(s) reçue(s) · {{ $s['visited'] }} ménage(s) visité(s)
                @if ($s['outside']) · <span class="text-red-700 font-bold">{{ $s['outside'] }} hors liste</span>@endif</p>
        </div>
        <div class="prog" style="height:1rem">
            @foreach ($statusOrder as $sk) @if ($s[$sk] > 0)
                <span style="height:1rem;width:{{ $pct($s[$sk], $s['expected']) }}%;background:{{ $statusColors[$sk] }}" title="{{ $statusLabels[$sk] }} : {{ $s[$sk] }} ({{ $pct($s[$sk], $s['expected']) }} %)"></span>
            @endif @endforeach
        </div>
        <div class="flex flex-wrap gap-4 mt-2 text-xs text-gray-600">
            @foreach ($statusOrder as $sk)
                <span class="inline-flex items-center gap-1"><span class="legend-dot" style="background:{{ $statusColors[$sk] }}"></span>{{ $statusLabels[$sk] }} ({{ $s[$sk] }})</span>
            @endforeach
        </div>
    </section>

    @php
        $blocks = [
            ['Par village', $data['byVillage'], 'Village'],
            ['Par cohorte', $data['byCohorte'], 'Cohorte'],
            ['Par bras', $data['byBras'], 'Bras'],
            ['Par DAG (REDCap)', $data['byDag'], 'DAG'],
            ['Par grappe', $data['byGrappe'], 'Grappe'],
        ];
    @endphp
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach ($blocks as [$title, $rows, $first])
        <section class="card p-5 {{ $first === 'Grappe' ? 'xl:col-span-2' : '' }}">
            <h2 class="section-title mb-3">{{ $title }}</h2>
            <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr>
                    <th>{{ $first }}</th><th class="num">Attendus</th><th class="num">Réalisés</th><th class="num">Refus</th>
                    <th class="num">Absents</th><th class="num">Introuv.</th><th class="num">Non visités</th><th style="width:28%">Avancement</th>
                </tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="font-semibold whitespace-nowrap">{{ $r['label'] }}</td>
                        <td class="num">{{ $r['expected'] }}</td>
                        <td class="num font-bold text-green-700">{{ $r['realise'] }}</td>
                        <td class="num">{{ $r['refus'] ?: '' }}</td>
                        <td class="num">{{ $r['absent'] ?: '' }}</td>
                        <td class="num">{{ $r['introuvable'] ?: '' }}</td>
                        <td class="num text-gray-400">{{ $r['non_visite'] }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="prog flex-1">
                                    @foreach (['realise', 'refus', 'absent', 'introuvable'] as $sk) @if ($r[$sk] > 0)
                                        <span style="width:{{ $pct($r[$sk], $r['expected']) }}%;background:{{ $statusColors[$sk] }}"></span>
                                    @endif @endforeach
                                </div>
                                <span class="text-[11px] font-bold text-gray-600 w-12 text-right">{{ $pct($r['realise'], $r['expected']) }} %</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </section>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="card p-5 xl:col-span-2">
            <h2 class="section-title mb-3">Activité par binôme</h2>
            <table class="tbl">
                <thead><tr><th>Binôme</th><th class="num">Soumissions</th><th class="num">Ménages</th><th class="num">Entretiens</th>
                    <th class="num">Refus</th><th class="num">Absents</th><th class="num">Introuv.</th><th>Période</th></tr></thead>
                <tbody>
                @forelse ($data['byBinome'] as $b)
                    <tr>
                        <td><span class="font-bold">{{ $b['code'] }}</span> <span class="text-gray-500">{{ $b['label'] }}</span></td>
                        <td class="num">{{ $b['submissions'] }}</td><td class="num">{{ $b['households'] }}</td>
                        <td class="num font-bold text-green-700">{{ $b['realise'] }}</td>
                        <td class="num">{{ $b['refus'] ?: '' }}</td><td class="num">{{ $b['absent'] ?: '' }}</td><td class="num">{{ $b['introuvable'] ?: '' }}</td>
                        <td class="text-gray-500">{{ $b['first'] === $b['last'] ? $b['first'] : $b['first'] . ' → ' . $b['last'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-6">Aucune soumission.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section class="card p-5">
            <h2 class="section-title mb-3">Soumissions par jour</h2>
            <table class="tbl">
                <thead><tr><th>Date de visite</th><th class="num">Soumissions</th><th class="num">Entretiens</th></tr></thead>
                <tbody>
                @forelse (array_reverse($data['byDate']) as $d)
                    <tr><td>{{ $d['date'] ?: '—' }}</td><td class="num">{{ $d['submissions'] }}</td><td class="num font-bold text-green-700">{{ $d['realise'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-gray-400 py-6">Aucune soumission.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endif

{{-- ════════════════════════════ MÉNAGES ════════════════════════════ --}}
@if ($tab === 'menages')
    <section class="card p-4">
        <form method="GET" action="{{ route('accumulation.index') }}" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="tab" value="menages">
            <label class="text-[11px] text-gray-500">Village<br>
                <select name="village" class="sel" onchange="this.form.grappe.value='';this.form.submit()">
                    <option value="">Tous</option>
                    @foreach ($villages as $v)<option {{ $f['village'] === $v ? 'selected' : '' }}>{{ $v }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">Grappe<br>
                <select name="grappe" class="sel">
                    <option value="">Toutes</option>
                    @foreach ($grappes as $g)<option {{ $f['grappe'] === $g ? 'selected' : '' }}>{{ $g }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">DAG<br>
                <select name="dag" class="sel">
                    <option value="">Tous</option>
                    @foreach ($dags as $d)<option {{ $f['dag'] === $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">Cohorte<br>
                <select name="cohorte" class="sel">
                    <option value="">Toutes</option>
                    @foreach (['Cohorte A', 'Cohorte B'] as $c)<option {{ $f['cohorte'] === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">Bras<br>
                <select name="bras" class="sel">
                    <option value="">Tous</option>
                    @foreach (['PermaNet Dual', 'Interceptor G2'] as $b)<option {{ $f['bras'] === $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">Statut<br>
                <select name="status" class="sel">
                    <option value="">Tous</option>
                    <option value="restants" {{ $f['status'] === 'restants' ? 'selected' : '' }}>Restants (non réalisés)</option>
                    @foreach ($statusLabels as $sk => $sl)<option value="{{ $sk }}" {{ $f['status'] === $sk ? 'selected' : '' }}>{{ $sl }}</option>@endforeach
                </select></label>
            <label class="text-[11px] text-gray-500">Recherche<br>
                <input type="text" name="search" value="{{ $f['search'] }}" placeholder="ID, chef, téléphone…" class="sel w-48"></label>
            <button class="btn btn-primary">Filtrer</button>
            <a href="{{ route('accumulation.index', ['tab' => 'menages']) }}" class="btn btn-secondary">Réinitialiser</a>
        </form>
        @php $pdfParams = array_filter(array_merge($f, ['status' => '', 'search' => '']), fn($v) => $v !== ''); @endphp
        <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-100">
            <span class="text-xs text-gray-500 mr-1">Listes terrain (par grappe, filtres village / grappe / DAG / cohorte / bras appliqués) :</span>
            <a href="{{ route('accumulation.pdf', $pdfParams) }}" class="btn btn-primary">⬇ PDF ménages restants</a>
            <a href="{{ route('accumulation.pdf', $pdfParams + ['only' => 'all']) }}" class="btn btn-secondary">⬇ PDF tous les ménages</a>
            <a href="{{ route('accumulation.pdf', $pdfParams + ['format' => 'html']) }}" target="_blank" class="btn btn-secondary">🖨 Version imprimable</a>
        </div>
    </section>

    <section class="card p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-bold text-gray-700">{{ $filteredCount }} ménage(s)</p>
            @if ($pages > 1)
                <div class="flex gap-1 text-xs">
                    @if ($page > 1)<a class="btn btn-secondary" href="{{ $url(['page' => $page - 1]) }}">‹</a>@endif
                    <span class="px-2 py-1 text-gray-500">Page {{ $page }} / {{ $pages }}</span>
                    @if ($page < $pages)<a class="btn btn-secondary" href="{{ $url(['page' => $page + 1]) }}">›</a>@endif
                </div>
            @endif
        </div>
        <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr>
                <th>N°</th><th>ID ménage</th><th>Grappe</th><th>DAG</th><th>Cohorte</th><th>Bras</th><th class="num">Anc. MII</th>
                <th>Chef de ménage · Tél.</th><th>Point de contact · Tél.</th><th>Statut</th><th class="num">Visites</th><th>Date</th><th>Binôme</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $i => $r)
                <tr>
                    <td class="text-gray-400">{{ $i + 1 }}</td>
                    <td class="font-mono font-semibold">{{ $r['id'] }}</td>
                    <td>{{ $r['grappe'] }}</td>
                    <td class="text-gray-500">{{ $r['dagLabel'] }}</td>
                    <td>{{ $r['cohorte'] }}</td>
                    <td>{{ $r['bras'] }}</td>
                    <td class="num">{{ $r['nbOld'] }}</td>
                    <td>{{ $r['chef'] ?: '—' }}<br><span class="text-gray-400">{{ $r['chefPhone'] }}</span></td>
                    <td>{{ $r['contact'] ?: '—' }}<br><span class="text-gray-400">{{ $r['contactPhone'] }}</span></td>
                    <td><span class="st st-{{ $r['status'] }}">{{ $statusLabels[$r['status']] }}</span>
                        @if ($r['stores'])<span class="text-[10px] text-teal-700 font-bold ml-1" title="Le ménage stocke des moustiquaires non utilisées (B1 = Oui)">stocke</span>@endif</td>
                    <td class="num">{{ $r['attempts'] ?: '' }}</td>
                    <td>{{ $r['date'] }}</td>
                    <td class="text-gray-500">{{ $r['binome'] }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center text-gray-400 py-8">Aucun ménage pour ces filtres.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </section>
@endif

{{-- ════════════════════════════ RÉSULTATS ════════════════════════════ --}}
@if ($tab === 'resultats')
    @php
        // Couleur fixée par entité (jamais par rang)
        $seriesOrder = $compare === 'bras' ? ['PermaNet Dual' => 'var(--series-1)', 'Interceptor G2' => 'var(--series-2)']
                                            : ['Cohorte A' => 'var(--series-1)', 'Cohorte B' => 'var(--series-2)'];
        $nInterviews = $data['summary']['realise'];
    @endphp
    <section class="card p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 text-xs">Comparer par :</span>
            <a href="{{ route('accumulation.index', ['tab' => 'resultats', 'compare' => 'bras']) }}" class="btn {{ $compare === 'bras' ? 'btn-primary' : 'btn-secondary' }}">Bras</a>
            <a href="{{ route('accumulation.index', ['tab' => 'resultats', 'compare' => 'cohorte']) }}" class="btn {{ $compare === 'cohorte' ? 'btn-primary' : 'btn-secondary' }}">Cohorte</a>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-700">
            @foreach ($seriesOrder as $name => $col)
                <span class="inline-flex items-center gap-1.5"><span class="legend-dot" style="background:{{ $col }}"></span>{{ $name }}</span>
            @endforeach
            <span class="text-gray-400">Base : {{ $nInterviews }} entretien(s) réalisé(s) · % des répondants à chaque question</span>
        </div>
    </section>

    @if ($nInterviews === 0)
        <div class="card p-8 text-center text-gray-400">Aucun entretien réalisé pour le moment.</div>
    @endif

    @foreach ($data['results'] as $secKey => $sec)
    <section class="card p-5">
        <h2 class="section-title mb-4">{{ $sec['label'] }}</h2>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-x-10 gap-y-7">
        @foreach ($sec['items'] as $it)
            @php $groups = $it[$compare]; @endphp
            <div>
                <p class="text-sm font-semibold text-gray-800 leading-snug">{{ $it['label'] }}</p>
                <p class="text-[11px] text-gray-400 mb-2">
                    {{ $it['multi'] ? 'Plusieurs réponses possibles · ' : '' }}n = {{ $it['all']['n'] }}
                    @foreach ($seriesOrder as $name => $_c) · {{ $name }} : {{ $groups[$name]['n'] ?? 0 }} @endforeach
                </p>
                @if ($it['all']['n'] === 0)
                    <p class="text-xs text-gray-400 italic">Aucune réponse (question non posée ou pas encore d'entretien).</p>
                @else
                <table class="w-full text-xs">
                    @foreach ($it['options'] as $code => $optLabel)
                        @php $totalOpt = $it['all']['counts'][$code] ?? 0; @endphp
                        <tr class="align-top">
                            <td class="py-1 pr-3 text-gray-700 w-2/5">{{ $optLabel }}
                                <span class="text-gray-400">· {{ $pct($totalOpt, $it['all']['n']) }} %</span></td>
                            <td class="py-1">
                                @foreach ($seriesOrder as $name => $col)
                                    @php $g = $groups[$name] ?? ['n' => 0, 'counts' => []]; $c = $g['counts'][$code] ?? 0; $p = $pct($c, $g['n']); @endphp
                                    <div class="bar-row flex items-center gap-2" title="{{ $name }} — {{ $optLabel }} : {{ $c }} / {{ $g['n'] }} ({{ $p }} %)">
                                        <div class="bar-track flex-1"><div class="bar-fill" style="width:{{ $p }}%;background:{{ $col }}"></div></div>
                                        <span class="w-20 text-right tabular-nums text-gray-600">{{ $p }} % <span class="text-gray-400">({{ $c }})</span></span>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </table>
                @endif
            </div>
        @endforeach
        </div>

        @if ($secKey === 'observation' && count($data['photos']))
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Photos reçues</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
                @foreach ($data['photos'] as $ph)
                    <div class="border rounded-lg overflow-hidden text-[11px]">
                        @if ($ph['available'])
                            <a href="{{ route('accumulation.photo', [$ph['subId'], $ph['field']]) }}" target="_blank">
                                <img src="{{ route('accumulation.photo', [$ph['subId'], $ph['field']]) }}" loading="lazy" alt="{{ $ph['label'] }} {{ $ph['hh'] }}" class="w-full h-32 object-cover bg-gray-100">
                            </a>
                        @else
                            <div class="h-32 flex items-center justify-center bg-gray-50 text-gray-400 text-center px-2">Fichier non reçu</div>
                        @endif
                        <div class="p-1.5"><b class="font-mono">{{ $ph['hh'] }}</b><br><span class="text-gray-500">{{ $ph['label'] }} · {{ $ph['date'] }}</span></div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
    @endforeach
@endif

{{-- ════════════════════════════ RÉPONSES OUVERTES ════════════════════════════ --}}
@if ($tab === 'ouvertes')
    <section class="card p-4 flex flex-wrap items-center gap-3">
        <input type="search" id="openFilter" placeholder="Rechercher dans les réponses ou un ID ménage…" class="sel w-80" oninput="filterOpen()">
        <select id="openBras" class="sel" onchange="filterOpen()">
            <option value="">Tous les bras</option><option>PermaNet Dual</option><option>Interceptor G2</option>
        </select>
        <select id="openCoh" class="sel" onchange="filterOpen()">
            <option value="">Toutes les cohortes</option><option>Cohorte A</option><option>Cohorte B</option>
        </select>
    </section>
    @forelse ($data['openAnswers'] as $oa)
        <section class="card p-5 open-block">
            <p class="text-[11px] uppercase tracking-wider text-gray-400">{{ $oa['section'] }} · {{ $oa['name'] }}</p>
            <h2 class="text-sm font-bold text-gray-800 mb-3">{{ $oa['label'] }} <span class="text-gray-400 font-normal">({{ count($oa['answers']) }})</span></h2>
            <div class="space-y-2">
                @foreach ($oa['answers'] as $a)
                    <div class="open-item border-l-4 pl-3 py-1" style="border-color:{{ $a['bras'] === 'PermaNet Dual' ? 'var(--series-1)' : 'var(--series-2)' }}"
                         data-bras="{{ $a['bras'] }}" data-coh="{{ $a['cohorte'] }}" data-text="{{ mb_strtolower($a['text'] . ' ' . $a['hh']) }}">
                        <p class="text-sm text-gray-800 whitespace-pre-line">{{ $a['text'] }}</p>
                        <p class="text-[11px] text-gray-400"><span class="font-mono">{{ $a['hh'] }}</span> · {{ $a['bras'] }} · {{ $a['cohorte'] }} · {{ $a['date'] }} · {{ $a['binome'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card p-8 text-center text-gray-400">Aucune réponse ouverte pour le moment.</div>
    @endforelse
    <script>
        function filterOpen() {
            const q = document.getElementById('openFilter').value.trim().toLowerCase();
            const b = document.getElementById('openBras').value, c = document.getElementById('openCoh').value;
            document.querySelectorAll('.open-block').forEach(block => {
                let shown = 0;
                block.querySelectorAll('.open-item').forEach(el => {
                    const ok = (!q || el.dataset.text.includes(q)) && (!b || el.dataset.bras === b) && (!c || el.dataset.coh === c);
                    el.style.display = ok ? '' : 'none'; if (ok) shown++;
                });
                block.style.display = shown ? '' : 'none';
            });
        }
    </script>
@endif

{{-- ════════════════════════════ QUERIES ════════════════════════════ --}}
@if ($tab === 'queries')
    @php $allQ = collect($data['queries']); @endphp
    <section class="grid grid-cols-3 gap-4">
        @foreach (['critical' => 'Critiques', 'warning' => 'Avertissements', 'info' => 'Informatives'] as $sv => $sl)
            <a href="{{ route('accumulation.index', ['tab' => 'queries', 'severity' => $querySev === $sv ? '' : $sv]) }}"
               class="card p-4 border-t-4 {{ $querySev === $sv ? 'ring-2 ring-offset-1 ring-gray-300' : '' }}"
               style="border-color:{{ ['critical' => '#DC2626', 'warning' => '#D97706', 'info' => '#0284C7'][$sv] }}">
                <p class="text-[11px] text-gray-400 uppercase tracking-widest">{{ $sl }}</p>
                <p class="text-3xl font-extrabold text-gray-800">{{ $allQ->where('severity', $sv)->count() }}</p>
            </a>
        @endforeach
    </section>
    <section class="card p-4">
        <form method="GET" action="{{ route('accumulation.index') }}" class="flex flex-wrap items-end gap-2 mb-3">
            <input type="hidden" name="tab" value="queries">
            <input type="hidden" name="severity" value="{{ $querySev }}">
            <select name="code" class="sel" onchange="this.form.submit()">
                <option value="">Tous les codes</option>
                @foreach ($queryCodes as $code => [$sv, $title])
                    <option value="{{ $code }}" {{ $queryCode === $code ? 'selected' : '' }}>{{ $code }} — {{ $title }} ({{ $allQ->where('code', $code)->count() }})</option>
                @endforeach
            </select>
            @if ($querySev || $queryCode)<a href="{{ route('accumulation.index', ['tab' => 'queries']) }}" class="btn btn-secondary">Réinitialiser</a>@endif
        </form>
        <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Code</th><th>Sévérité</th><th>Ménage</th><th>Problème</th><th>Détail</th><th>Date visite</th><th>Binôme</th><th>ID Kobo</th></tr></thead>
            <tbody>
            @forelse ($queries as $qq)
                <tr class="align-top">
                    <td class="font-mono font-bold">{{ $qq['code'] }}</td>
                    <td><span class="st sev-{{ $qq['severity'] }}">{{ ['critical' => 'Critique', 'warning' => 'Avertissement', 'info' => 'Info'][$qq['severity']] }}</span></td>
                    <td class="font-mono">{{ $qq['hh'] }}</td>
                    <td class="font-semibold">{{ $qq['title'] }}</td>
                    <td class="text-gray-600 max-w-xl">{{ $qq['detail'] }}</td>
                    <td>{{ $qq['date'] }}</td>
                    <td class="text-gray-500">{{ $qq['binome'] }}</td>
                    <td class="text-gray-400 font-mono">{{ $qq['subId'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-gray-400 py-8">Aucune query.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </section>
@endif

@endif
</main>
</body>
</html>
