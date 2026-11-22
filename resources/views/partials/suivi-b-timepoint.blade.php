{{--
    Suivi Cohorte B — un moment de suivi (6 / 12 / 18 / 24 / 36 mois)
    Variables : $d (CohortBFollowUpService::build), $tk (préfixe DOM unique)
--}}
@php
    $s   = $d['summary'];
    $n   = $s['nets'];
    $pct = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
    $statusMeta = [
        'realise'  => ['Complet', 'bg-green-50 text-green-700 border-green-200'],
        'en_cours' => ['Partiel', 'bg-amber-50 text-amber-700 border-amber-200'],
    ];
    $armColor = ['1' => '#C41230', '2' => '#374151'];
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

@if ($s['hhVisited'] === 0)
<div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700">
    Aucune donnée saisie pour le suivi {{ $d['label'] }}.
    {{ $s['hhExpected'] }} ménages de la Cohorte B ({{ $s['netsExpected'] }} moustiquaires de l'étude) sont concernés.
</div>
@endif

{{-- ── KPIs ──────────────────────────────────────────────────────────── --}}
<section class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
    @foreach ([
        ['Ménages attendus', $s['hhExpected'], $s['netsExpected'] . ' moustiquaires distribuées', 'var(--red)'],
        ['Visités', $s['hhVisited'], $pct($s['hhVisited'], $s['hhExpected']) . '% des attendus', '#2563EB'],
        ['Complets', $s['hhDone'], $s['hhPartial'] . ' partiel(s)', '#16A34A'],
        ['Moustiq. prélevées', $n['assessed'], $pct($n['assessed'], $s['netsExpected']) . '% des distribuées', '#0F766E'],
        ['Remplacées', $n['replaced'], $pct($n['replaced'], $n['verified']) . '% des vérifiées', '#7C3AED'],
        ['Alertes cohérence', count($d['alerts']), 'à vérifier', '#DC2626'],
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
                <span class="text-xs text-gray-400">{{ $a['hhVisited'] }} / {{ $a['hhExpected'] }} ménages visités</span>
            </div>
            <div class="space-y-2">
                @foreach ([
                    ['Ménages complets', $a['hhDone'], $a['hhExpected'], '#16A34A'],
                    ['Moustiquaires prélevées / distribuées', $an['assessed'], $a['netsExpected'], '#0F766E'],
                    ['Vérifiées (étude, bonne marque)', $an['verified'], $an['assessed'], $c],
                    ['Remplacées (code saisi)', $an['replaced'], $an['verified'], '#7C3AED'],
                    ['Suspendues au moment du prélèvement', $an['hanging'], $an['verified'], '#0EA5E9'],
                    ['Utilisées la nuit dernière', $an['usedLastNight'], $an['verified'], '#16A34A'],
                    ['Utilisées chaque nuit (7/7)', $an['everyNight'], $an['verified'], '#2563EB'],
                    ['Déjà lavées', $an['washed'], $an['verified'], '#8B5CF6'],
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
            @if ($an['washMean'] !== null)
                <p class="text-[11px] text-gray-500 mt-3">Lavages sur 6 mois (moustiquaires lavées) : moyenne <b>{{ $an['washMean'] }}</b></p>
            @endif
        </div>
    </div>
    @endforeach
</section>

{{-- ── Usage des moustiquaires prélevées ─────────────────────────────── --}}
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card p-5">
        <p class="section-title mb-1">Où la moustiquaire a été trouvée</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['verified'] }} moustiquaire(s) vérifiée(s)</p>
        {!! $barList($n['locations'], '#0EA5E9', $n['verified']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Utilisation sur 7 nuits</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['verified'] }} moustiquaire(s) vérifiée(s)</p>
        {!! $barList($n['use7'], '#2563EB', $n['verified']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Usage selon la saison</p>
        <p class="text-xs text-gray-400 mb-3">{{ $n['verified'] }} moustiquaire(s) vérifiée(s)</p>
        {!! $barList($n['seasonal'], '#16A34A', $n['verified']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Utilisateur principal</p>
        {!! $barList($n['mainUser'], '#F59E0B', $n['verified']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Type de couchage</p>
        {!! $barList($n['sleepPlace'], '#64748B', $n['verified']) !!}
    </div>
    <div class="card p-5">
        <p class="section-title mb-1">Produit du dernier lavage</p>
        {!! $barList($n['washProduct'], '#8B5CF6', $n['washed']) !!}
    </div>
</section>

{{-- ── Alertes ────────────────────────────────────────────────────────── --}}
@if (count($d['alerts']))
<section class="card overflow-hidden">
    <details>
        <summary class="px-5 py-3 cursor-pointer section-title flex items-center gap-2">
            Alertes de cohérence <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700">{{ count($d['alerts']) }}</span>
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
                        <th class="py-2 px-3 text-left">Village</th><th class="py-2 px-3 text-left">Grappe</th><th class="py-2 px-3 text-left">Bras</th>
                        <th class="py-2 px-3 text-center">Attendus</th><th class="py-2 px-3 text-center">Visités</th>
                        <th class="py-2 px-3 text-center">Complets</th><th class="py-2 px-3 text-center">Moustiq. prélevées</th>
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
                        <td class="py-1.5 px-3 text-center">{{ $c['nets'] }}</td>
                        <td class="py-1.5 px-3"><div class="prog"><div class="prog-fill" style="width:{{ $pct($c['visited'], $c['expected']) }}%;background:#16A34A"></div></div></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>

{{-- ── Ménages visités ───────────────────────────────────────────────── --}}
@if (count($d['households']))
<section class="card overflow-hidden">
    <details open>
        <summary class="px-5 py-3 cursor-pointer section-title">
            Ménages visités — {{ $d['label'] }}
            <span class="text-xs font-normal text-gray-400">(<span id="fubCount-{{ $tk }}">{{ count($d['households']) }}</span> affichés)</span>
        </summary>
        <div class="px-5 pb-3 flex flex-wrap gap-2">
            <input id="fubSearch-{{ $tk }}" type="text" placeholder="Rechercher (ID, village, agent…)" oninput="filterFubTable('{{ $tk }}')"
                   class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-64">
            <select id="fubStatus-{{ $tk }}" onchange="filterFubTable('{{ $tk }}')" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                <option value="">Tous statuts</option>
                @foreach ($statusMeta as $sk => [$sl]) <option value="{{ $sk }}">{{ $sl }}</option> @endforeach
            </select>
            <select id="fubArm-{{ $tk }}" onchange="filterFubTable('{{ $tk }}')" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                <option value="">Tous bras</option><option value="1">PERMANET DUAL</option><option value="2">INTERCEPTOR G2</option>
            </select>
        </div>
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table id="fubTable-{{ $tk }}" class="w-full text-xs">
                <thead class="sticky top-0 bg-gray-50">
                    <tr class="text-gray-400 uppercase border-b">
                        <th class="py-2 px-3 text-left">Ménage</th><th class="py-2 px-3 text-left">Village / Grappe</th>
                        <th class="py-2 px-3 text-left">Bras</th><th class="py-2 px-3 text-left">Statut</th>
                        <th class="py-2 px-3 text-left">Date</th><th class="py-2 px-3 text-left">Agent</th>
                        <th class="py-2 px-3 text-center">Prélevées / distrib.</th><th class="py-2 px-3 text-center">Vérifiées</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($d['households'] as $h)
                    @php [$sl, $scls] = $statusMeta[$h['status']]; @endphp
                    <tr class="hov-row" data-status="{{ $h['status'] }}" data-arm="{{ $h['brasKey'] }}">
                        <td class="py-1.5 px-3 font-mono whitespace-nowrap">{{ $h['id'] }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $h['village'] }} <span class="text-gray-400">· {{ $h['cluster'] }}</span></td>
                        <td class="py-1.5 px-3 whitespace-nowrap" style="color:{{ $armColor[$h['brasKey']] ?? '#374151' }}">{{ $h['bras'] }}</td>
                        <td class="py-1.5 px-3"><span class="px-1.5 py-0.5 rounded border text-[10px] font-semibold {{ $scls }}">{{ $sl }}</span></td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $h['date'] ?: '—' }}</td>
                        <td class="py-1.5 px-3">{{ $h['agent'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $h['f10Count'] }} / {{ $h['netsExpected'] }}</td>
                        <td class="py-1.5 px-3 text-center">{{ $h['netsVerified'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
@endif

{{-- ── Détail par moustiquaire ───────────────────────────────────────── --}}
@if (count($d['netRows']))
<section class="card overflow-hidden">
    <details>
        <summary class="px-5 py-3 cursor-pointer section-title">
            Détail par moustiquaire — Form 10 <span class="text-xs font-normal text-gray-400">({{ count($d['netRows']) }})</span>
        </summary>
        <div class="px-5 pb-3">
            <input type="text" placeholder="Rechercher…" oninput="filterFubNets(this, '{{ $tk }}')" class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 w-64">
        </div>
        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table id="fubNets-{{ $tk }}" class="w-full text-xs">
                <thead class="sticky top-0 bg-gray-50">
                    <tr class="text-gray-400 uppercase border-b">
                        <th class="py-2 px-3 text-left">Ménage</th><th class="py-2 px-3 text-left">Bras</th>
                        <th class="py-2 px-3 text-left">Code prélevé</th><th class="py-2 px-3 text-left">Date</th>
                        <th class="py-2 px-3 text-center">Vérifiée</th><th class="py-2 px-3 text-left">Emplacement</th>
                        <th class="py-2 px-3 text-center">Nuit dern.</th><th class="py-2 px-3 text-left">7 nuits</th>
                        <th class="py-2 px-3 text-center">Lavée</th><th class="py-2 px-3 text-left">Code remplacement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($d['netRows'] as $r)
                    @php $yn = fn($v) => $v === '1' ? '<span class="text-green-600 font-semibold">Oui</span>' : ($v === '0' ? '<span class="text-red-600">Non</span>' : '<span class="text-gray-300">—</span>'); @endphp
                    <tr class="hov-row">
                        <td class="py-1.5 px-3 font-mono whitespace-nowrap">{{ $r['hh'] }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $r['bras'] }}</td>
                        <td class="py-1.5 px-3 font-mono {{ $r['known'] ? '' : 'text-red-600' }}" title="{{ $r['known'] ? '' : 'Code absent des moustiquaires distribuées au ménage' }}">{{ $r['code'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 whitespace-nowrap">{{ $r['date'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['verified']) !!}</td>
                        <td class="py-1.5 px-3">{{ $r['location'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['used']) !!}</td>
                        <td class="py-1.5 px-3">{{ $r['use7'] ?: '—' }}</td>
                        <td class="py-1.5 px-3 text-center">{!! $yn($r['washed']) !!}</td>
                        <td class="py-1.5 px-3 font-mono">{{ $r['replacement'] ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>
@endif
