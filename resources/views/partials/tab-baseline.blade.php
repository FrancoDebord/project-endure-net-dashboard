    <div id="tab-baseline" class="tab-panel active space-y-8">

        @php extract($baseline); @endphp

        {{-- KPI par bras --}}
        @php
            $consentRate  = $totalVisited > 0 ? round($totalConsented / $totalVisited * 100, 1) : 0;
            $totalMembers = $membersByHh->sum();
            $brasMap = $byBras->keyBy(fn($r) => $r['label']);
            $bras1   = $brasMap->get('PERMANET DUAL',  ['total'=>0,'consented'=>0]);
            $bras2   = $brasMap->get('INTERCEPTOR G2', ['total'=>0,'consented'=>0]);
            $rate1   = $bras1['total'] > 0 ? round($bras1['consented'] / $bras1['total'] * 100, 1) : 0;
            $rate2   = $bras2['total'] > 0 ? round($bras2['consented'] / $bras2['total'] * 100, 1) : 0;
        @endphp

        <section class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {{-- Bras 1 --}}
            <div class="kpi-card card overflow-hidden">
                <div class="h-2.5" style="background:linear-gradient(90deg,#E8192C,#8B0D21)"></div>
                <div class="p-6">
                    <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full badge-red">
                        Bras 1 · PERMANET DUAL
                    </span>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Visités</p>
                            <p class="text-4xl font-black" style="color:var(--red)">{{ $bras1['total'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Consentis</p>
                            <p class="text-4xl font-black text-green-600">{{ $bras1['consented'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Taux</p>
                            <p class="text-4xl font-black text-gray-700">{{ $rate1 }}<span class="text-xl">%</span></p></div>
                    </div>
                    <div class="prog mt-3"><div class="prog-fill" style="width:{{ $rate1 }}%;background:var(--red)"></div></div>
                </div>
            </div>
            {{-- Bras 2 --}}
            <div class="kpi-card card overflow-hidden">
                <div class="h-2.5" style="background:linear-gradient(90deg,#334155,#0F172A)"></div>
                <div class="p-6">
                    <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-1 rounded-full badge-gray">
                        Bras 2 · INTERCEPTOR G2
                    </span>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Visités</p>
                            <p class="text-4xl font-black text-gray-800">{{ $bras2['total'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Consentis</p>
                            <p class="text-4xl font-black text-green-600">{{ $bras2['consented'] }}</p></div>
                        <div><p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Taux</p>
                            <p class="text-4xl font-black text-gray-700">{{ $rate2 }}<span class="text-xl">%</span></p></div>
                    </div>
                    <div class="prog mt-3"><div class="prog-fill" style="width:{{ $rate2 }}%;background:#374151"></div></div>
                </div>
            </div>
        </section>

        {{-- KPI globaux --}}
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            <div class="kpi-card card p-5" style="border-top:4px solid #C41230">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Total visités</p>
                <p class="text-4xl font-extrabold" style="color:#C41230">{{ $totalVisited }}</p>
                <p class="text-xs text-gray-400 mt-1">Global tous bras</p>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #16A34A">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Total consentis</p>
                <p class="text-4xl font-extrabold text-green-700">{{ $totalConsented }}</p>
                <p class="text-xs text-gray-400 mt-1">sur {{ $totalVisited }} visités</p>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #2563EB">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Taux global</p>
                <p class="text-4xl font-extrabold text-blue-700">{{ $consentRate }}<span class="text-xl text-gray-400">%</span></p>
                <div class="prog mt-2"><div class="prog-fill" style="width:{{ $consentRate }}%;background:#2563EB"></div></div>
            </div>
            <div class="kpi-card card p-5" style="border-top:4px solid #D97706">
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Membres enregistrés</p>
                <p class="text-4xl font-extrabold text-amber-600">{{ $totalMembers }}</p>
                <p class="text-xs text-gray-400 mt-1">tous ménages confondus</p>
            </div>
        </section>

        {{-- Répartitions : Village | Bras | Cohorte × Bras --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Par village --}}
            <div class="card p-6">
                <p class="section-title">Par village</p>
                <canvas id="chartVillage" height="180"></canvas>
                <table class="w-full text-sm mt-3">
                    <thead><tr class="text-xs text-gray-400 uppercase border-b">
                        <th class="pb-1 text-left">Village</th>
                        <th class="pb-1 text-right">Visités</th>
                        <th class="pb-1 text-right">Consentis</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($byVillage as $row)
                            <tr><td class="py-1.5 font-medium">{{ $row['label'] }}</td>
                                <td class="py-1.5 text-right text-gray-500">{{ $row['total'] }}</td>
                                <td class="py-1.5 text-right font-semibold text-green-600">{{ $row['consented'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Par bras (chart) --}}
            <div class="card p-6">
                <p class="section-title">Par bras d'étude</p>
                <canvas id="chartBras" height="180"></canvas>
                <div class="mt-3 space-y-2">
                    @foreach ($byBras as $row)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-700">{{ $row['label'] }}</p>
                                <p class="text-xs text-gray-400">{{ $row['consented'] }} consentis</p>
                            </div>
                            <p class="text-2xl font-black" style="color:var(--red)">{{ $row['total'] }}</p>
                        </div>
                        @if(!$loop->last)<hr class="border-gray-100">@endif
                    @endforeach
                    @if ($byBras->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-4 italic">Données non disponibles</p>
                    @endif
                </div>
            </div>

            {{-- Cohorte × Bras --}}
            <div class="card p-6">
                <p class="section-title">Cohortes par bras</p>
                <div class="space-y-4">
                    @foreach ($cohortByBras as $brasRow)
                        <div class="rounded-lg border border-gray-100 overflow-hidden">
                            <div class="px-3 py-2 flex items-center justify-between"
                                 style="{{ $brasRow['label'] === 'PERMANET DUAL' ? 'background:var(--red-lite)' : 'background:#F3F4F6' }}">
                                <span class="text-xs font-bold uppercase tracking-wide"
                                      style="{{ $brasRow['label'] === 'PERMANET DUAL' ? 'color:var(--red)' : 'color:#374151' }}">
                                    {{ $brasRow['label'] }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    <span class="font-semibold text-gray-700">{{ $brasRow['consented'] }}</span> consentis
                                    <span class="text-gray-400">/ {{ $brasRow['total'] }}</span>
                                </span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                @foreach ($brasRow['cohorts'] as $cohort)
                                    <div class="flex items-center justify-between px-3 py-2">
                                        <div>
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ $cohort['label'] === 'Cohorte A' ? 'badge-amb' : 'badge-slt' }}">
                                                {{ $cohort['label'] }}
                                            </span>
                                        </div>
                                        <div class="text-right flex items-center gap-3">
                                            <span class="text-xs text-gray-400">sur {{ $cohort['total'] }}</span>
                                            <span class="text-base font-bold text-gray-800">{{ $cohort['consented'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                                @if ($brasRow['cohorts']->isEmpty())
                                    <p class="text-xs text-gray-400 text-center py-3 italic">Aucune cohorte</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($cohortByBras->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-4 italic">Aucune donnée</p>
                    @endif
                </div>
            </div>

        </section>

        {{-- Grappe + Carte (carte 2/3, grappe 1/3) --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Grappe (1/3) --}}
            <div class="card p-6">
                <p class="section-title">Par grappe</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2 px-2 text-left">Grappe</th>
                            <th class="py-2 px-2 text-right">Vis.</th>
                            <th class="py-2 px-2 text-right">Cons.</th>
                            <th class="py-2 px-2 text-right">%</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($byCluster as $row)
                                @php $r = $row['total'] > 0 ? round($row['consented'] / $row['total'] * 100) : 0; @endphp
                                <tr class="hov-row">
                                    <td class="py-2 px-2 font-medium text-xs text-gray-700">{{ $row['label'] }}</td>
                                    <td class="py-2 px-2 text-right text-gray-500">{{ $row['total'] }}</td>
                                    <td class="py-2 px-2 text-right font-semibold text-green-600">{{ $row['consented'] }}</td>
                                    <td class="py-2 px-2 text-right">
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-semibold
                                            {{ $r>=80?'bg-green-100 text-green-700':($r>=50?'bg-amber-100 text-amber-700':'bg-red-100 text-red-700') }}">
                                            {{ $r }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-400 text-xs italic">Aucune donnée</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Carte (2/3) --}}
            <div class="card p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <p class="section-title mb-0">Répartition spatiale</p>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                        {{ $gpsData->count() }} ménages géolocalisés
                    </span>
                </div>
                <div id="map"></div>
            </div>

        </section>

        {{-- Performance agents : chart tablettes × dates --}}
        <section class="card p-6">
            <p class="section-title">Performance des agents — données envoyées par tablette</p>
            @if ($allDates->count())
                <canvas id="chartTablets" height="90"></canvas>
            @else
                <p class="text-xs text-gray-400 text-center py-8 italic">Aucune donnée de visite enregistrée</p>
            @endif
        </section>

        {{-- Tableau ménages dépliable + recherche --}}
        <section class="card overflow-hidden">
            <details open>
                <summary class="flex items-center justify-between px-6 py-4 bg-white hover:bg-gray-50 transition select-none">
                    <div class="flex items-center gap-3">
                        <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                        <span class="section-title mb-0">Détail par ménage</span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ $householdDetails->count() }} ménages
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">Cliquer pour replier</span>
                </summary>

                <div class="px-6 pb-6">
                    {{-- Barre de filtre --}}
                    <div class="flex flex-col sm:flex-row flex-wrap gap-2 my-4">
                        <div class="relative flex-1 min-w-[180px]">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                            </svg>
                            <input id="searchHH" type="text" placeholder="Rechercher un ménage (ID, village, bras, code filet...)"
                                   oninput="filterHH()"
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                                   style="--tw-ring-color:var(--red)">
                        </div>
                        <select id="filterBras" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les bras</option>
                            <option>PERMANET DUAL</option>
                            <option>INTERCEPTOR G2</option>
                        </select>
                        <select id="filterCohort" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Toutes les cohortes</option>
                            <option>Cohorte A</option>
                            <option>Cohorte B</option>
                        </select>
                        <select id="filterHHVillage" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les villages</option>
                            <option>Djigbe</option>
                            <option>Gbonou</option>
                            <option>Miniffi</option>
                            <option>Dogbo</option>
                            <option>Agao</option>
                            <option>Akoba</option>
                        </select>
                        <select id="filterHHGrappe" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Toutes les grappes</option>
                            <option>Grappe 1</option>
                            <option>Grappe 2</option>
                            <option>Grappe 3</option>
                        </select>
                        <select id="filterHHDag" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les DAGs</option>
                            @foreach ($householdDetails->pluck('dag')->filter()->unique()->sort() as $dagOpt)
                                <option value="{{ $dagOpt }}">{{ $dagOpt }}</option>
                            @endforeach
                        </select>
                        <select id="filterConsent" onchange="filterHH()"
                                class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <option value="">Tous les ménages</option>
                            <option value="1">Consentis</option>
                            <option value="0">Non consentis</option>
                        </select>
                        <span id="hhCount" class="flex items-center text-xs text-gray-400 whitespace-nowrap px-2">
                            {{ $householdDetails->count() }} lignes
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="hhTable">
                            <thead>
                                <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                    <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                    <th class="py-2.5 px-3 text-left">Village</th>
                                    <th class="py-2.5 px-3 text-left">Bras</th>
                                    <th class="py-2.5 px-3 text-left">Cohorte</th>
                                    <th class="py-2.5 px-3 text-left">Grappe</th>
                                    <th class="py-2.5 px-3 text-center">Consent.</th>
                                    <th class="py-2.5 px-3 text-right">Membres</th>
                                    <th class="py-2.5 px-3 text-right">Esp. couchage</th>
                                    <th class="py-2.5 px-3 text-right">Anc. filets</th>
                                    <th class="py-2.5 px-3 text-right">Filets étude</th>
                                    <th class="py-2.5 px-3 text-left">Codes</th>
                                    <th class="py-2.5 px-3 text-right">Date visite</th>
                                    <th class="py-2.5 px-3 text-right">Tablette</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse ($householdDetails as $hh)
                                    <tr class="hov-row transition-colors" data-bras="{{ $hh['bras'] }}" data-village="{{ $hh['village'] }}" data-cohort="{{ $hh['cohort'] }}" data-cluster="{{ $hh['cluster'] }}" data-dag="{{ $hh['dag'] }}" data-consented="{{ $hh['consented'] ? '1' : '0' }}">
                                        <td class="py-2.5 px-3 font-mono text-xs">
                                            <span class="{{ $hh['id_valid'] ? 'text-gray-500' : 'text-red-500' }}">{{ $hh['id'] }}</span>
                                            @if (!$hh['id_valid'])
                                                <span class="ml-1 text-red-400" title="Identifiant invalide — format attendu : VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE">⚠</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 font-medium text-gray-700">{{ $hh['village'] }}</td>
                                        <td class="py-2.5 px-3">
                                            @if ($hh['bras']==='PERMANET DUAL')
                                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold badge-red whitespace-nowrap">PERMANET DUAL</span>
                                            @elseif ($hh['bras']==='INTERCEPTOR G2')
                                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold badge-gray whitespace-nowrap">INTERCEPTOR G2</span>
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ $hh['cohort']==='Cohorte A'?'badge-amb':'badge-slt' }}">
                                                {{ $hh['cohort'] }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 text-xs text-gray-500">{{ $hh['cluster'] }}</td>
                                        <td class="py-2.5 px-3 text-center">
                                            @if ($hh['consented'])
                                                <span class="inline-flex items-center gap-1 text-xs badge-ok px-2 py-0.5 rounded-full font-semibold">
                                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Oui
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs badge-no px-2 py-0.5 rounded-full font-semibold">
                                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>Non
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-gray-700">{{ $hh['members']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-gray-600">{{ $hh['sleep_spaces'] }}</td>
                                        <td class="py-2.5 px-3 text-right text-gray-600">{{ $hh['old_nets']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right font-black" style="color:var(--red)">{{ $hh['study_nets']?:'—' }}</td>
                                        <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $hh['net_codes']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-xs text-gray-500">{{ $hh['date_visit']?:'—' }}</td>
                                        <td class="py-2.5 px-3 text-right text-xs text-gray-500">
                                            {{ $hh['tablette'] ? "T{$hh['tablette']}" : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="13" class="py-10 text-center text-gray-400 italic">Aucune donnée</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        </section>

        {{-- Moustiquaires de l'étude --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Chart nets --}}
            <div class="card p-6">
                <p class="section-title">Filets distribués</p>
                @if ($studyNetsDetail->count())
                    <canvas id="chartNets" height="180"></canvas>

                    {{-- Par bras --}}
                    <div class="mt-3 space-y-1.5">
                        @foreach ($netsByBras as $bras => $count)
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600">{{ $bras }}</span>
                                <span class="text-base font-black" style="color:var(--red)">{{ $count }}</span>
                            </div>
                        @endforeach
                        <hr class="border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-700 uppercase">Total</span>
                            <span class="text-xl font-black text-gray-800">{{ $studyNetsDetail->count() }}</span>
                        </div>
                    </div>

                    {{-- Par DAG --}}
                    @if ($byDag->count())
                        <p class="section-title mt-5 mb-2">Par DAG</p>
                        <div class="space-y-1.5">
                            @foreach ($byDag as $row)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-600">{{ $row['dag'] }}</span>
                                    <span class="text-base font-black text-gray-700">{{ $row['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-xs text-gray-400 text-center py-8 italic">Aucune moustiquaire enregistrée</p>
                @endif
            </div>

            {{-- Tableau moustiquaires (dépliable, 2/3) --}}
            <div class="card overflow-hidden lg:col-span-2">
                <details open>
                    <summary class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <svg class="chevron w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="section-title mb-0">Détail des moustiquaires de l'étude</span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                                {{ $studyNetsDetail->count() }} filets
                            </span>
                        </div>
                    </summary>
                    <div class="px-6 pb-6">
                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 my-4">
                            <div class="relative flex-1 min-w-[160px]">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                                </svg>
                                <input id="searchNets" type="text" placeholder="Rechercher un code, ménage..."
                                       oninput="filterNets()"
                                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none">
                            </div>
                            <select id="filterNetsBras" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les bras</option>
                                <option>PERMANET DUAL</option>
                                <option>INTERCEPTOR G2</option>
                            </select>
                            <select id="filterNetsVillage" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les villages</option>
                                <option>Djigbe</option>
                                <option>Gbonou</option>
                                <option>Miniffi</option>
                                <option>Dogbo</option>
                                <option>Agao</option>
                                <option>Akoba</option>
                            </select>
                            <select id="filterNetsGrappe" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Toutes les grappes</option>
                                <option>Grappe 1</option>
                                <option>Grappe 2</option>
                                <option>Grappe 3</option>
                            </select>
                            <select id="filterNetsCohort" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Toutes les cohortes</option>
                                <option>Cohorte A</option>
                                <option>Cohorte B</option>
                            </select>
                            <select id="filterNetsDag" onchange="filterNets()"
                                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                                <option value="">Tous les DAGs</option>
                                @foreach ($byDag as $row)
                                    <option value="{{ $row['dag'] }}">{{ $row['dag'] }}</option>
                                @endforeach
                            </select>
                            <span id="netsCount" class="flex items-center text-xs text-gray-400 whitespace-nowrap px-2">
                                {{ $studyNetsDetail->count() }} filets
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm" id="netsTable">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase border-b" style="background:#FDF4F5">
                                        <th class="py-2.5 px-3 text-right">#</th>
                                        <th class="py-2.5 px-3 text-left">Code filet</th>
                                        <th class="py-2.5 px-3 text-left">ID Ménage</th>
                                        <th class="py-2.5 px-3 text-left">Bras</th>
                                        <th class="py-2.5 px-3 text-left">Cohorte</th>
                                        <th class="py-2.5 px-3 text-left">Village</th>
                                        <th class="py-2.5 px-3 text-left">Grappe</th>
                                        <th class="py-2.5 px-3 text-left">DAG</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse ($studyNetsDetail as $i => $net)
                                        <tr class="hov-row transition-colors" data-bras="{{ $net['bras'] }}" data-village="{{ $net['village'] }}" data-cohort="{{ $net['cohort'] }}" data-cluster="{{ $net['cluster'] }}" data-dag="{{ $net['dag'] }}">
                                            <td class="py-2.5 px-3 text-right text-xs text-gray-400">{{ $i+1 }}</td>
                                            <td class="py-2.5 px-3 font-mono font-bold" style="color:var(--red)">
                                                {{ $net['net_code'] }}
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-xs text-gray-500">{{ $net['household_id'] }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($net['bras']==='PERMANET DUAL')
                                                    <span class="text-xs badge-red px-2 py-0.5 rounded-full font-semibold">PERMANET DUAL</span>
                                                @elseif ($net['bras']==='INTERCEPTOR G2')
                                                    <span class="text-xs badge-gray px-2 py-0.5 rounded-full font-semibold">INTERCEPTOR G2</span>
                                                @else
                                                    <span class="text-gray-400 text-xs">{{ $net['bras'] }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3">
                                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                    {{ $net['cohort']==='Cohorte A'?'badge-amb':'badge-slt' }}">
                                                    {{ $net['cohort'] }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-gray-600">{{ $net['village'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $net['cluster'] }}</td>
                                            <td class="py-2.5 px-3 text-xs text-gray-500">{{ $net['dag'] ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="py-8 text-center text-gray-400 italic">Aucune moustiquaire enregistrée</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </details>
            </div>

        </section>

    {{-- Inline chart scripts for baseline — executed by injectHTML on lazy-load --}}
    <script>
    (function(){
        const RED='#C41230',RED_DARK='#8B0D21',RED_LT='#F9A8B4',GRAY='#374151',GREEN='#16A34A',GREEN_LT='#86EFAC';
        // ── Chart Village ──
        @if ($byVillage->count())
        new Chart(document.getElementById('chartVillage'), {
            type: 'bar',
            data: {
                labels: {!! $byVillage->pluck('label')->toJson() !!},
                datasets: [
                    { label:'Visités',  data:{!! $byVillage->pluck('total')->toJson() !!},    backgroundColor:RED_LT, borderColor:RED,   borderWidth:1.5, borderRadius:4 },
                    { label:'Consentis',data:{!! $byVillage->pluck('consented')->toJson() !!},backgroundColor:GREEN_LT,borderColor:GREEN, borderWidth:1.5, borderRadius:4 }
                ]
            },
            options: { responsive:true, plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
        });
        @endif
        // ── Chart Bras ──
        @if ($byBras->count())
        new Chart(document.getElementById('chartBras'), {
            type:'doughnut',
            data:{ labels:{!! $byBras->pluck('label')->toJson() !!}, datasets:[{data:{!! $byBras->pluck('total')->toJson() !!},backgroundColor:[RED,GRAY],borderWidth:2}] },
            options:{ responsive:true, cutout:'68%', plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}} }
        });
        @endif
        // ── Chart Tablettes × Dates ──
        @if ($allDates->count() && $tabletDatasets->count())
        new Chart(document.getElementById('chartTablets'), {
            type: 'bar',
            data: {
                labels: {!! $allDates->toJson() !!},
                datasets: {!! $tabletDatasets->map(fn($d) => [
                    'label'           => $d['label'],
                    'data'            => $d['data'],
                    'backgroundColor' => $d['color'],
                    'borderColor'     => $d['color'],
                    'borderWidth'     => 1,
                    'borderRadius'    => 3,
                ])->toJson() !!}
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position:'bottom', labels:{ boxWidth:12, font:{ size:11 } } },
                    tooltip: { mode:'index', intersect:false }
                },
                scales: {
                    x: { stacked:false },
                    y: { beginAtZero:true, ticks:{ stepSize:1 }, title:{ display:true, text:'Soumissions' } }
                }
            }
        });
        @endif
        // ── Chart Performance ──
        @if (!empty($performanceChart['datasets']))
        new Chart(document.getElementById('chartPerformance'), {
            type: 'line',
            data: {
                labels: {!! json_encode($performanceChart['labels']) !!},
                datasets: {!! json_encode($performanceChart['datasets']) !!},
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            title: (items) => 'Date : ' + items[0].label,
                            label: (item) => ' ' + item.dataset.label + ' : ' + item.parsed.y + ' enrôlé(s)',
                        }
                    },
                },
                scales: {
                    x: {
                        ticks: { font: { size: 10 }, maxRotation: 45, autoSkip: true, maxTicksLimit: 30 },
                        grid: { color: '#F1F5F9' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 }, stepSize: 1 },
                        grid: { color: '#F1F5F9' },
                        title: { display: true, text: 'Enrôlés / jour', font: { size: 10 } },
                    },
                },
            },
        });
        @endif
        // ── Chart Nets ──
        @if ($netsByBras->count())
        new Chart(document.getElementById('chartNets'), {
            type:'doughnut',
            data:{ labels:{!! $netsByBras->keys()->toJson() !!}, datasets:[{data:{!! $netsByBras->values()->toJson() !!},backgroundColor:[RED,GRAY],borderWidth:2}] },
            options:{ responsive:true, cutout:'60%', plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}} }
        });
        @endif
        // ── Leaflet ──
        @php $gpsJson = $gpsData->toJson(); @endphp
        (function(){
            const pts = {!! $gpsJson !!};
            if (!pts.length) {
                const el = document.getElementById('map');
                if (el) el.innerHTML='<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9CA3AF;font-size:.875rem">Aucune coordonnée GPS</div>';
                return;
            }
            const map = L.map('map').setView([pts.reduce((s,p)=>s+p.lat,0)/pts.length, pts.reduce((s,p)=>s+p.lng,0)/pts.length], 14);
            const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '© Esri, Maxar, Earthstar Geographics', maxZoom: 19, maxNativeZoom: 17,
            });
            const esriLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19, maxNativeZoom: 17, opacity: 0.85,
            });
            [esriSat, esriLabels].forEach(layer => { layer.on('tileerror', e => { e.tile.style.opacity = '0'; }); });
            const tileLayers = {
                'OpenStreetMap': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }),
                'Satellite (Esri)': esriSat,
                'Satellite + Labels': L.layerGroup([esriSat, esriLabels]),
                'Topographique': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '© OpenTopoMap', maxZoom: 17 }),
                'CartoDB Clair': L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '© CartoDB', maxZoom: 19 }),
                'CartoDB Sombre': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© CartoDB', maxZoom: 19 }),
            };
            tileLayers['OpenStreetMap'].addTo(map);
            L.control.layers(tileLayers, {}, { position: 'topleft', collapsed: false }).addTo(map);
            const MAP_COLORS = {
                'PERMANET DUAL':  { 'Cohorte A': '#C41230', 'Cohorte B': '#F97316' },
                'INTERCEPTOR G2': { 'Cohorte A': '#374151', 'Cohorte B': '#2563EB' },
            };
            function makeIcon(color) {
                return L.divIcon({ html: `<div style="background:${color};width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`, iconSize: [12, 12], className: '' });
            }
            pts.forEach(p => {
                const color = ((MAP_COLORS[p.bras] || {})[p.cohort]) || '#9CA3AF';
                L.marker([p.lat, p.lng], { icon: makeIcon(color) }).addTo(map)
                 .bindPopup(`<strong>${p.id}</strong><br><small>${p.bras || '—'} · ${p.cohort || '—'}</small><br><small>📍 ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)}</small>`);
            });
            const legend = L.control({ position: 'bottomright' });
            legend.onAdd = function() {
                const div = L.DomUtil.create('div', '');
                div.style.cssText = 'background:white;padding:8px 10px;border-radius:8px;font-size:11px;box-shadow:0 2px 8px rgba(0,0,0,.15);line-height:1.8';
                div.innerHTML = [
                    '<b style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:3px">Légende</b>',
                    '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#C41230;display:inline-block;flex-shrink:0"></span>PERMANET DUAL — Cohorte A</div>',
                    '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#F97316;display:inline-block;flex-shrink:0"></span>PERMANET DUAL — Cohorte B</div>',
                    '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#374151;display:inline-block;flex-shrink:0"></span>INTERCEPTOR G2 — Cohorte A</div>',
                    '<div style="display:flex;align-items:center;gap:6px"><span style="width:11px;height:11px;border-radius:50%;background:#2563EB;display:inline-block;flex-shrink:0"></span>INTERCEPTOR G2 — Cohorte B</div>',
                ].join('');
                return div;
            };
            legend.addTo(map);
            const FsControl = L.Control.extend({
                options: { position: 'topright' },
                onAdd() {
                    const btn = L.DomUtil.create('button');
                    btn.title = 'Plein écran';
                    btn.style.cssText = 'width:32px;height:32px;background:white;border:2px solid rgba(0,0,0,.2);border-radius:4px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.12)';
                    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>';
                    L.DomEvent.disableClickPropagation(btn);
                    btn.addEventListener('click', () => {
                        const el = map.getContainer();
                        if (!document.fullscreenElement) { (el.requestFullscreen || el.webkitRequestFullscreen).call(el); }
                        else { (document.exitFullscreen || document.webkitExitFullscreen).call(document); }
                    });
                    const exitIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M8 3v3a2 2 0 01-2 2H3m18 0h-3a2 2 0 01-2-2V3m0 18v-3a2 2 0 012-2h3M3 16h3a2 2 0 012 2v3"/></svg>';
                    const enterIcon = btn.innerHTML;
                    document.addEventListener('fullscreenchange', () => {
                        btn.innerHTML = document.fullscreenElement ? exitIcon : enterIcon;
                        setTimeout(() => map.invalidateSize(), 100);
                    });
                    document.addEventListener('webkitfullscreenchange', () => {
                        btn.innerHTML = (document.fullscreenElement || document.webkitFullscreenElement) ? exitIcon : enterIcon;
                        setTimeout(() => map.invalidateSize(), 100);
                    });
                    return btn;
                }
            });
            new FsControl().addTo(map);
            if (pts.length > 1) map.fitBounds(L.latLngBounds(pts.map(p => [p.lat, p.lng])).pad(0.15), { maxZoom: 15 });
        })();
    })();
    </script>

    </div>{{-- /tab-baseline --}}
