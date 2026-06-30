    <div id="tab-distribution" class="tab-panel space-y-6">

        @php if (!empty($baseline) && is_array($baseline)) extract($baseline); @endphp

        {{-- ── Filters ── --}}
        <div class="card p-5">
            <p class="section-title">Distribution des moustiquaires par binôme</p>

            <div class="flex flex-wrap items-end gap-4 mb-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                    <input type="date" id="distFrom"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"
                           onchange="applyDistFilter()">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                    <input type="date" id="distTo"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"
                           onchange="applyDistFilter()">
                </div>
                <button onclick="resetDistFilter()"
                        class="text-xs text-gray-500 underline hover:text-red-600 transition pb-1.5">
                    Réinitialiser
                </button>
                <div id="distSummary" class="text-sm text-gray-500 pb-1.5">
                    <span class="font-bold text-gray-800">{{ $netsByTablet->sum('visited') }}</span> visités ·
                    <span class="font-bold text-gray-800">{{ $netsByTablet->sum('consented') }}</span> enrôlés ·
                    <span id="distTotalNets" class="font-bold text-gray-800">{{ $netsByTablet->sum('total') }}</span> moustiquaires
                    (<span id="distTotalHH">{{ $netsByTablet->sum(fn($t) => count($t['households'])) }}</span> ménages)
                </div>
            </div>

            @if ($netsByTablet->isEmpty())
                <p class="text-xs text-gray-400 italic">Aucune moustiquaire enregistrée.</p>
            @else

            @php
                $distColors = ['#C41230','#374151','#D97706','#16A34A','#7C3AED','#0891B2','#DB2777','#059669','#EA580C','#4338CA','#0D9488','#9333EA'];
            @endphp

            <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
                <span class="font-semibold text-gray-600">Dotation MILDA :</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-400"></span> Conforme (1 MILDA / 2–3 pers.)</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400"></span> Sous-doté</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span> Sur-doté</span>
            </div>

            @foreach ($netsByTablet as $dti => $tabletGroup)
            <details class="dist-tablet-section mb-4 rounded-xl border border-gray-200 overflow-hidden" open
                     data-tablet="{{ $tabletGroup['tablet'] }}">
                <summary class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer select-none list-none"
                         style="background:#F8F9FB">
                    <div class="flex items-center gap-2 min-w-0 flex-wrap">
                        <span class="inline-block w-3 h-3 rounded-sm flex-shrink-0"
                              style="background:{{ $distColors[$dti % count($distColors)] }}"></span>
                        <span class="font-bold text-sm text-gray-800">Tablette {{ $tabletGroup['tablet'] }}</span>
                        @php $tabletBras = $tabletGroup['households']->first()['bras'] ?? ''; @endphp
                        @if ($tabletBras)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                {{ str_contains($tabletBras, 'DUAL') ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700' }}">
                                {{ $tabletBras }}
                            </span>
                        @endif
                        <span class="font-normal text-gray-500 text-xs">
                            — {{ $tabletGroup['visited'] }} visité(s)
                            · {{ $tabletGroup['consented'] }} enrôlé(s)
                            · <span class="dist-tablet-nets">{{ $tabletGroup['total'] }}</span> moustiquaire(s)
                            sur <span class="dist-tablet-hh">{{ count($tabletGroup['households']) }}</span> ménage(s)
                        </span>
                        @if ($tabletGroup['net_low'] > 0)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700">
                                {{ $tabletGroup['net_low'] }} sous-doté{{ $tabletGroup['net_low'] > 1 ? 's' : '' }}
                            </span>
                        @endif
                        @if ($tabletGroup['net_high'] > 0)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                {{ $tabletGroup['net_high'] }} sur-doté{{ $tabletGroup['net_high'] > 1 ? 's' : '' }}
                            </span>
                        @endif
                        @if (($tabletGroup['pd_nets'] ?? 0) > 0)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">
                                {{ $tabletGroup['pd_nets'] }} PD distribué{{ $tabletGroup['pd_nets'] > 1 ? 'es' : 'e' }}
                            </span>
                        @endif
                        @if (($tabletGroup['ig2_nets'] ?? 0) > 0)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $tabletGroup['ig2_marked'] ?? 0 }}/{{ $tabletGroup['ig2_nets'] }} IG2 marquée{{ ($tabletGroup['ig2_nets'] ?? 0) > 1 ? 's' : '' }}
                            </span>
                        @endif
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 chevron"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:28%">Code Ménage</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:8%">Taille</th>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:14%">Date visite</th>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A">Codes Moustiquaires</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A;width:10%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tabletGroup['households'] as $hi => $hh)
                            <tr class="dist-hh-row border-b border-gray-100 {{ $hi % 2 === 1 ? 'bg-gray-50' : '' }}"
                                data-date="{{ $hh['date_visit'] }}"
                                data-nets="{{ $hh['total'] }}">
                                <td class="px-3 py-2 font-mono">{{ $hh['household_id'] }}</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ $hh['members'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-gray-600">
                                    {{ $hh['date_visit'] ? \Carbon\Carbon::parse($hh['date_visit'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">{{ $hh['nets']->implode(' · ') }}</td>
                                <td class="px-3 py-2 text-right font-bold text-gray-800">
                                    {{ $hh['total'] }}
                                    @if ($hh['net_status'] === 'low')
                                        <span class="text-xs font-semibold text-orange-600 ml-1 whitespace-nowrap"
                                              title="Attendu {{ $hh['net_min'] }}–{{ $hh['net_max'] }} pour {{ $hh['members'] }} pers.">sous-doté</span>
                                    @elseif ($hh['net_status'] === 'high')
                                        <span class="text-xs font-semibold text-blue-600 ml-1 whitespace-nowrap"
                                              title="Attendu {{ $hh['net_min'] }}–{{ $hh['net_max'] }} pour {{ $hh['members'] }} pers.">sur-doté</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if (($tabletGroup['pd_nets'] ?? 0) > 0)
                            <tr style="background:#FEF2F2">
                                <td colspan="4" class="px-3 py-1.5 font-semibold text-xs" style="color:#B91C1C">
                                    PERMANET DUAL — distribuées
                                </td>
                                <td class="px-3 py-1.5 text-right font-bold text-xs" style="color:#B91C1C">
                                    {{ $tabletGroup['pd_nets'] }}
                                </td>
                            </tr>
                            @endif
                            @if (($tabletGroup['ig2_nets'] ?? 0) > 0)
                            <tr style="background:#F5F3FF">
                                <td colspan="4" class="px-3 py-1.5 font-semibold text-xs" style="color:#6D28D9">
                                    INTERCEPTOR G2 — marquées&thinsp;/&thinsp;total
                                </td>
                                <td class="px-3 py-1.5 text-right font-bold text-xs" style="color:#6D28D9">
                                    {{ $tabletGroup['ig2_marked'] ?? 0 }}&thinsp;/&thinsp;{{ $tabletGroup['ig2_nets'] }}
                                </td>
                            </tr>
                            @endif
                            <tr style="background:#C41230">
                                <td colspan="4" class="px-3 py-2 text-white font-bold text-xs">Total Tablette {{ $tabletGroup['tablet'] }}</td>
                                <td class="px-3 py-2 text-right text-white font-bold dist-tablet-total">{{ $tabletGroup['total'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </details>
            @endforeach

            @endif
        </div>

        {{-- ── PDF Export : détail ── --}}
        <div class="card p-5">
            <p class="section-title">Exporter en PDF — Détail par ménage</p>
            <form method="GET" action="{{ route('distribution.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Tablettes / Binômes</label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, $reportTablets->count())) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            style="background:#C41230"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger PDF distribution
                    </button>
                    <p class="text-xs text-gray-400">Format portrait A4 · détail par ménage et par tablette</p>
                </div>
            </form>
        </div>

        {{-- ── PDF Export : résumé ── --}}
        <div class="card p-5" style="background:#F0FDF4;border:1px solid #BBF7D0">
            <p class="section-title" style="color:#166534">Exporter en PDF — Résumé par binôme</p>
            <p class="text-xs text-green-700 mb-4">
                Tableau synthèse par tablette et par date : ménages visités, enrôlés, moustiquaires distribuées et marquées.
            </p>
            <form method="GET" action="{{ route('distribution.resume.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de début</label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Date de fin</label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">Tablettes / Binômes</label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, $reportTablets->count())) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            style="background:#16A34A"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le résumé PDF
                    </button>
                    <p class="text-xs text-gray-400">Format portrait A4 · une ligne par date et par binôme</p>
                </div>
            </form>
        </div>

    </div>{{-- /tab-distribution --}}
