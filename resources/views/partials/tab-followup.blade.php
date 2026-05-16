{{--
    Follow-up tab partial — per-timepoint, per-bras
    Variables:
      $data    – array from buildFollowUpData
      $label   – string  (ex: "Suivi 6 mois")
      $tabKey  – string  (unique DOM id prefix)
      $cohort  – 'A' | 'B'  (filters Form 7 / Form 10)
--}}
@php
    $cohort      = $cohort ?? null;
    $total       = $data['total']      ?? 0;
    $form7Count  = $data['form7Count'] ?? 0;
    $form10Count = $data['form10Count']?? 0;
    $byBras      = collect($data['byBras'] ?? []);
    $allRows     = collect($data['rows']   ?? []);

    $showF7  = $cohort !== 'B';
    $showF10 = $cohort !== 'A';

    $rows = match($cohort) {
        'A'     => $allRows->filter(fn($r) => str_contains($r['form'], 'Form 7'))->values(),
        'B'     => $allRows->filter(fn($r) => str_contains($r['form'], 'Form 10'))->values(),
        default => $allRows->values(),
    };

    $relevantCount  = ($showF7  ? $form7Count  : 0) + ($showF10 ? $form10Count : 0);
    $totalPresent   = ($showF7  ? ($data['netsPresent7']  ?? 0) : 0)
                    + ($showF10 ? ($data['netsPresent10'] ?? 0) : 0);

    $pct = fn($n, $d) => $d > 0 ? round($n / $d * 100, 1) : 0;
@endphp

{{-- ── KPIs ────────────────────────────────────────────────────────── --}}
<section class="grid grid-cols-3 gap-4">
    <div class="kpi-card card border-t-2 p-4" style="border-color:var(--red)">
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Ménages suivis</p>
        <p class="text-3xl font-extrabold" style="color:var(--red)">{{ $total }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $label }}</p>
    </div>
    <div class="kpi-card card border-t-2 {{ $showF7 && !$showF10 ? 'border-amber-500' : 'border-slate-400' }} p-4">
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">
            @if ($cohort === 'A') Form 7 (Coh. A)
            @elseif ($cohort === 'B') Form 10 (Coh. B)
            @else Filets vérifiés @endif
        </p>
        <p class="text-3xl font-extrabold {{ $showF7 && !$showF10 ? 'text-amber-600' : 'text-slate-600' }}">
            {{ $relevantCount }}
        </p>
        <p class="text-xs text-gray-400 mt-1">filets vérifiés</p>
    </div>
    <div class="kpi-card card border-t-2 border-green-500 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Filets présents</p>
        <p class="text-3xl font-extrabold text-green-600">{{ $totalPresent }}</p>
        <p class="text-xs text-gray-400 mt-1">trouvés lors de la visite</p>
    </div>
</section>

{{-- ── Par bras ────────────────────────────────────────────────────── --}}
@if ($byBras->isNotEmpty())
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach ($byBras as $bras)
    @php
        $isBras1     = $bras['brasKey'] === '1';
        $accentColor = $isBras1 ? 'var(--red)' : '#374151';
        $badgeCls    = $isBras1 ? 'badge-red'  : 'badge-gray';
        $p7          = $bras['form7Count'];
        $p10         = $bras['form10Count'];
        $cardEmpty   = (!$showF7 || $p7 === 0) && (!$showF10 || $p10 === 0);
    @endphp
    <div class="card overflow-hidden">
        <div class="h-1.5" style="background:{{ $accentColor }}"></div>
        <div class="p-5">

            {{-- En-tête bras --}}
            <div class="flex items-center justify-between mb-5">
                <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full {{ $badgeCls }}">
                    Bras {{ $bras['brasKey'] }} &middot; {{ $bras['label'] }}
                </span>
                @php $shown = ($showF7 ? $p7 : 0) + ($showF10 ? $p10 : 0); @endphp
                <span class="text-xs text-gray-400">{{ $shown }} enreg.</span>
            </div>

            {{-- Form 7 — Cohorte A --}}
            @if ($showF7 && $p7 > 0)
            <p class="text-xs font-bold uppercase tracking-wide text-amber-600 mb-3 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block flex-shrink-0"></span>
                Cohorte A — Form 7 &nbsp;<span class="font-normal text-gray-400">({{ $p7 }} filets)</span>
            </p>
            <div class="space-y-2.5 mb-5">
                @foreach ([
                    ['Filet présent',          $bras['netsPresent7'],  $p7, '#C41230'],
                    ['Utilisé la nuit dern.',  $bras['netsUsed7'],     $p7, '#16A34A'],
                    ['Utilisé / 7 jours',      $bras['netsUsed7d7'],   $p7, '#2563EB'],
                    ['Trous détectés',         $bras['netsHoles7'],    $p7, '#D97706'],
                ] as [$lbl, $n, $d, $color])
                @php $r = $pct($n, $d); @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">{{ $lbl }}</span>
                        <span class="font-semibold text-gray-800">{{ $n }}&thinsp;/&thinsp;{{ $d }}
                            <span class="text-gray-400 font-normal ml-1">({{ $r }}%)</span>
                        </span>
                    </div>
                    <div class="prog"><div class="prog-fill" style="width:{{ $r }}%;background:{{ $color }}"></div></div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Form 10 — Cohorte B --}}
            @if ($showF10 && $p10 > 0)
            @if ($showF7 && $p7 > 0)<hr class="border-gray-100 mb-4">@endif
            <p class="text-xs font-bold uppercase tracking-wide text-slate-600 mb-3 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-400 inline-block flex-shrink-0"></span>
                Cohorte B — Form 10 &nbsp;<span class="font-normal text-gray-400">({{ $p10 }} filets)</span>
            </p>
            <div class="space-y-2.5">
                @foreach ([
                    ['Filet vérifié / présent', $bras['netsPresent10'], $p10, '#C41230'],
                    ['Utilisé la nuit dern.',   $bras['netsUsed10'],   $p10, '#16A34A'],
                    ['Utilisé / 7 jours',       $bras['netsUsed7d10'], $p10, '#2563EB'],
                ] as [$lbl, $n, $d, $color])
                @php $r = $pct($n, $d); @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-gray-600">{{ $lbl }}</span>
                        <span class="font-semibold text-gray-800">{{ $n }}&thinsp;/&thinsp;{{ $d }}
                            <span class="text-gray-400 font-normal ml-1">({{ $r }}%)</span>
                        </span>
                    </div>
                    <div class="prog"><div class="prog-fill" style="width:{{ $r }}%;background:{{ $color }}"></div></div>
                </div>
                @endforeach
                <p class="text-xs text-slate-400 italic mt-1">Champ "trous" non collecté en Form 10.</p>
            </div>
            @endif

            {{-- État vide --}}
            @if ($cardEmpty)
            <p class="text-xs text-gray-400 text-center py-6 italic">Aucune donnée pour ce bras à cette visite</p>
            @endif

        </div>
    </div>
    @endforeach
