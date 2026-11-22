{{--
    Suivi Cohorte A — un moment de suivi (6 / 12 / 24 / 36 mois)
    Variables : $d (buildCohortAFollowUp), $tk (préfixe DOM unique)
--}}
@php
    $s      = $d['summary'];
    $n      = $s['nets'];
    $pct    = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
    $statusMeta = [
        'realise'     => ['Suivi réalisé',     'bg-green-50 text-green-700 border-green-200'],
        'non_realise' => ['Non réalisé',       'bg-red-50 text-red-700 border-red-200'],
        'en_cours'    => ['En cours / à compléter', 'bg-amber-50 text-amber-700 border-amber-200'],
        'non_visite'  => ['Non visité',        'bg-gray-50 text-gray-500 border-gray-200'],
    ];
    $formCols = ['visite' => 'Vis.', 'qb' => 'QB', 'membres' => 'Mbr', 'avant' => 'AvS', 'f7' => 'F7', 'f8' => 'F8', 'f9' => 'F9'];
    $armColor = ['1' => '#C41230', '2' => '#374151'];
@endphp

@if ($s['hhVisited'] === 0)
<div class="rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    Aucune donnée saisie pour le suivi {{ $d['label'] }}.
    {{ $s['hhExpected'] }} ménages de la Cohorte A sont à suivre (voir l'avancement par village / grappe ci-dessous).
</div>
@endif

{{-- ── KPIs ──────────────────────────────────────────────────────────── --}}
<section class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
    @foreach ([
        ['Ménages attendus', $s['hhExpected'], 'Cohorte A consentis', 'var(--red)', null],
        ['Visités', $s['hhVisited'], $pct($s['hhVisited'], $s['hhExpected']) . '% des attendus', '#2563EB', null],
        ['Suivi réalisé', $s['hhDone'], $pct($s['hhDone'], $s['hhExpected']) . '% des attendus', '#16A34A', null],
        ['Non réalisé', $s['hhNotDone'], $s['hhAbsent'] . ' absent(s) · ' . $s['hhMoved'] . ' déménagé(s)', '#DC2626', null],
        ['Saisie incomplète', $s['hhIncomplete'] + $s['hhInProgress'], $s['hhInProgress'] . ' en cours · ' . $s['hhIncomplete'] . ' form. manquants', '#D97706', null],
        ['Alertes cohérence', count($d['alerts']), 'à vérifier', '#7C3AED', null],
    ] as [$lbl, $val, $sub, $color])
    <div class="kpi-card card border-t-2 p-4" style="border-color:{{ $color }}">
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">{{ $lbl }}</p>
        <p class="text-3xl font-extrabold" style="color:{{ $color }}">{{ $val }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $sub }}</p>
    </div>
    @endforeach
</section>

