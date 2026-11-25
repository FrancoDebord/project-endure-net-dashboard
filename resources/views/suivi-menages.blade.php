<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Cohorte {{ $cfg['letter'] }} — Ménages | ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red:#C41230; --red-dark:#8B0D21; --red-lite:#FDECED; --dark:#1A1A1A; }
        body { background:#F1F5F9; font-family: system-ui, sans-serif; }
        .card  { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }
        .section-title {
            font-size:.78rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
            color:var(--dark); border-left:4px solid var(--red); padding-left:.65rem;
        }
        .btn { border-radius:.5rem; padding:.4rem .9rem; font-size:.75rem; font-weight:700;
               display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; transition:background .15s; }
        .btn-primary { background:var(--red); color:#fff; }
        .btn-primary:hover { background:var(--red-dark); }
        .btn-secondary { background:#fff; color:var(--dark); border:1.5px solid #E2E8F0; }
        .btn-secondary:hover { background:#F8FAFC; }
        .tbl { width:100%; border-collapse:collapse; font-size:.75rem; }
        .tbl thead th { background:#F1F5F9; color:#475569; font-size:.65rem; font-weight:700; letter-spacing:.05em;
                        text-transform:uppercase; padding:.55rem .6rem; border-bottom:2px solid #CBD5E1; white-space:nowrap;
                        position:sticky; top:0; z-index:1; }
        .tbl tbody tr { border-bottom:1px solid #E2E8F0; }
        .tbl tbody tr:hover { background:#F8FAFC; }
        .tbl tbody td { padding:.45rem .6rem; vertical-align:middle; }
        .st { border-radius:.35rem; padding:.1rem .45rem; font-size:.65rem; font-weight:700; white-space:nowrap; border:1px solid; }
        .st-realise     { background:#DCFCE7; color:#15803D; border-color:#BBF7D0; }
        .st-non_realise { background:#FEE2E2; color:#991B1B; border-color:#FECACA; }
        .st-en_cours    { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
        .st-non_visite  { background:#F8FAFC; color:#94A3B8; border-color:#E2E8F0; }
        .prog { background:#E2E8F0; border-radius:9999px; height:.45rem; overflow:hidden; }
        .prog-fill { height:.45rem; }
        .tp-tab { padding:.6rem 1.1rem; font-size:.8rem; font-weight:700; color:#64748B; border-bottom:3px solid transparent; }
        .tp-tab.active { color:var(--red); border-color:var(--red); }
    </style>
</head>
<body>
@php
    $q = fn(array $over = []) => route($cfg['route'], array_filter(array_merge($filters, $over), fn($v) => $v !== ''));
    $pct = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
    $shortStatus = $cfg['short'];
    $statusColors = ['non_visite' => '#94A3B8', 'en_cours' => '#D97706', 'non_realise' => '#DC2626', 'realise' => '#16A34A'];
    $hasNotDone = isset($statusLabels['non_realise']);
@endphp

{{-- ── Barre supérieure ────────────────────────────────────────────── --}}
<header style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="text-white">
    <div class="max-w-screen-2xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-widest text-white/70">ENDURE-Net · Suivi Cohorte {{ $cfg['letter'] }}</p>
            <h1 class="text-lg font-extrabold">Ménages à suivre — listes par DAG et suivi individuel</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ $q(['refresh' => 1]) }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30"
               title="Recharger les données de suivi depuis REDCap">⟳ Actualiser</a>
            <a href="{{ route('dashboard') }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30">← Retour au dashboard</a>
        </div>
    </div>
</header>

<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

    {{-- ── Choix du suivi ──────────────────────────────────────────── --}}
    <nav class="card px-4 flex items-center gap-1 overflow-x-auto">
        @foreach ($suivis as $sk => $sl)
            <a href="{{ $q(['suivi' => $sk]) }}" class="tp-tab {{ $filters['suivi'] === $sk ? 'active' : '' }}">Suivi {{ $sl }}</a>
        @endforeach
    </nav>

    {{-- ── KPIs statut ─────────────────────────────────────────────── --}}
    <section class="grid grid-cols-2 md:grid-cols-{{ count($statusLabels) + 1 }} gap-4">
        <div class="card p-4 border-t-4" style="border-color:var(--red)">
            <p class="text-[11px] text-gray-400 uppercase tracking-widest">Ménages attendus</p>
            <p class="text-3xl font-extrabold" style="color:var(--red)">{{ $total }}</p>
            <p class="text-xs text-gray-400">Cohorte {{ $cfg['letter'] }} consentis</p>
        </div>
        @foreach ($statusLabels as $sk => $_lbl) @php $col = $statusColors[$sk]; @endphp
        <a href="{{ $q(['status' => $filters['status'] === $sk ? '' : $sk]) }}"
           class="card p-4 border-t-4 hover:ring-2 hover:ring-gray-200 {{ $filters['status'] === $sk ? 'ring-2 ring-gray-300' : '' }}" style="border-color:{{ $col }}">
            <p class="text-[11px] text-gray-400 uppercase tracking-widest">{{ $statusLabels[$sk] }}</p>
            <p class="text-3xl font-extrabold" style="color:{{ $col }}">{{ $statusCounts[$sk] ?? 0 }}</p>
            <p class="text-xs text-gray-400">{{ $pct($statusCounts[$sk] ?? 0, $total) }}% · cliquer pour filtrer</p>
        </a>
        @endforeach
    </section>

    {{-- ── Listes PDF par DAG ──────────────────────────────────────── --}}
    <section class="card overflow-hidden">
        <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100">
            <div>
                <p class="section-title">Listes des ménages par DAG — Suivi {{ $suivis[$filters['suivi']] }}</p>
                <p class="text-xs text-gray-500 mt-1 pl-4">PDF à imprimer pour les équipes : coordonnées, moustiquaires distribuées (n° et code), colonnes à remplir sur le terrain.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route($cfg['route'] . '.pdf', ['suivi' => $filters['suivi'], 'format' => 'html']) }}" target="_blank" class="btn btn-primary"
                   title="Ouvre la version imprimable (une page par DAG) — Imprimer → Enregistrer en PDF">🖨 Imprimer tous les DAG</a>
                <a href="{{ route($cfg['route'] . '.pdf', ['suivi' => $filters['suivi'], 'only' => 'remaining', 'format' => 'html']) }}" target="_blank" class="btn btn-secondary">🖨 Restants — tous les DAG</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="text-left">DAG</th>
                        <th>Attendus</th>
                        <th>Visités</th>
                        <th>Réalisés</th>
                        @if ($hasNotDone)<th>Non réalisés</th>@endif
                        <th>En cours</th>
                        <th>Restants</th>
                        <th class="text-left" style="width:180px">Progression (visités)</th>
                        <th class="text-right">Listes PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byDag as $d)
                    <tr>
                        <td class="font-semibold"><a class="hover:underline" href="{{ $q(['dag' => $d['dag']]) }}">{{ $d['label'] }}</a></td>
                        <td class="text-center">{{ $d['expected'] }}</td>
                        <td class="text-center">{{ $d['visited'] }}</td>
                        <td class="text-center text-green-700 font-semibold">{{ $d['done'] }}</td>
                        @if ($hasNotDone)<td class="text-center text-red-600">{{ $d['notDone'] ?: '' }}</td>@endif
                        <td class="text-center text-amber-600">{{ $d['inProgress'] ?: '' }}</td>
                        <td class="text-center font-semibold">{{ $d['expected'] - $d['done'] - $d['notDone'] }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="prog flex-1"><div class="prog-fill" style="width:{{ $pct($d['visited'], $d['expected']) }}%;background:#16A34A"></div></div>
                                <span class="text-[11px] text-gray-500 w-10 text-right">{{ $pct($d['visited'], $d['expected']) }}%</span>
                            </div>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route($cfg['route'] . '.pdf', ['suivi' => $filters['suivi'], 'dag' => $d['dag']]) }}" class="btn btn-secondary">PDF liste</a>
                            <a href="{{ route($cfg['route'] . '.pdf', ['suivi' => $filters['suivi'], 'dag' => $d['dag'], 'only' => 'remaining']) }}" class="btn btn-secondary">PDF restants</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── Listes par tablette ─────────────────────────────────────── --}}
    <section class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <p class="section-title">Listes des ménages par village et tablette — Suivi {{ $suivis[$filters['suivi']] }}</p>
            <p class="text-xs text-gray-500 mt-1 pl-4">N° de tablette = avant-dernière composante de l'identifiant du ménage (ex. AG-01-PD-<b>01</b>-001) ; il se répète d'un village à l'autre.
                Choisir un village dans les filtres ci-dessous pour réduire la liste.
                @if ($cfg['letter'] === 'A') La liste inclut aussi les ménages Cohorte B sélectionnés de la même tablette. @endif</p>
        </div>
        <div class="overflow-x-auto" style="max-height:420px">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="text-left">Village</th>
                        <th class="text-left">Tablette</th>
                        <th class="text-left">Grappe(s)</th>
                        <th class="text-left">DAG</th>
                        <th>Ménages</th>
                        <th>Visités</th>
                        <th>Restants</th>
                        <th class="text-right">Listes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byTablet as $t)
                    <tr>
                        @php $vt = ['suivi' => $filters['suivi'], 'village' => $t['village'], 'tablet' => $t['tablet']]; @endphp
                        <td class="font-semibold">{{ $t['village'] }}</td>
                        <td class="font-mono font-bold"><a class="hover:underline" href="{{ $q(['village' => $t['village'], 'tablet' => $t['tablet']]) }}">{{ $t['tablet'] ?: '—' }}</a></td>
                        <td class="text-[11px]">{{ $t['clusters'] }}</td>
                        <td class="text-[11px]">{{ $t['dags'] }}</td>
                        <td class="text-center">{{ $t['expected'] }}</td>
                        <td class="text-center">{{ $t['visited'] }}</td>
                        <td class="text-center font-semibold">{{ $t['expected'] - $t['done'] }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route($cfg['route'] . '.pdf', $vt) }}" class="btn btn-secondary">PDF liste</a>
                            <a href="{{ route($cfg['route'] . '.pdf', $vt + ['only' => 'remaining']) }}" class="btn btn-secondary">PDF restants</a>
                            <a href="{{ route($cfg['route'] . '.pdf', $vt + ['format' => 'html']) }}" target="_blank" class="btn btn-secondary" title="Version imprimable dans le navigateur">🖨</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── Suivi individuel des ménages ────────────────────────────── --}}
    <section class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            <p class="section-title">Suivi individuel des ménages — Suivi {{ $suivis[$filters['suivi']] }}</p>
            <form method="GET" action="{{ route($cfg['route']) }}" class="flex flex-wrap gap-2 items-center">
                <input type="hidden" name="suivi" value="{{ $filters['suivi'] }}">
                <select name="dag" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                    <option value="">Tous les DAG</option>
                    @foreach ($dags as $d)
                        <option value="{{ $d['key'] }}" @selected($filters['dag'] === $d['key'])>{{ $d['label'] }} ({{ $d['count'] }})</option>
                    @endforeach
                </select>
                <select name="village" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                    <option value="">Tous les villages</option>
                    @foreach ($villages as $v)
                        <option value="{{ $v }}" @selected($filters['village'] === $v)>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="tablet" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                    <option value="">Toutes les tablettes</option>
                    @foreach ($tablets as $tb)
                        <option value="{{ $tb }}" @selected($filters['tablet'] === $tb)>Tablette {{ $tb }}</option>
                    @endforeach
                </select>
                <select name="status" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                    <option value="">Tous les statuts</option>
                    @foreach ($statusLabels as $sk => $sl)
                        <option value="{{ $sk }}" @selected($filters['status'] === $sk)>{{ $sl }}</option>
                    @endforeach
                </select>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ID ménage, chef, téléphone…"
                       class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-60">
                <button class="btn btn-primary">Filtrer</button>
                <a href="{{ route($cfg['route'], ['suivi' => $filters['suivi']]) }}" class="btn btn-secondary">Réinitialiser</a>
                <span class="text-xs text-gray-500 ml-auto">{{ $filteredCount }} ménage(s) · page {{ $page }} / {{ $pages }}</span>
            </form>
        </div>
        @php
            $pager = function () use ($page, $pages, $q) {
                if ($pages <= 1) return '';
                $win = collect(range(max(1, $page - 3), min($pages, $page + 3)))
                    ->merge([1, $pages])->unique()->sort()->values();
                $h = '<div class="flex flex-wrap items-center gap-1 px-5 py-3 text-xs">';
                if ($page > 1) $h .= '<a class="btn btn-secondary" href="' . e($q(['page' => $page - 1])) . '">‹ Préc.</a>';
                $prev = 0;
                foreach ($win as $p) {
                    if ($p - $prev > 1) $h .= '<span class="px-1 text-gray-400">…</span>';
                    $h .= $p === $page
                        ? '<span class="btn btn-primary">' . $p . '</span>'
                        : '<a class="btn btn-secondary" href="' . e($q(['page' => $p])) . '">' . $p . '</a>';
                    $prev = $p;
                }
                if ($page < $pages) $h .= '<a class="btn btn-secondary" href="' . e($q(['page' => $page + 1])) . '">Suiv. ›</a>';
                return $h . '</div>';
            };
        @endphp
        {!! $pager() !!}
        <div class="overflow-auto" style="max-height:70vh">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="text-left">#</th>
                        <th class="text-left">Ménage</th>
                        <th class="text-left">DAG</th>
                        <th class="text-left">Village / Grappe</th>
                        <th class="text-left">Bras</th>
                        <th class="text-left">Chef de ménage</th>
                        <th class="text-left">Point de contact</th>
                        <th class="text-left">Membres (baseline)</th>
                        <th>Moustiq.</th>
                        @foreach ($suivis as $sk => $sl)
                            <th class="{{ $filters['suivi'] === $sk ? 'text-red-700' : '' }}">{{ $sl }}</th>
                        @endforeach
                        <th class="text-left">Date / Agent</th>
                        <th class="text-left">Observations</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $i => $r)
                    @php $cur = $r['current']; @endphp
                    <tr>
                        <td class="text-gray-400">{{ $i + 1 }}</td>
                        <td class="font-mono whitespace-nowrap">{{ $r['id'] }}</td>
                        <td class="whitespace-nowrap">{{ $r['dagLabel'] }}</td>
                        <td class="whitespace-nowrap">{{ $r['village'] }} <span class="text-gray-400">· {{ $r['cluster'] }}</span></td>
                        <td class="whitespace-nowrap {{ $r['brasKey'] === '1' ? 'text-red-700' : 'text-gray-700' }}">{{ $r['bras'] }}</td>
                        <td>{{ $r['chef'] ?: '—' }}<span class="block text-[11px] text-gray-400">{{ $r['chefPhone'] }}</span></td>
                        <td>{{ $r['contact'] ?: '—' }}<span class="block text-[11px] text-gray-400">{{ $r['contactPhone'] }}</span></td>
                        <td class="text-[11px] leading-snug" style="min-width:210px">@include('partials.hh-members', ['list' => $members[$r['id']] ?? []])</td>
                        <td class="text-center">
                            {{ $cur['netsAssessed'] ? $cur['netsPresent'] . ' / ' : '' }}{{ count($r['netCodes']) }}
                        </td>
                        @foreach ($suivis as $sk => $sl)
                        @php $t = $r['timepoints'][$sk]; @endphp
                        <td class="text-center {{ $filters['suivi'] === $sk ? 'bg-red-50/40' : '' }}">
                            <span class="st st-{{ $t['status'] }}" title="{{ $statusLabels[$t['status']] }}">{{ $shortStatus[$t['status']] }}</span>
                        </td>
                        @endforeach
                        <td class="whitespace-nowrap">{{ $cur['date'] ?: '—' }}<span class="block text-[11px] text-gray-400">{{ $cur['agent'] }}</span></td>
                        <td class="text-[11px]">
                            @if ($cur['notDoneReason']) <span class="text-red-600">{{ $cur['notDoneReason'] }}</span> @endif
                            @if ($cur['moved']) <span class="text-amber-600">Déménagé</span> @endif
                            @if (!empty($cur['missing'])) <span class="text-red-600">Manque : {{ implode(', ', $cur['missing']) }}</span> @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="16" class="text-center text-gray-400 py-8">Aucun ménage ne correspond aux filtres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {!! $pager() !!}
    </section>

    <p class="text-[11px] text-gray-400 text-center">
        {!! $cfg['footnote'] !!}
    </p>
</main>
</body>
</html>
