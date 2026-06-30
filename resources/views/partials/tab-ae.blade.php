    <div id="tab-ae" class="tab-panel space-y-6">

        @php
            $aeTotal          = $ae['total']          ?? 0;
            $aeSymptom        = $ae['anySymptom']      ?? 0;
            $aeContinue       = $ae['continueUse']     ?? 0;
            $aeBedbugs        = $ae['bedbugs']         ?? 0;
            $aeBbOnNet        = $ae['bbOnNet']         ?? 0;
            $aeSymCounts      = collect($ae['symptomCounts'] ?? []);
            $aeRows           = collect($ae['rows'] ?? []);
            $aeByArm          = $ae['byArm']          ?? ['PD' => [], 'G2' => []];
            $aeSymByArm       = $ae['symptomsByArm']  ?? ['PD' => [], 'G2' => []];
            $aeAbsentByArm    = $ae['absentByArm']    ?? ['PD' => [], 'G2' => []];

            $pctAE = fn($n) => $aeTotal > 0 ? round($n / $aeTotal * 100, 1) : 0;
        @endphp

        {{-- ── FORMULAIRE 5a — Ménages (par bras) ───────────────────────────── --}}
        <section class="space-y-3">
            <p class="section-title">Formulaire 5a — Ménages (par bras)</p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $aeByArm['PD'] ?? [], $aeAbsentByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $aeByArm['G2'] ?? [], $aeAbsentByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armBg, $armData, $armAbsent])
            @php
                $armSelected  = $armAbsent['selected']      ?? 0;
                $armEnquetes  = $armData['total']            ?? 0;
                $armPresentN  = $armAbsent['present']        ?? 0;
                $armAbsentN   = $armAbsent['absent']         ?? 0;
                $armBedbugs   = $armData['bedbugs']          ?? 0;
                $armItnTot    = $armAbsent['nets_expected']  ?? 0;
                $armItnPres   = $armAbsent['nets_followed']  ?? 0;
                $pctSel = fn($v) => $armSelected > 0 ? round($v / $armSelected * 100, 1) : 0;
            @endphp
            <div class="card overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between" style="background:{{ $armBg }};border-bottom:2px solid {{ $armColor }}">
                    <span class="font-bold uppercase tracking-widest text-sm" style="color:{{ $armColor }}">{{ $armLabel }}</span>
                    <span class="text-xs font-semibold text-gray-500">{{ $armSelected }} ménages sélectionnés</span>
                </div>
                {{-- Ligne 1 : funnel ménages --}}
                <div class="grid grid-cols-4 divide-x divide-gray-100">
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-wide leading-tight mb-2">Sélectionnés</p>
                        <p class="text-2xl font-extrabold text-gray-700">{{ $armSelected }}</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-wide leading-tight mb-2">Visités</p>
                        <p class="text-2xl font-extrabold" style="color:{{ $armColor }}">{{ 371 }}</p>
                        <p class="text-xs text-gray-400">{{ $pctSel(371) }}%</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-wide leading-tight mb-2">Présents</p>
                        <p class="text-2xl font-extrabold text-green-600">{{ $armPresentN }}</p>
                        <p class="text-xs text-gray-400">{{ $pctSel($armPresentN) }}%</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-wide leading-tight mb-2">Absents</p>
                        <p class="text-2xl font-extrabold text-amber-600">{{ $armAbsentN }}</p>
                        <p class="text-xs text-gray-400">{{ $pctSel($armAbsentN) }}%</p>
                    </div>
                </div>
                {{-- Ligne 2 : filets & punaises --}}
                <div class="grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100 bg-gray-50">
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 mb-1">Punaises maison</p>
                        <p class="text-xl font-extrabold text-rose-600">{{ $armBedbugs }}</p>
                        <p class="text-xs text-gray-400">{{ $pctSel($armBedbugs) }}% des sélectionnés</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 mb-1">Filets attendus</p>
                        <p class="text-xl font-extrabold text-gray-600">{{ $armItnTot }}</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-xs text-gray-400 mb-1">Filets présents</p>
                        <p class="text-xl font-extrabold text-blue-600">{{ $armItnPres }}</p>
                        <p class="text-xs text-gray-400">{{ $armItnTot > 0 ? round($armItnPres / $armItnTot * 100, 1) : 0 }}%</p>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
        </section>

        {{-- ── FORMULAIRE 5b — Moustiquaires (par bras) ───────────────────────── --}}
        <section class="space-y-3">
            <p class="section-title">Formulaire 5b — Moustiquaires (par bras)</p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $aeByArm['PD'] ?? [], $aeAbsentByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $aeByArm['G2'] ?? [], $aeAbsentByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armBg, $armData, $armAbsent])
            @php
                $armNetCount  = $armData['netCount']    ?? 0;
                $armHhWithNet = $armData['hhWithNets']  ?? 0;
                $armAnySymp   = $armData['anySymptom']  ?? 0;
                $armContinue  = $armData['continueUse'] ?? 0;
                $armPhys      = $armData['reportedPhysician'] ?? 0;
                $armSelected  = $armAbsent['selected']  ?? 0;
                $avgPerHh     = $armHhWithNet > 0 ? round($armNetCount / $armHhWithNet, 1) : 0;
                $pctHh  = fn($v) => $armHhWithNet > 0 ? round($v / $armHhWithNet * 100, 1) : 0;
            @endphp
            <div class="card overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between" style="background:{{ $armBg }};border-bottom:2px solid {{ $armColor }}">
                    <span class="font-bold uppercase tracking-widest text-sm" style="color:{{ $armColor }}">{{ $armLabel }}</span>
                    <span class="text-xs font-semibold text-gray-500">{{ $armSelected }} ménages sélectionnés</span>
                </div>
                {{-- Moustiquaires suivies -- bannière --}}
                <div class="grid grid-cols-2 divide-x divide-gray-100">
                    <div class="p-5 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Moustiquaires suivies</p>
                        <p class="text-4xl font-extrabold" style="color:{{ $armColor }}">{{ $armNetCount }}</p>
                        <p class="text-xs text-gray-400 mt-1">dans {{ $armAbsent['present'] ?? $armHhWithNet }} ménage(s) présents</p>
                    </div>
                    <div class="p-5 text-center">
                        <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Moyenne / ménage</p>
                        <p class="text-4xl font-extrabold text-gray-600">{{ $avgPerHh }}</p>
                        <p class="text-xs text-gray-400 mt-1">moustiquaire(s) par ménage</p>
                    </div>
                </div>
                {{-- Indicateurs ménages --}}
                <div class="grid grid-cols-3 divide-x divide-gray-100 border-t border-gray-100">
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 mb-1">Avec symptôme(s)</p>
                        <p class="text-2xl font-extrabold text-amber-600">{{ $armAnySymp }}</p>
                        <p class="text-xs text-gray-400">{{ $pctHh($armAnySymp) }}% des évalués</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 mb-1">Continueraient</p>
                        <p class="text-2xl font-extrabold text-green-600">{{ $armContinue }}</p>
                        <p class="text-xs text-gray-400">{{ $pctHh($armContinue) }}% des évalués</p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-xs text-gray-400 mb-1">Consulté médecin</p>
                        <p class="text-2xl font-extrabold text-blue-600">{{ $armPhys }}</p>
                        <p class="text-xs text-gray-400">{{ $pctHh($armPhys) }}% des évalués</p>
                    </div>
                </div>
            </div>
            @endforeach
            </div>
        </section>

        {{-- ── Répondant AE & présence filets ───────────────────────────── --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $aeByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $aeByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armBg, $armData])
            @php
                $armN     = $armData['total'] ?? 0;
                $pctA     = fn($v) => $armN > 0 ? round($v / $armN * 100, 1) : 0;
                $respDist = $armData['respondentDist'] ?? [];
                $respTot  = array_sum($respDist);
                $itnPres  = $armData['itnPresent'] ?? 0;
                $itnTot   = $armData['itnTotal']   ?? 0;
            @endphp
            <div class="card p-5">
                <div class="text-xs font-bold uppercase tracking-widest mb-3 pl-2 border-l-4" style="color:{{ $armColor }};border-color:{{ $armColor }}">
                    {{ $armLabel }} — Profil répondant & filets
                </div>
                @if (!empty($respDist))
                <p class="text-xs font-semibold text-gray-500 mb-2">Qui a répondu ?</p>
                <div class="space-y-1.5 mb-4">
                    @foreach ($respDist as $roleName => $roleCount)
                    @php $rolePct = $respTot > 0 ? round($roleCount / $respTot * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-600 flex-1 truncate">{{ $roleName }}</span>
                        <div class="w-20 bg-gray-100 rounded-full h-1.5 overflow-hidden flex-shrink-0">
                            <div class="h-1.5 rounded-full" style="width:{{ $rolePct }}%;background:{{ $armColor }}"></div>
                        </div>
                        <span class="text-xs font-bold w-8 text-right" style="color:{{ $armColor }}">{{ $roleCount }}</span>
                        <span class="text-xs text-gray-400 w-10 text-right">{{ $rolePct }}%</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @if ($itnTot > 0)
                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                    <span class="text-xs text-gray-600">Filets présents / attendus</span>
                    <span class="text-sm font-bold" style="color:{{ $armColor }}">
                        {{ $itnPres }} / {{ $itnTot }}
                        <span class="text-xs text-gray-400 font-normal">({{ $itnTot > 0 ? round($itnPres/$itnTot*100,1) : 0 }}%)</span>
                    </span>
                </div>
                @endif
            </div>
            @endforeach
        </section>

        {{-- ── Punaises de lit par bras ───────────────────────────────────── --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', '#FDF4F5', $aeByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', '#F9FAFB', $aeByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armBg, $armData])
            @php
                $armN      = $armData['total']       ?? 0;
                $pctA      = fn($v) => $armN > 0 ? round($v / $armN * 100, 1) : 0;
                $bbComm    = $armData['bbCommunity'] ?? 0;
                $bbHouse   = $armData['bedbugs']     ?? 0;
                $bbLocs    = $armData['bbLocations'] ?? [];
                $bbNetEff  = $armData['bbNetEff']    ?? 0;
                $bbSeen    = $armData['bbSeenOnNet'] ?? 0;
                $bbStop    = $armData['bbStopNet']   ?? 0;
                $bbAct     = $armData['bbActionDist']?? [];
            @endphp
            <div class="card p-5 space-y-4">
                <div class="text-xs font-bold uppercase tracking-widest pl-2 border-l-4" style="color:{{ $armColor }};border-color:{{ $armColor }}">
                    {{ $armLabel }} — Punaises de lit
                </div>
                {{-- Global --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-400 mb-1">Problème communauté</p>
                        <p class="text-xl font-extrabold" style="color:{{ $armColor }}">{{ $bbComm }}</p>
                        <p class="text-xs text-gray-400">{{ $pctA($bbComm) }}%</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-400 mb-1">Punaises maison actuelle</p>
                        <p class="text-xl font-extrabold" style="color:{{ $armColor }}">{{ $bbHouse }}</p>
                        <p class="text-xs text-gray-400">{{ $pctA($bbHouse) }}%</p>
                    </div>
                </div>
                {{-- Locations --}}
                @if (!empty($bbLocs))
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-2">Emplacements observés</p>
                    <div class="space-y-1">
                        @foreach ($bbLocs as $loc => $locData)
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600 flex-1">{{ $loc }}</span>
                            <div class="w-16 bg-gray-100 rounded-full h-1.5 overflow-hidden flex-shrink-0">
                                <div class="h-1.5 rounded-full" style="width:{{ $locData['pct'] }}%;background:{{ $armColor }}"></div>
                            </div>
                            <span class="text-xs font-bold w-7 text-right" style="color:{{ $armColor }}">{{ $locData['count'] }}</span>
                            <span class="text-xs text-gray-400 w-9 text-right">{{ $locData['pct'] }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                {{-- Net influence --}}
                <div class="border-t border-gray-100 pt-3 space-y-1">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Impact sur le filet</p>
                    @foreach ([
                        ['Filet efficace vs punaises', $bbNetEff],
                        ['Punaises vues sur filet', $bbSeen],
                        ['A cessé d\'utiliser à cause punaises', $bbStop],
                    ] as [$lbl, $val])
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">{{ $lbl }}</span>
                        <span class="font-bold" style="color:{{ $armColor }}">{{ $val }}
                            <span class="text-gray-400 font-normal">({{ $pctA($val) }}%)</span>
                        </span>
                    </div>
                    @endforeach
                    @if (!empty($bbAct))
                    <div class="mt-2">
                        <p class="text-xs text-gray-400 mb-1">Actions prises :</p>
                        @foreach ($bbAct as $actName => $actCount)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 truncate">{{ $actName }}</span>
                            <span class="font-semibold ml-2 flex-shrink-0" style="color:{{ $armColor }}">{{ $actCount }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </section>

        {{-- ── Symptômes par moustiquaire — par bras ──────────────────────── --}}
        <section class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <p class="section-title mb-0">Symptômes détaillés par moustiquaire et par bras</p>
                <p class="text-xs text-gray-400">Adultes uniquement · Pourcentage sur filets évalués</p>
            </div>
            @php
                $pdSym = ($aeByArm['PD']['symDetails'] ?? []);
                $g2Sym = ($aeByArm['G2']['symDetails'] ?? []);
                $pdNet = ($aeByArm['PD']['netCount']   ?? 0);
                $g2Net = ($aeByArm['G2']['netCount']   ?? 0);
                $pdAvg = ($aeByArm['PD']['avgNetUsers'] ?? 0);
                $g2Avg = ($aeByArm['G2']['avgNetUsers'] ?? 0);
            @endphp
            {{-- Net-level KPIs --}}
            <div class="grid grid-cols-2 divide-x divide-gray-100 bg-gray-50 border-b border-gray-100">
                @foreach ([
                    ['PERMANET DUAL', '#C41230', $pdNet, $pdAvg, ($aeByArm['PD']['anySymptom'] ?? 0)],
                    ['INTERCEPTOR G2','#374151', $g2Net, $g2Avg, ($aeByArm['G2']['anySymptom'] ?? 0)],
                ] as [$lbl, $col, $nets, $avg, $anySym])
                <div class="p-4 flex items-center gap-4">
                    <div class="text-center px-3">
                        <p class="text-xs text-gray-400">Filets évalués</p>
                        <p class="text-2xl font-extrabold" style="color:{{ $col }}">{{ $nets }}</p>
                    </div>
                    <div class="text-center px-3 border-l border-gray-200">
                        <p class="text-xs text-gray-400">Moy. utilisateurs/filet</p>
                        <p class="text-2xl font-extrabold" style="color:{{ $col }}">{{ $avg }}</p>
                    </div>
                    <div class="text-center px-3 border-l border-gray-200">
                        <p class="text-xs text-gray-400">Ménages avec symptôme</p>
                        <p class="text-2xl font-extrabold text-amber-600">{{ $anySym }}</p>
                        <p class="text-xs text-gray-400">{{ $nets > 0 ? round($anySym/$nets*100,1) : 0 }}%</p>
                    </div>
                    <div class="flex-1 text-center">
                        <span class="text-xs font-bold uppercase tracking-widest" style="color:{{ $col }}">{{ $lbl }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            {{-- Symptom comparison table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2.5 px-4 text-left">Symptôme</th>
                            <th class="py-2.5 px-4 text-center" style="color:#C41230">PERMANET DUAL<br><span class="font-normal normal-case">n={{ $pdNet }} nets</span></th>
                            <th class="py-2.5 px-4 text-center text-gray-400 text-xs normal-case font-normal">Groupes d'âge</th>
                            <th class="py-2.5 px-4 text-center" style="color:#374151">INTERCEPTOR G2<br><span class="font-normal normal-case">n={{ $g2Net }} nets</span></th>
                            <th class="py-2.5 px-4 text-center text-gray-400 text-xs normal-case font-normal">Groupes d'âge</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($pdSym as $field => $sData)
                        @php
                            $g2Data = $g2Sym[$field] ?? ['count' => 0, 'pct' => 0, 'ageCounts' => []];
                            $ageAbbrev = ['Adulte' => 'A', 'Enfant (< 18 ans)' => 'E', 'Femme enceinte' => 'FE'];
                        @endphp
                        <tr class="hov-row transition-colors">
                            <td class="py-2.5 px-4 font-medium text-gray-700">{{ $sData['label'] }}</td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="font-bold" style="color:#C41230">{{ $sData['count'] }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $sData['pct'] }}%)</span>
                            </td>
                            <td class="py-2.5 px-4 text-center text-xs text-gray-500">
                                @if (!empty($sData['ageCounts']))
                                    @foreach ($sData['ageCounts'] as $ag => $ac)
                                    <span class="inline-block mr-1 whitespace-nowrap">{{ $ageAbbrev[$ag] ?? $ag }}&nbsp;<strong>{{ $ac }}</strong></span>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="font-bold text-gray-700">{{ $g2Data['count'] }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $g2Data['pct'] }}%)</span>
                            </td>
                            <td class="py-2.5 px-4 text-center text-xs text-gray-500">
                                @if (!empty($g2Data['ageCounts']))
                                    @foreach ($g2Data['ageCounts'] as $ag => $ac)
                                    <span class="inline-block mr-1 whitespace-nowrap">{{ $ageAbbrev[$ag] ?? $ag }}&nbsp;<strong>{{ $ac }}</strong></span>
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Other symptoms + continue use + physician --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100 border-t border-gray-100">
                @foreach ([
                    ['PD', 'PERMANET DUAL',  '#C41230', $aeByArm['PD'] ?? []],
                    ['G2', 'INTERCEPTOR G2', '#374151', $aeByArm['G2'] ?? []],
                ] as [$armKey, $armLabel, $armColor, $armData])
                @php
                    $otherSym      = $armData['otherSymptoms']  ?? [];
                    $nonUse        = $armData['nonUseReasonDist']?? [];
                    $contN         = $armData['continueUse']    ?? 0;
                    $phys          = $armData['reportedPhysician']?? 0;
                    $armHhNets     = $armData['hhWithNets']     ?? ($armData['total'] ?? 0);
                    $pctA          = fn($v) => $armHhNets > 0 ? round($v / $armHhNets * 100, 1) : 0;
                @endphp
                <div class="p-5 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-widest pl-2 border-l-4" style="color:{{ $armColor }};border-color:{{ $armColor }}">{{ $armLabel }}</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">Continue utilisation ITN</span>
                        <span class="font-bold" style="color:{{ $armColor }}">{{ $contN }}
                            <span class="text-gray-400 font-normal">({{ $pctA($contN) }}%)</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">Consulté un médecin</span>
                        <span class="font-bold" style="color:{{ $armColor }}">{{ $phys }}
                            <span class="text-gray-400 font-normal">({{ $pctA($phys) }}%)</span>
                        </span>
                    </div>
                    @if (!empty($nonUse))
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Raisons non-utilisation :</p>
                        @foreach ($nonUse as $reasonLabel => $reasonCount)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">{{ $reasonLabel }}</span>
                            <span class="font-semibold" style="color:{{ $armColor }}">{{ $reasonCount }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if (!empty($otherSym))
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Autres symptômes signalés :</p>
                        @foreach (array_slice($otherSym, 0, 5) as $symText => $symCount)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 truncate">{{ $symText }}</span>
                            <span class="font-semibold ml-2 flex-shrink-0" style="color:{{ $armColor }}">{{ $symCount }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </section>

        {{-- Activité journalière par tablette (AE) --}}
        @php
            $aeAllDates    = $ae['allAeDates']      ?? collect();
            $aeTabDatasets = $ae['aeTabletDatasets'] ?? collect();
        @endphp
        @if (collect($aeTabDatasets)->isNotEmpty())
        <section class="card p-6">
            <p class="section-title mb-4">Activité journalière par tablette — Adverse Events</p>
            <canvas id="chartAETablets" height="120"></canvas>
        </section>
        @endif

        {{-- Symptômes par bras (graphes) --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['PD', 'PERMANET DUAL',  '#C41230', $aeSymByArm['PD'] ?? []],
                ['G2', 'INTERCEPTOR G2', '#374151', $aeSymByArm['G2'] ?? []],
            ] as [$armKey, $armLabel, $armColor, $armSym])
            @php
                $armSymptoms  = collect($armSym['symptoms'] ?? []);
                $netTotal     = $armSym['netTotal']    ?? 0;
                $netWithSymp  = $armSym['netWithSymp'] ?? 0;
                $netSympRate  = $armSym['netSympRate'] ?? 0;
                $hasSymData   = $armSymptoms->sum('count') > 0;
            @endphp
            <div class="card p-5">
                <p class="section-title mb-1">Symptômes — {{ $armLabel }}</p>
                <p class="text-xs text-gray-400 mb-4">
                    Groupe d'âge : <strong>Adultes</strong> &nbsp;·&nbsp;
                    Moustiquaires évaluées : <strong>{{ $netTotal }}</strong> &nbsp;·&nbsp;
                    Avec symptôme : <strong style="color:{{ $armColor }}">{{ $netWithSymp }} ({{ $netSympRate }}%)</strong>
                </p>
                @if ($hasSymData)
                    <canvas id="chartAESym{{ $armKey }}" height="260"></canvas>
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucun symptôme signalé</p>
                @endif
            </div>
            @endforeach
        </section>

        {{-- Tableau comparatif des symptômes --}}
        @php
            $symPD = collect($aeSymByArm['PD']['symptoms'] ?? []);
            $symG2 = collect($aeSymByArm['G2']['symptoms'] ?? []);
            $pdNet = $aeSymByArm['PD']['netTotal'] ?? 0;
            $g2Net = $aeSymByArm['G2']['netTotal'] ?? 0;
        @endphp
        @if ($symPD->isNotEmpty() || $symG2->isNotEmpty())
        <section class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="section-title mb-0">Détail des symptômes par bras (adultes)</p>
                <p class="text-xs text-gray-400 mt-1">Nombre et pourcentage de moustiquaires évaluées ayant déclenché chaque symptôme</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2.5 px-4 text-left">Symptôme</th>
                            <th class="py-2.5 px-4 text-center" style="color:#C41230">PERMANET DUAL<br><span class="font-normal normal-case">n = {{ $pdNet }} nets</span></th>
                            <th class="py-2.5 px-4 text-center" style="color:#374151">INTERCEPTOR G2<br><span class="font-normal normal-case">n = {{ $g2Net }} nets</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($symPD as $i => $s)
                        @php
                            $pdCount = $s['count'];
                            $pdPct   = $pdNet > 0 ? round($pdCount / $pdNet * 100, 1) : 0;
                            $g2s     = $symG2->get($i, []);
                            $g2Count = $g2s['count'] ?? 0;
                            $g2Pct   = $g2Net > 0 ? round($g2Count / $g2Net * 100, 1) : 0;
                        @endphp
                        <tr class="hov-row transition-colors">
                            <td class="py-2.5 px-4 font-medium text-gray-700">{{ $s['label'] }}</td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="font-bold" style="color:#C41230">{{ $pdCount }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $pdPct }}%)</span>
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="font-bold text-gray-700">{{ $g2Count }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $g2Pct }}%)</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        {{-- Détail par ménage --}}
        <section class="card overflow-hidden">
            <details open>
                <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition select-none">
                    <div class="flex items-center gap-3">
                        <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span class="section-title mb-0">Détail par ménage — Adverse Events</span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $aeRows->count() }}</span>
                    </div>
                </summary>
                <div class="px-6 pb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="fwTable-ae">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                    <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                    <th class="py-2.5 px-3 text-left">Bras</th>
                                    <th class="py-2.5 px-3 text-left">Cohorte</th>
                                    <th class="py-2.5 px-3 text-left">Village</th>
                                    <th class="py-2.5 px-3 text-left">Grappe</th>
                                    <th class="py-2.5 px-3 text-left">Date</th>
                                    <th class="py-2.5 px-3 text-center">Symptôme</th>
                                    <th class="py-2.5 px-3 text-center">Continue ITN</th>
                                    <th class="py-2.5 px-3 text-center">Punaises</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($aeRows as $row)
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
                                        <td class="py-2.5 px-3 text-xs text-gray-500">{{ $row['date'] }}</td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if ($row['any_symptom'])
                                                <span class="text-xs badge-amb px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                            @else
                                                <span class="text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">Non</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="text-xs {{ $row['continue_use'] ? 'badge-ok' : 'badge-no' }} px-2 py-0.5 rounded-full font-semibold">
                                                {{ $row['continue_use'] ? 'Oui' : 'Non' }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if ($row['bedbugs'])
                                                <span class="text-xs badge-no px-2 py-0.5 rounded-full font-semibold">Oui</span>
                                            @else
                                                <span class="text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">Non</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="py-8 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        </section>

    {{-- Inline chart scripts for AE — executed by injectHTML on lazy-load --}}
    <script>
    (function(){
        const RED='#C41230',RED_DARK='#8B0D21',RED_LT='#F9A8B4',GRAY='#374151',GRAY_DARK='#1F2937',GREEN='#16A34A',GREEN_LT='#86EFAC';
        // ── Charts AE Symptômes par bras ──
        @foreach ([
            ['PD', 'RED', 'RED_DARK'],
            ['G2', 'GRAY', 'GRAY_DARK'],
        ] as [$armKey, $bgVar, $bdVar])
        @php $armSym = $ae['symptomsByArm'][$armKey] ?? []; @endphp
        @if (!empty($armSym['symptoms']) && collect($armSym['symptoms'])->sum('count') > 0)
        (function(){
            const el = document.getElementById('chartAESym{{ $armKey }}');
            if (!el) return;
            const syms = {!! collect($armSym['symptoms'])->toJson() !!};
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: syms.map(s => s.label),
                    datasets: [{ label: 'Cas signalés', data: syms.map(s => s.count), backgroundColor: {{ $bgVar }}, borderColor: {{ $bdVar }}, borderWidth: 1, borderRadius: 4 }]
                },
                options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        })();
        @endif
        @endforeach
        // ── Chart AE tablettes ──
        @if (!empty($ae['aeTabletDatasets']) && collect($ae['aeTabletDatasets'])->isNotEmpty())
        (function(){
            const el = document.getElementById('chartAETablets');
            if (!el) return;
            const labels   = {!! collect($ae['allAeDates'] ?? [])->toJson() !!};
            const datasets = {!! collect($ae['aeTabletDatasets'] ?? [])->toJson() !!};
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
        // ── DataTable AE ──
        if ($.fn && $.fn.DataTable && !$.fn.DataTable.isDataTable('#fwTable-ae')) {
            $('#fwTable-ae').DataTable({
                layout: { topStart: 'buttons', topEnd: 'search', bottomStart: 'info', bottomEnd: 'paging' },
                buttons: [
                    { extend: 'csv',   text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>CSV',   className: 'dt-export-btn', title: 'AE-AdverseEvents', exportOptions: { columns: ':visible' } },
                    { extend: 'excel', text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Excel', className: 'dt-export-btn', title: 'AE-AdverseEvents', exportOptions: { columns: ':visible' } },
                    { extend: 'pdf',   text: '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>PDF',   className: 'dt-export-btn', title: 'AE — Adverse Events ENDURE-Net', orientation: 'landscape', exportOptions: { columns: ':visible' } },
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

    </div>{{-- /tab-ae --}}