{{-- ── Par bras ──────────────────────────────────────────────────────── --}}
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach ($d['byArm'] as $bk => $a)
    @php $an = $a['nets']; $c = $armColor[$bk] ?? '#374151'; @endphp
    <div class="card overflow-hidden">
        <div class="h-1.5" style="background:{{ $c }}"></div>
        <div class="p-5">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full {{ $bk === '1' ? 'badge-red' : 'badge-gray' }}">
                    Bras {{ $bk }} · {{ $a['label'] }}
                </span>
                <span class="text-xs text-gray-400">{{ $a['hhDone'] }} / {{ $a['hhExpected'] }} ménages suivis</span>
            </div>

            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Ménages</p>
            <div class="space-y-2 mb-4">
                @foreach ([
                    ['Visités', $a['hhVisited'], $a['hhExpected'], '#2563EB'],
                    ['Suivi réalisé', $a['hhDone'], $a['hhExpected'], '#16A34A'],
                ] as [$lbl, $v, $t, $col])
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">{{ $lbl }}</span>
                        <span class="font-semibold text-gray-800">{{ $v }} / {{ $t }} <span class="text-gray-400 font-normal">({{ $pct($v, $t) }}%)</span></span>
                    </div>
                    <div class="prog"><div class="prog-fill" style="width:{{ $pct($v, $t) }}%;background:{{ $col }}"></div></div>
                </div>
                @endforeach
            </div>

            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Moustiquaires de l'étude (Form 7)</p>
            <div class="space-y-2 mb-4">
                @foreach ([
                    ['Évaluées / distribuées (ménages suivis)', $an['assessed'], $a['netsExpectedDone'], '#64748B'],
                    ['Présentes', $an['present'], $an['assessed'], $c],
                    ['Suspendues (parmi présentes)', $an['hanging'], $an['present'], '#0EA5E9'],
                    ['Utilisées la nuit dernière', $an['usedLastNight'], $an['present'], '#16A34A'],
                    ['Utilisées chaque nuit (7/7)', $an['everyNight'], $an['present'], '#2563EB'],
                    ['Déjà lavées', $an['washed'], $an['present'], '#8B5CF6'],
                    ['Avec trous', $an['withHoles'], $an['present'], '#D97706'],
                ] as [$lbl, $v, $t, $col])
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">{{ $lbl }}</span>
                        <span class="font-semibold text-gray-800">{{ $v }} / {{ $t }} <span class="text-gray-400 font-normal">({{ $pct($v, $t) }}%)</span></span>
                    </div>
                    <div class="prog"><div class="prog-fill" style="width:{{ $pct($v, $t) }}%;background:{{ $col }}"></div></div>
                </div>
                @endforeach
            </div>

            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">
                Intégrité (pHI) <span class="font-normal normal-case text-gray-400">— {{ $an['phiAssessed'] }} moustiquaire(s) évaluée(s){{ $an['phiMedian'] !== null ? ' · médiane ' . $an['phiMedian'] : '' }}</span>
            </p>
            @if ($an['phiAssessed'] > 0)
            <div class="flex h-5 rounded overflow-hidden text-[10px] font-bold text-white">
                @foreach ([[$an['phiGood'], '#16A34A'], [$an['phiAcceptable'], '#F59E0B'], [$an['phiTorn'], '#DC2626']] as [$v, $col])
                    @if ($v > 0)
                    <div class="flex items-center justify-center" style="width:{{ $pct($v, $an['phiAssessed']) }}%;background:{{ $col }}">{{ $pct($v, $an['phiAssessed']) }}%</div>
                    @endif
                @endforeach
            </div>
            <div class="flex gap-4 mt-1.5 text-[11px] text-gray-500">
                <span><span class="inline-block w-2 h-2 rounded-sm bg-green-600"></span> Bon {{ $an['phiGood'] }}</span>
                <span><span class="inline-block w-2 h-2 rounded-sm bg-amber-500"></span> Acceptable {{ $an['phiAcceptable'] }}</span>
                <span><span class="inline-block w-2 h-2 rounded-sm bg-red-600"></span> Déchiré {{ $an['phiTorn'] }}</span>
            </div>
            @else
            <p class="text-xs text-gray-300 italic">Aucune donnée</p>
            @endif
        </div>
    </div>
    @endforeach
</section>

{{-- ── Répartitions Form 7 ──────────────────────────────────────────── --}}
@php
    $barList = function (array $items, string $color, int $total) use ($pct) {
        if (empty($items)) return '<p class="text-xs text-gray-300 italic">Aucune donnée</p>';
        $h = '<div class="space-y-2">';
        foreach ($items as $lbl => $v) {
            $p = $pct($v, $total);
            $h .= '<div><div class="flex justify-between text-xs mb-1"><span class="text-gray-600">' . e($lbl) . '</span>'
                . '<span class="font-semibold text-gray-800">' . $v . ' <span class="text-gray-400 font-normal">(' . $p . '%)</span></span></div>'
                . '<div class="prog"><div class="prog-fill" style="width:' . $p . '%;background:' . $color . '"></div></div></div>';
        }
        return $h . '</div>';
    };
@endphp
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card p-5">
        <p class="section-title mb-1">Attrition — sort des moustiquaires absentes</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['absent'] }} absente(s) sur {{ $n['assessed'] }} évaluée(s)</p>
        {!! $barList($n['absenceReasons'], '#DC2626', $n['absent']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Où la moustiquaire a été trouvée</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['present'] }} moustiquaire(s) présente(s)</p>
        {!! $barList($n['locations'], '#0EA5E9', $n['present']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Utilisation sur les 7 dernières nuits</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['present'] }} moustiquaire(s) présente(s)</p>
        {!! $barList($n['use7'], '#2563EB', $n['present']) !!}
    </div>
</section>