</section>
@endif

{{-- ── Tableau détail dépliable ────────────────────────────────────── --}}
<section class="card overflow-hidden">
    <details>
        <summary class="flex items-center justify-between px-6 py-4 bg-white hover:bg-gray-50 transition select-none">
            <div class="flex items-center gap-3">
                <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <span class="section-title mb-0">Détail par ménage — {{ $label }}</span>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                    {{ $rows->count() }} enreg.
                </span>
            </div>
            <span class="text-xs text-gray-400">Cliquer pour déplier</span>
        </summary>

        <div class="px-6 pb-6">
            <div class="relative my-4">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" placeholder="Rechercher ménage, bras, code filet..."
                       oninput="filterFwTable(this, '{{ $tabKey }}')"
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color:var(--red)">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="fwTable-{{ $tabKey }}">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                            <th class="py-2.5 px-3 text-left">ID Ménage</th>
                            <th class="py-2.5 px-3 text-left">Bras</th>
                            <th class="py-2.5 px-3 text-left">Cohorte</th>
                            <th class="py-2.5 px-3 text-left">Village</th>
                            <th class="py-2.5 px-3 text-left">Grappe</th>
                            <th class="py-2.5 px-3 text-left">Formulaire</th>
                            <th class="py-2.5 px-3 text-left">ID Filet</th>
                            <th class="py-2.5 px-3 text-left">Date</th>
                            <th class="py-2.5 px-3 text-center">Présent</th>
                            <th class="py-2.5 px-3 text-center">Nuit</th>
                            <th class="py-2.5 px-3 text-center">7 jours</th>
                            @if ($showF7)<th class="py-2.5 px-3 text-center">Trous</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($rows as $row)
                            <tr class="hov-row transition-colors">
                                <td class="py-2 px-3 font-mono text-xs text-gray-500">{{ $row['id'] }}</td>
                                <td class="py-2 px-3">
                                    @if ($row['bras'] === 'PERMANET DUAL')
                                        <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">PERMANET DUAL</span>
                                    @elseif ($row['bras'] === 'INTERCEPTOR G2')
                                        <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold whitespace-nowrap">INTERCEPTOR G2</span>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ $row['bras'] }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                        {{ $row['cohort'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                        {{ $row['cohort'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-600 text-xs">{{ $row['village'] }}</td>
                                <td class="py-2 px-3 text-xs text-gray-500">{{ $row['cluster'] }}</td>
                                <td class="py-2 px-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                        {{ str_contains($row['form'], 'Form 7') ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $row['form'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 font-mono text-xs font-bold" style="color:var(--red)">{{ $row['net_id'] ?: '—' }}</td>
                                <td class="py-2 px-3 text-xs text-gray-500">{{ $row['date'] ?: '—' }}</td>
                                <td class="py-2 px-3 text-center">
                                    @if ($row['present'])
                                        <span class="inline-flex items-center gap-1 text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Oui
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs badge-no px-2 py-0.5 rounded-full font-semibold">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>Non
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <span class="text-xs {{ $row['used_night'] ? 'badge-ok' : 'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                        {{ $row['used_night'] ? 'Oui' : 'Non' }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <span class="text-xs {{ $row['used_7days'] ? 'badge-ok' : 'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                        {{ $row['used_7days'] ? 'Oui' : 'Non' }}
                                    </span>
                                </td>
                                @if ($showF7)
                                <td class="py-2 px-3 text-center">
                                    @if ($row['has_holes'] === null)
                                        <span class="text-gray-300 text-xs">—</span>
                                    @elseif ($row['has_holes'])
                                        <span class="text-xs badge-amb px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                    @else
                                        <span class="text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">Non</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $showF7 ? 12 : 11 }}" class="py-10 text-center text-gray-400 italic">Aucune donnée pour cette visite</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </details>
</section>
