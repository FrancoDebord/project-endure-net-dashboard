    <div id="tab-ua" class="tab-panel space-y-6">

        @php
            $uaTotal          = $ua['total']          ?? 0;
            $uaLike           = $ua['like']            ?? 0;
            $uaSatisfied      = $ua['satisfied']       ?? 0;
            $uaContinue       = $ua['continueUse']     ?? 0;
            $uaRecommend      = $ua['recommend']       ?? 0;
            $uaEasy           = $ua['easyUse']         ?? 0;
            $uaSatDist        = collect($ua['satisfactionDist'] ?? []);
            $uaRows           = collect($ua['rows'] ?? []);
            $uaByArm          = $ua['byArm']           ?? ['PD' => [], 'G2' => []];
            $uaRespRoleDist   = $ua['respRoleDist']    ?? [];
            $uaImportance     = $ua['importanceStats'] ?? [];
            $uaOpenEnded      = $ua['openEndedThemes'] ?? [];

            $pctUA = fn($n) => $uaTotal > 0 ? round($n / $uaTotal * 100, 1) : 0;
        @endphp

        {{-- KPI global --}}
        @php
            $pdData = $uaByArm['PD'] ?? [];
            $g2Data = $uaByArm['G2'] ?? [];
            $pdN    = $pdData['total'] ?? 0;
            $g2N    = $g2Data['total'] ?? 0;
            $pctPD  = fn($v) => $pdN > 0 ? round($v / $pdN * 100, 1) : 0;
            $pctG2  = fn($v) => $g2N > 0 ? round($v / $g2N * 100, 1) : 0;
        @endphp
        <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            {{-- MÉNAGES --}}
            <div class="kpi-card card border-t-2 p-4" style="border-color:var(--red)">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Ménages</p>
                <p class="text-3xl font-extrabold" style="color:var(--red)">{{ $uaTotal }}</p>
                <div class="mt-2 pt-2 border-t border-gray-100 grid grid-cols-2 gap-1 text-center">
                    <div>
                        <p class="text-xs font-bold text-purple-600">{{ $pdN }}</p>
                        <p class="text-xs text-gray-400">PD</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-600">{{ $g2N }}</p>
                        <p class="text-xs text-gray-400">G2</p>
                    </div>
                </div>
            </div>
            {{-- Indicateurs avec bras --}}
            @foreach ([
                ['Aiment le filet',     $uaLike,      'text-green-600',  'border-green-400',  'like'],
                ['Satisfaits (≥ sat.)', $uaSatisfied, 'text-blue-600',   'border-blue-400',   'satisfied'],
                ['Continueraient',      $uaContinue,  'text-teal-600',   'border-teal-400',   'continueUse'],
                ['Recommanderaient',    $uaRecommend, 'text-violet-600', 'border-violet-400', 'recommend'],
                ['Utilisation facile',  $uaEasy,      'text-amber-600',  'border-amber-400',  'easyUse'],
            ] as [$lbl, $val, $textCls, $borderCls, $key])
            @php
                $pdVal = $pdData[$key] ?? 0;
                $g2Val = $g2Data[$key] ?? 0;
            @endphp
                <div class="kpi-card card border-t-2 p-4 {{ $borderCls }}">
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">{{ $lbl }}</p>
                    <p class="text-3xl font-extrabold {{ $textCls }}">{{ $val }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $pctUA($val) }}%</p>
                    <div class="mt-2 pt-2 border-t border-gray-100 grid grid-cols-2 gap-1 text-center">
                        <div>
                            <p class="text-xs font-bold text-purple-600">{{ $pdVal }}</p>
                            <p class="text-xs text-gray-400">PD · {{ $pctPD($pdVal) }}%</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-600">{{ $g2Val }}</p>
                            <p class="text-xs text-gray-400">G2 · {{ $pctG2($g2Val) }}%</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- KPI par bras --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $uaByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $uaByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armBg, $armData])
            @php
                $armN       = $armData['total']   ?? 0;
                $armAbsent  = $armData['absent']  ?? 0;
                $armPresent = $armData['present'] ?? 0;
                $pctArmKpi   = fn($v) => $armPresent > 0 ? round($v / $armPresent * 100, 1) : 0;
                $pctArmTotal = fn($v) => $armN > 0 ? round($v / $armN * 100, 1) : 0;
            @endphp
            <div class="card overflow-hidden">
                <div class="px-4 py-2.5 flex items-center justify-between" style="background:{{ $armBg }}; border-bottom:2px solid {{ $armColor }}">
                    <span class="text-xs font-bold uppercase tracking-widest" style="color:{{ $armColor }}">{{ $armLabel }}</span>
                    <span class="text-xs font-semibold text-gray-500">{{ $armPresent }} présents / {{ $armN }} visités</span>
                </div>
                <div class="grid grid-cols-5 divide-x divide-gray-100">
                    @foreach ([
                        ['Aiment', $armData['like'] ?? 0],
                        ['Satisfaits', $armData['satisfied'] ?? 0],
                        ['Continueraient', $armData['continueUse'] ?? 0],
                        ['Recommandent', $armData['recommend'] ?? 0],
                        ['Facile', $armData['easyUse'] ?? 0],
                    ] as [$lbl, $val])
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 leading-tight mb-1">{{ $lbl }}</p>
                        <p class="text-xl font-extrabold" style="color:{{ $armColor }}">{{ $val }}</p>
                        <p class="text-xs text-gray-400">{{ $pctArmKpi($val) }}% des présents</p>
                    </div>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 bg-gray-50">
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 leading-tight mb-1">Présents</p>
                        <p class="text-lg font-bold text-green-600">{{ $armPresent }}</p>
                        <p class="text-xs text-gray-400">{{ $pctArmTotal($armPresent) }}% des visités</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 leading-tight mb-1">Absents</p>
                        <p class="text-lg font-bold text-amber-600">{{ $armAbsent }}</p>
                        <p class="text-xs text-gray-400">{{ $pctArmTotal($armAbsent) }}% des visités</p>
                    </div>
                </div>
            </div>
            @endforeach
        </section>

        {{-- ── Répondant & attributs du filet — PAR BRAS ──────────────────────── --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5'],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB'],
            ] as [$armKey, $armLabel, $armColor, $armBg])
            @php $armD = $uaByArm[$armKey] ?? []; @endphp
            <div class="card overflow-hidden">
                <div class="px-4 py-2 flex items-center" style="background:{{ $armBg }};border-bottom:2px solid {{ $armColor }}">
                    <span class="text-xs font-bold uppercase tracking-widest" style="color:{{ $armColor }}">{{ $armLabel }}</span>
                </div>
                <div class="p-5 space-y-6">

                    {{-- Profil du répondant --}}
                    @php $roleDist = $armD['respRoleDist'] ?? []; $roleTotal = array_sum($roleDist); @endphp
                    @if ($roleTotal > 0)
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider">Qui répond à l'enquête ?</p>
                        <div class="space-y-2">
                            @foreach ($roleDist as $roleName => $roleCount)
                            @php $rolePct = $roleTotal > 0 ? round($roleCount / $roleTotal * 100, 1) : 0; @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-600 w-44 truncate flex-shrink-0" title="{{ $roleName }}">{{ $roleName }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full" style="width:{{ $rolePct }}%; background:{{ $armColor }}"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700 w-8 text-right">{{ $roleCount }}</span>
                                <span class="text-xs text-gray-400 w-12 text-right">{{ $rolePct }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Attributs & facilité d'utilisation --}}
                    @php $impStats = $armD['importanceStats'] ?? []; @endphp
                    @if (!empty($impStats))
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider">Attributs & facilité d'utilisation</p>
                        <div class="space-y-3">
                            @foreach ($impStats as $attr)
                            @php
                                $attrLabel = $attr['label'];
                                $attrType  = $attr['type'];
                                $attrDist  = $attr['dist'];
                                if ($attrType === 'binary') {
                                    $ouiN    = $attrDist['oui'] ?? 0;
                                    $nonN    = $attrDist['non'] ?? 0;
                                    $attrTot = $attrDist['total'] ?? ($ouiN + $nonN);
                                    $ouiPct  = $attrTot > 0 ? round($ouiN / $attrTot * 100, 1) : 0;
                                } else {
                                    $attrTot = array_sum($attrDist);
                                }
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-700">{{ $attrLabel }}</span>
                                    @if ($attrType === 'binary')
                                    <span class="text-xs text-gray-500">
                                        <span class="text-green-600 font-bold">{{ $ouiN }}</span> oui
                                        &nbsp;·&nbsp;
                                        <span class="text-red-600 font-bold">{{ $nonN }}</span> non
                                    </span>
                                    @endif
                                </div>
                                @if ($attrType === 'binary')
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden flex">
                                    <div class="h-2 bg-green-400 rounded-l-full" style="width:{{ $ouiPct }}%"></div>
                                    <div class="h-2 bg-red-300 rounded-r-full" style="width:{{ 100 - $ouiPct }}%"></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $ouiPct }}% satisfaisant</p>
                                @else
                                <div class="grid grid-cols-4 gap-1">
                                    @foreach (['Très important', 'Plutôt important', 'Peu important', 'Pas important'] as $lvlLabel)
                                    @php
                                        $lvlN     = $attrDist[$lvlLabel] ?? 0;
                                        $lvlPct   = $attrTot > 0 ? round($lvlN / $attrTot * 100) : 0;
                                        $lvlColor = match($lvlLabel) {
                                            'Très important'   => '#16A34A',
                                            'Plutôt important' => '#65A30D',
                                            'Peu important'    => '#EAB308',
                                            default            => '#EF4444',
                                        };
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-xs font-bold" style="color:{{ $lvlColor }}">{{ $lvlN }}</div>
                                        <div class="text-gray-400 leading-tight" style="font-size:0.6rem">{{ $lvlLabel }}</div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>
            </div>
            @endforeach
        </section>

        {{-- ── Questions ouvertes — thématiques PAR BRAS ──────────────────────── --}}
        @php
            $pdOpenEnded    = $uaByArm['PD']['openEndedThemes'] ?? [];
            $g2OpenEnded    = $uaByArm['G2']['openEndedThemes'] ?? [];
            $openEndedKeys  = array_keys(!empty($pdOpenEnded) ? $pdOpenEnded : $g2OpenEnded);
        @endphp
        @if (!empty($openEndedKeys))
        <section class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="section-title mb-0">Questions ouvertes — analyse thématique par bras</p>
                <p class="text-xs text-gray-400 mt-1">Réponses regroupées par thèmes clés, comparées entre bras.</p>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($openEndedKeys as $qKey)
                @php
                    $pdQ          = $pdOpenEnded[$qKey] ?? ['label' => $qKey, 'total' => 0, 'themes' => [], 'other' => []];
                    $g2Q          = $g2OpenEnded[$qKey] ?? ['label' => $qKey, 'total' => 0, 'themes' => [], 'other' => []];
                    $qLabel       = $pdQ['label'] ?? $g2Q['label'] ?? $qKey;
                    $allThemeNames = array_unique(array_merge(array_keys($pdQ['themes'] ?? []), array_keys($g2Q['themes'] ?? [])));
                @endphp
                @if (($pdQ['total'] ?? 0) > 0 || ($g2Q['total'] ?? 0) > 0)
                <details class="group">
                    <summary class="flex items-center justify-between px-6 py-3 cursor-pointer select-none hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-700">{{ $qLabel }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="bg-red-50 text-red-700 px-2 py-0.5 rounded-full font-medium">PD : {{ $pdQ['total'] }}</span>
                            <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full font-medium">G2 : {{ $g2Q['total'] }}</span>
                        </div>
                    </summary>
                    <div class="px-6 pb-4 mt-2 overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-1.5 pr-3 text-gray-500 font-medium" style="width:28%">Thème</th>
                                    <th class="py-1.5 px-2 font-bold" style="color:#C41230;width:36%">PERMANET DUAL</th>
                                    <th class="py-1.5 px-2 font-bold text-gray-600" style="width:36%">INTERCEPTOR G2</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($allThemeNames as $tName)
                                @php
                                    $pdT = $pdQ['themes'][$tName] ?? ['count' => 0, 'pct' => 0];
                                    $g2T = $g2Q['themes'][$tName] ?? ['count' => 0, 'pct' => 0];
                                @endphp
                                @if ($pdT['count'] > 0 || $g2T['count'] > 0)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-1.5 pr-3 text-gray-700">{{ $tName }}</td>
                                    <td class="py-1.5 px-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full" style="width:{{ $pdT['pct'] }}%; background:#C41230"></div>
                                            </div>
                                            <span class="text-right text-gray-600 whitespace-nowrap" style="min-width:4.5rem">{{ $pdT['count'] }} ({{ $pdT['pct'] }}%)</span>
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full bg-gray-500" style="width:{{ $g2T['pct'] }}%"></div>
                                            </div>
                                            <span class="text-right text-gray-600 whitespace-nowrap" style="min-width:4.5rem">{{ $g2T['count'] }} ({{ $g2T['pct'] }}%)</span>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                                @php
                                    $pdOther = $pdQ['other'] ?? ['count' => 0, 'pct' => 0];
                                    $g2Other = $g2Q['other'] ?? ['count' => 0, 'pct' => 0];
                                @endphp
                                @if (($pdOther['count'] ?? 0) > 0 || ($g2Other['count'] ?? 0) > 0)
                                <tr class="bg-gray-50">
                                    <td class="py-1.5 pr-3 text-gray-400 italic">Non classifié</td>
                                    <td class="py-1.5 px-2 text-gray-400 text-center">{{ $pdOther['count'] ?? 0 }} ({{ $pdOther['pct'] ?? 0 }}%)</td>
                                    <td class="py-1.5 px-2 text-gray-400 text-center">{{ $g2Other['count'] ?? 0 }} ({{ $g2Other['pct'] ?? 0 }}%)</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </details>
                @endif
                @endforeach
            </div>
        </section>
        @endif

        {{-- ── Raisons par bras ──────────────────────────────────────────────── --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ([
            ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $uaByArm['PD'] ?? []],
            ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $uaByArm['G2'] ?? []],
        ] as [$armKey, $armLabel, $armColor, $armBg, $armData])
        @php
            $likeReasons    = $armData['likeReasons']    ?? [];
            $dislikeReasons = $armData['dislikeReasons'] ?? [];
            $noContReasons  = $armData['noContReasons']  ?? [];
            $likeCount      = $armData['likeCount']      ?? 0;
            $dislikeCount   = $armData['dislikeCount']   ?? 0;
            $contCount      = $armData['contCount']      ?? 0;
            $noContCount    = $armData['noContCount']    ?? 0;
        @endphp
        <div class="card overflow-hidden">
            <div class="px-4 py-2 flex items-center justify-between" style="background:{{ $armBg }};border-bottom:2px solid {{ $armColor }}">
                <span class="text-xs font-bold uppercase tracking-widest" style="color:{{ $armColor }}">{{ $armLabel }}</span>
                <span class="text-xs text-gray-400">Raisons détaillées</span>
            </div>
            <div class="p-4 space-y-4">

                {{-- Aiment le filet --}}
                <details open>
                    <summary class="flex items-center justify-between cursor-pointer select-none group">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                                ✓ Aiment le filet — {{ $likeCount }}
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="mt-2 space-y-1 pl-1">
                        @forelse ($likeReasons as $reason => $cnt)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">{{ $reason }}</span>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-green-400" style="width:{{ $likeCount > 0 ? round($cnt/$likeCount*100) : 0 }}%"></div>
                                </div>
                                <span class="font-bold text-green-700 w-6 text-right">{{ $cnt }}</span>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 italic">Aucune donnée</p>
                        @endforelse
                    </div>
                </details>

                <div class="border-t border-gray-100"></div>

                {{-- N'aiment pas le filet --}}
                <details open>
                    <summary class="flex items-center justify-between cursor-pointer select-none group">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                                ✗ N'aiment pas — {{ $dislikeCount }}
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="mt-2 space-y-1 pl-1">
                        @forelse ($dislikeReasons as $reason => $cnt)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">{{ $reason }}</span>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-red-400" style="width:{{ $dislikeCount > 0 ? round($cnt/$dislikeCount*100) : 0 }}%"></div>
                                </div>
                                <span class="font-bold text-red-700 w-6 text-right">{{ $cnt }}</span>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 italic">Aucune donnée</p>
                        @endforelse
                    </div>
                </details>

                <div class="border-t border-gray-100"></div>

                {{-- Raisons de ne pas continuer --}}
                <details>
                    <summary class="flex items-center justify-between cursor-pointer select-none group">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-orange-700 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200">
                                ↺ Continueraient : {{ $contCount }} · Ne continueraient pas : {{ $noContCount }}
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="mt-2 space-y-1 pl-1">
                        @if (!empty($noContReasons))
                            <p class="text-xs text-gray-400 mb-1 italic">Raisons de ne pas continuer :</p>
                            @foreach ($noContReasons as $reason => $cnt)
                            <div class="flex items-start justify-between text-xs gap-2">
                                <span class="text-gray-600 leading-snug">{{ $reason }}</span>
                                <span class="font-bold text-orange-700 flex-shrink-0">×{{ $cnt }}</span>
                            </div>
                            @endforeach
                        @else
                            <p class="text-xs text-gray-400 italic">Aucune raison saisie</p>
                        @endif
                    </div>
                </details>

            </div>
        </div>
        @endforeach
        </section>

        {{-- Activité journalière par tablette (UA) --}}
        @php
            $uaAllDates   = $ua['allUaDates']      ?? collect();
            $uaTabDatasets = $ua['uaTabletDatasets'] ?? collect();
        @endphp
        @if ($uaTabDatasets->isNotEmpty())
        <section class="card p-6">
            <p class="section-title mb-4">Activité journalière par tablette — User Acceptability</p>
            <canvas id="chartUATablets" height="120"></canvas>
        </section>
        @endif

        {{-- Satisfaction dist + table --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="card p-6">
                <p class="section-title">Distribution satisfaction globale</p>
                @if ($uaSatDist->isNotEmpty())
                    <canvas id="chartUASat" height="220"></canvas>
                    <div class="mt-4 space-y-2">
                        @foreach ($uaSatDist as $label => $count)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-bold text-gray-800">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucune donnée</p>
                @endif
            </div>

            <div class="card overflow-hidden lg:col-span-2">
                <details open>
                    <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition select-none">
                        <div class="flex items-center gap-3">
                            <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="section-title mb-0">Détail par ménage — User Acceptability</span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $uaRows->count() }}</span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="fwTable-ua">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                        <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                        <th class="py-2.5 px-3 text-left">Bras</th>
                                        <th class="py-2.5 px-3 text-left">Cohorte</th>
                                        <th class="py-2.5 px-3 text-left">Village</th>
                                        <th class="py-2.5 px-3 text-left">Grappe</th>
                                        <th class="py-2.5 px-3 text-center">Aime</th>
                                        <th class="py-2.5 px-3 text-left">Satisfaction</th>
                                        <th class="py-2.5 px-3 text-center">Continuer</th>
                                        <th class="py-2.5 px-3 text-center">Recommande</th>
                                        <th class="py-2.5 px-3 text-center">Facile</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($uaRows as $row)
                                        <tr class="hov-row transition-colors">
                                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $row['id'] }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($row['bras'] === 'PERMANET DUAL')
                                                    <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold">PERMANET DUAL</span>
                                                @elseif ($row['bras'] === 'INTERCEPTOR G2')
                                                    <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold">INTERCEPTOR G2</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ $row['bras'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ $row['cohort'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                                    {{ $row['cohort'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-xs text-gray-600">{{ $row['village'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $row['cluster'] }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['net_like']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['net_like'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-xs">
                                                @php
                                                    $satClass = match($row['satisfaction']) {
                                                        'Très satisfait' => 'badge-ok',
                                                        'Satisfait'      => 'bg-blue-50 text-blue-700',
                                                        'Insatisfait'    => 'badge-amb',
                                                        'Très insatisfait'=> 'badge-no',
                                                        default          => 'badge-gray',
                                                    };
                                                @endphp
                                                <span class="text-xs {{ $satClass }} px-2 py-0.5 rounded-full font-medium">
                                                    {{ $row['satisfaction'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['continue_use']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['continue_use'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['recommend']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['recommend'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-xs {{ $row['easy_use']==='Oui'?'badge-ok':'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                    {{ $row['easy_use'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="10" class="py-8 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </div>

        </section>

    {{-- Inline chart scripts for UA — executed by injectHTML on lazy-load --}}
    <script>
    (function(){
        const RED='#C41230',RED_DARK='#8B0D21',RED_LT='#F9A8B4',GRAY='#374151',GREEN='#16A34A',GREEN_LT='#86EFAC';
        // ── Chart UA Satisfaction ──
        @if (!empty($ua['satisfactionDist']) && collect($ua['satisfactionDist'])->isNotEmpty())
        (function(){
            const el = document.getElementById('chartUASat');
            if (!el) return;
            const labels = {!! collect($ua['satisfactionDist'])->keys()->toJson() !!};
            const data   = {!! collect($ua['satisfactionDist'])->values()->toJson() !!};
            new Chart(el, {
                type: 'doughnut',
                data: { labels: labels, datasets: [{ data: data, backgroundColor: [RED, '#2563EB', '#D97706', '#374151'], borderWidth: 2 }] },
                options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
            });
        })();
        @endif
        // ── Chart UA tablettes ──
        @if (!empty($ua['uaTabletDatasets']) && collect($ua['uaTabletDatasets'])->isNotEmpty())
        (function(){
            const el = document.getElementById('chartUATablets');
            if (!el) return;
            const labels   = {!! collect($ua['allUaDates'] ?? [])->toJson() !!};
            const datasets = {!! collect($ua['uaTabletDatasets'] ?? [])->toJson() !!};
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets.map(d => ({
                        label: d.label,
                        data: d.data,
                        backgroundColor: d.color + 'CC',
                        borderColor: d.color,
                        borderWidth: 1,
                        borderRadius: 3,
                    }))
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        x: { stacked: false, ticks: { font: { size: 10 } } },
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } }
                    }
                }
            });
        })();
        @endif
        // ── DataTable UA ──
        if ($.fn && $.fn.DataTable && !$.fn.DataTable.isDataTable('#fwTable-ua')) {
            $('#fwTable-ua').DataTable({
                layout: { topStart: 'buttons', topEnd: 'search', bottomStart: 'info', bottomEnd: 'paging' },
                buttons: [
                    { extend: 'csv',   text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>CSV',   className: 'dt-export-btn', title: 'UA-Acceptabilite', exportOptions: { columns: ':visible' } },
                    { extend: 'excel', text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Excel', className: 'dt-export-btn', title: 'UA-Acceptabilite', exportOptions: { columns: ':visible' } },
                    { extend: 'pdf',   text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>PDF',   className: 'dt-export-btn', title: 'UA — Acceptabilité ENDURE-Net', orientation: 'landscape', exportOptions: { columns: ':visible' } },
                ],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Tout']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json',
                },
                order: [[0, 'asc']],
            });
        }
    })();
    </script>

    </div>{{-- /tab-ua --}}