{{-- ── Avant suivi / Autres moustiquaires / Membres ─────────────────── --}}
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card p-5">
        <p class="section-title mb-3">Formulaire Avant Suivi</p>
        <dl class="grid grid-cols-2 gap-y-2 text-xs mb-4">
            <dt class="text-gray-500">Ménages absents</dt><dd class="text-right font-semibold">{{ $s['hhAbsent'] }}</dd>
            <dt class="text-gray-500">Ménages déménagés</dt><dd class="text-right font-semibold">{{ $s['hhMoved'] }}</dd>
            <dt class="text-gray-500">Suivi non réalisé</dt><dd class="text-right font-semibold">{{ $s['hhNotDone'] }}</dd>
            <dt class="text-gray-500">Ménages ayant acquis d'autres moustiquaires</dt><dd class="text-right font-semibold">{{ $s['hhNewNets'] }} <span class="text-gray-400 font-normal">({{ $s['newNetsTotal'] }} moustiq.)</span></dd>
        </dl>
        @if (!empty($s['notDoneReasons']))
            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Raisons du non-suivi</p>
            {!! $barList($s['notDoneReasons'], '#DC2626', $s['hhNotDone']) !!}
        @endif
        @if (!empty($s['newNetSources']))
            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mt-4 mb-2">Sources des nouvelles moustiquaires</p>
            {!! $barList($s['newNetSources'], '#8B5CF6', $s['hhNewNets']) !!}
        @endif
    </div>
    <div class="card p-5">
        <p class="section-title mb-3">Autres moustiquaires (Form 8 / Form 9)</p>
        <dl class="grid grid-cols-2 gap-y-2 text-xs mb-4">
            <dt class="text-gray-500">Ménages avec d'autres moustiquaires</dt><dd class="text-right font-semibold">{{ $s['hhOtherNets'] }}</dd>
            <dt class="text-gray-500">Nombre déclaré (Form 8)</dt><dd class="text-right font-semibold">{{ $s['otherNetsDeclared'] }}</dd>
            <dt class="text-gray-500">Décrites (Form 9)</dt><dd class="text-right font-semibold">{{ $s['f9Count'] }}</dd>
            <dt class="text-gray-500">Utilisées la nuit dernière</dt><dd class="text-right font-semibold">{{ $s['otherNetsUsed'] }}</dd>
        </dl>
        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Marques</p>
        {!! $barList($s['otherNetBrands'], '#64748B', $s['f9Count']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-3">Membres du ménage</p>
        <dl class="grid grid-cols-2 gap-y-2 text-xs mb-4">
            <dt class="text-gray-500">Membres enregistrés</dt><dd class="text-right font-semibold">{{ $s['members'] }}</dd>
            <dt class="text-gray-500">Nouveaux membres (absents à la baseline)</dt><dd class="text-right font-semibold">{{ $s['membersNew'] }}</dd>
        </dl>
        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-2">Statut actuel</p>
        {!! $barList($s['memberStatus'], '#0F766E', $s['members']) !!}
    </div>
</section>

{{-- ── Alertes de cohérence ─────────────────────────────────────────── --}}
@if (count($d['alerts']))
<section class="card overflow-hidden">
    <details>
        <summary class="px-5 py-3 cursor-pointer section-title flex items-center gap-2">
            Alertes de cohérence
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700">{{ count($d['alerts']) }}</span>
        </summary>
        <ul class="divide-y divide-gray-50 text-sm">
            @foreach ($d['alerts'] as $al)
            <li class="px-5 py-2 flex items-start gap-3">
                <span class="mt-0.5 text-[10px] font-bold uppercase px-1.5 py-0.5 rounded {{ $al['type'] === 'critical' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $al['type'] === 'critical' ? 'Critique' : 'Attention' }}</span>
                <span class="font-mono text-xs text-gray-700 whitespace-nowrap">{{ $al['hh'] }}</span>
                <span class="text-xs text-gray-600">{{ $al['msg'] }}</span>
            </li>
            @endforeach
        </ul>
    </details>
</section>
@endif

{{-- ── Avancement par village / grappe ──────────────────────────────── --}}
<section class="card overflow-hidden">
    <details>
        <summary class="px-5 py-3 cursor-pointer section-title">
            Avancement par village / grappe
            <span class="text-xs font-normal text-gray-400">({{ $s['hhExpected'] - $s['hhVisited'] }} ménages restant à visiter)</span>
        </summary>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr class="text-gray-400 uppercase border-b">
                        <th class="py-2 px-3 text-left">Village</th>
                        <th class="py-2 px-3 text-left">Grappe</th>
                        <th class="py-2 px-3 text-left">Bras</th>
                        <th class="py-2 px-3 text-center">Attendus</th>
                        <th class="py-2 px-3 text-center">Visités</th>
                        <th class="py-2 px-3 text-center">Réalisés</th>
                        <th class="py-2 px-3 text-center">Non réalisés</th>
                        <th class="py-2 px-3 text-center">Restants</th>
                        <th class="py-2 px-3 text-left w-48">Progression</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($d['byCluster'] as $c)
                    <tr class="hov-row">
                        <td class="py-1.5 px-3">{{ $c['village'] }}</td>
                        <td class="py-1.5 px-3">{{ $c['cluster'] }}</td>
                        <td class="py-1.5 px-3" style="color:{{ $armColor[$c['brasKey']] ?? '#374151' }}">{{ $c['bras'] }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $c['expected'] }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $c['visited'] }}</td>
                        <td class="py-1.5 px-3 text-center text-green-700 font-semibold">{{ $c['done'] }}</td>
                        <td class="py-1.5 px-3 text-center text-red-600">{{ $c['notDone'] ?: '' }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $c['expected'] - $c['visited'] }}</td>
                        <td class="py-1.5 px-3">
                            <div class="prog"><div class="prog-fill" style="width:{{ $pct($c['visited'], $c['expected']) }}%;background:#16A34A"></div></div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>

{{-- ── Tableau des ménages visités ──────────────────────────────────── --}}
@if (count($d['households']))
<section class="card overflow-hidden">
    <details open>
        <summary class="px-5 py-3 cursor-pointer section-title">
            Ménages visités — {{ $d['label'] }}
            <span class="text-xs font-normal text-gray-400">(<span id="fuaCount-{{ $tk }}">{{ count($d['households']) }}</span> affichés)</span>
        </summary>
        <div class="px-5 pb-3 flex flex-wrap gap-2">
            <input id="fuaSearch-{{ $tk }}" type="text" placeholder="Rechercher (ID, village, agent…)"
                   oninput="filterFuaTable('{{ $tk }}')"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-64">
            <select id="fuaStatus-{{ $tk }}" onchange="filterFuaTable('{{ $tk }}')" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                <option value="">Tous statuts</option>
                @foreach ($statusMeta as $sk => [$sl]) <option value="{{ $sk }}">{{ $sl }}</option> @endforeach
            </select>
            <select id="fuaArm-{{ $tk }}" onchange="filterFuaTable('{{ $tk }}')" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                <option value="">Tous bras</option>
                <option value="1">PERMANET DUAL</option>
                <option value="2">INTERCEPTOR G2</option>
            </select>
            <span class="text-[11px] text-gray-400 self-center">Vis. = Visite · QB = Questionnaire Base · Mbr = Membres · AvS = Avant Suivi</span>
        </div>
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table id="fuaTable-{{ $tk }}" class="w-full text-xs">
                <thead class="sticky top-0 bg-gray-50">
                    <tr class="text-gray-400 uppercase border-b">
                        <th class="py-2 px-3 text-left">Ménage</th>
                        <th class="py-2 px-3 text-left">Village / Grappe</th>
                        <th class="py-2 px-3 text-left">Bras</th>
                        <th class="py-2 px-3 text-left">Statut</th>
                        <th class="py-2 px-3 text-left">Date</th>
                        <th class="py-2 px-3 text-left">Agent</th>
                        @foreach ($formCols as $fl) <th class="py-2 px-1.5 text-center">{{ $fl }}</th> @endforeach
                        <th class="py-2 px-3 text-center">Moustiq. présentes / distrib.</th>
                        <th class="py-2 px-3 text-left">Manquants</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($d['households'] as $h)
                    @php [$sl, $scls] = $statusMeta[$h['status']]; @endphp
                    <tr class="hov-row" data-status="{{ $h['status'] }}" data-arm="{{ $h['brasKey'] }}">
                        <td class="py-1.5 px-3 font-mono whitespace-nowrap">{{ $h['id'] }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $h['village'] }} <span class="text-gray-400">· {{ $h['cluster'] }}</span></td>
                        <td class="py-1.5 px-3 whitespace-nowrap" style="color:{{ $armColor[$h['brasKey']] ?? '#374151' }}">{{ $h['bras'] }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">
                            <span class="px-1.5 py-0.5 rounded border text-[10px] font-semibold {{ $scls }}">{{ $sl }}</span>
                            @if ($h['status'] === 'non_realise' && $h['notDoneReason'] !== '')
                                <span class="text-[10px] text-gray-400 block">{{ [ '1' => 'Absent', '2' => 'Refus', '3' => 'Malade', '99' => 'Autre'][$h['notDoneReason']] ?? '' }}</span>
                            @endif
                        </td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $h['date'] ?: '—' }}</td>
                        <td class="py-1.5 px-3">{{ $h['agent'] ?: '—' }}</td>
                        @foreach ($formCols as $fk => $fl)
                        <td class="py-1.5 px-1.5 text-center">
                            @if ($h['forms'][$fk])
                                <span class="text-green-600 font-bold">✓</span>
                                @if ($fk === 'f7')<span class="text-[10px] text-gray-400">{{ $h['f7Count'] }}</span>@endif
                                @if ($fk === 'f9')<span class="text-[10px] text-gray-400">{{ $h['f9Count'] }}</span>@endif
                                @if ($fk === 'membres')<span class="text-[10px] text-gray-400">{{ $h['memCount'] }}</span>@endif
                            @elseif ($fk === 'f9' && !$h['otherNetsYes'])
                                <span class="text-gray-300">n/a</span>
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>
                        @endforeach
                        <td class="py-1.5 px-3 text-center">{{ $h['f7Count'] ? $h['netsPresent'] : '—' }} / {{ $h['netsExpected'] }}</td>
                        <td class="py-1.5 px-3 text-red-600">{{ implode(', ', $h['missing']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
@endif

{{-- ── Détail par moustiquaire (Form 7) ─────────────────────────────── --}}
@if (count($d['netRows']))
<section class="card overflow-hidden">
    <details>
        <summary class="px-5 py-3 cursor-pointer section-title">
            Détail par moustiquaire — Form 7 <span class="text-xs font-normal text-gray-400">({{ count($d['netRows']) }})</span>
        </summary>
        <div class="px-5 pb-3">
            <input type="text" placeholder="Rechercher…" oninput="filterFuaNets(this, '{{ $tk }}')"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-64">
        </div>
        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table id="fuaNets-{{ $tk }}" class="w-full text-xs">
                <thead class="sticky top-0 bg-gray-50">
                    <tr class="text-gray-400 uppercase border-b">
                        <th class="py-2 px-3 text-left">Ménage</th>
                        <th class="py-2 px-3 text-left">Bras</th>
                        <th class="py-2 px-3 text-center">N°</th>
                        <th class="py-2 px-3 text-left">Code</th>
                        <th class="py-2 px-3 text-left">Date</th>
                        <th class="py-2 px-3 text-center">Présente</th>
                        <th class="py-2 px-3 text-left">Sort / Emplacement</th>
                        <th class="py-2 px-3 text-center">Nuit dern.</th>
                        <th class="py-2 px-3 text-left">7 nuits</th>
                        <th class="py-2 px-3 text-center">Lavée</th>
                        <th class="py-2 px-3 text-center">pHI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($d['netRows'] as $r)
                    @php
                        $yn = fn($v) => $v === '1' ? '<span class="text-green-600 font-semibold">Oui</span>' : ($v === '0' ? '<span class="text-red-600">Non</span>' : '<span class="text-gray-300">—</span>');
                        $phiCls = $r['phi'] === null ? 'text-gray-300' : ($r['phi'] <= 64 ? 'text-green-700' : ($r['phi'] <= 642 ? 'text-amber-600' : 'text-red-700'));
                    @endphp
                    <tr class="hov-row">
                        <td class="py-1.5 px-3 font-mono whitespace-nowrap">{{ $r['hh'] }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $r['bras'] }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $r['num'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 font-mono">{{ $r['code'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $r['date'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['present']) !!}</td>
                        <td class="py-1.5 px-3">{{ $r['present'] === '0' ? $r['absence'] : $r['location'] }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['used']) !!}</td>
                        <td class="py-1.5 px-3">{{ $r['use7'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['washed']) !!}</td>
                        <td class="py-1.5 px-3 text-center font-semibold {{ $phiCls }}">{{ $r['phi'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
@endif
