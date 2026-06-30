    <div id="tab-rapport" class="tab-panel space-y-6">

        @php if (!empty($baseline) && is_array($baseline)) extract($baseline); @endphp

        <div class="card p-6">
            <p class="section-title">Rapport d'activités des binômes</p>
            <p class="text-sm text-gray-500 mb-5">
                Sélectionnez la période et les filtres souhaités, puis téléchargez le rapport PDF.
            </p>
            <form method="GET" action="{{ route('rapport.pdf') }}" target="_blank" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                    {{-- Date début --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de début
                        </label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    {{-- Date fin --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de fin
                        </label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    {{-- Tablettes (= binômes) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Tablettes / Binômes
                        </label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, count($reportTablets))) }}"
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
                            style="background:var(--red)"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger le rapport PDF
                    </button>
                    <p class="text-xs text-gray-400">Sans filtre = toutes les données</p>
                </div>
            </form>
        </div>

        {{-- ── Moustiquaires par bras / cohorte ── --}}
        @if ($byBrasCohortNets->count())
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <p class="section-title mb-0">Moustiquaires distribuées par bras et par cohorte</p>
                {{-- Onglets Résumé / Détail --}}
                <div class="flex rounded-lg overflow-hidden border border-gray-200 text-xs font-bold">
                    <button id="nettab-btn-resume" onclick="showNetTab('resume')"
                            class="px-4 py-1.5 transition"
                            style="background:#1A1A1A;color:#fff">
                        Résumé
                    </button>
                    <button id="nettab-btn-detail" onclick="showNetTab('detail')"
                            class="px-4 py-1.5 transition bg-white text-gray-500 hover:bg-gray-50">
                        Détail par village &amp; grappe
                    </button>
                </div>
            </div>

            {{-- ─ Vue Résumé ─ --}}
            <div id="nettab-resume">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 text-white text-xs font-bold uppercase tracking-wide rounded-tl-lg" style="background:#1A1A1A">Bras / Cohorte</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide" style="background:#1A1A1A">Distribuées</th>
                                <th class="text-right px-3 py-2 text-white text-xs font-bold uppercase tracking-wide rounded-tr-lg" style="background:#1A1A1A">Marquées</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalNetsDisplay = $byBrasCohortNets->sum('nets'); $totalMarkedDisplay = $byBrasCohortNets->sum('marked'); @endphp
                            @foreach ($byBrasCohortNets as $bi => $b)
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-2 font-bold text-gray-800">
                                        <span class="inline-block w-2.5 h-2.5 rounded-sm mr-1.5 align-middle" style="background:{{ $bi === 0 ? '#C41230' : '#374151' }}"></span>
                                        {{ $b['bras'] }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-800">{{ $b['nets'] }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-gray-800">{{ $b['marked'] }}</td>
                                </tr>
                                @foreach ($b['cohorts'] as $c)
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <td class="px-3 py-2 pl-8 text-gray-500 text-xs">{{ $c['cohort'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">{{ $c['nets'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">{{ $c['marked'] }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                            <tr style="background:#C41230">
                                <td class="px-3 py-2 text-white font-bold text-xs uppercase">Total général</td>
                                <td class="px-3 py-2 text-right text-white font-bold">{{ $totalNetsDisplay }}</td>
                                <td class="px-3 py-2 text-right text-white font-bold">{{ $totalMarkedDisplay }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─ Vue Détail (pivot : Village/Grappe × Cohortes) ─ --}}
            <div id="nettab-detail" style="display:none">
                <div class="overflow-x-auto">
                    @php
                        $colCount = $allCohorts->count() + 2; // label + cohorts + Total
                        $grandByCohort = [];
                        $grandTotal    = 0;
                        $grandMarkedTot= 0;
                        foreach ($allCohorts as $c) $grandByCohort[$c] = 0;
                    @endphp
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide rounded-tl-lg"
                                    style="background:#1A1A1A">Bras / Village / Grappe</th>
                                @foreach ($allCohorts as $c)
                                <th class="text-right px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide"
                                    style="background:#1A1A1A">{{ $c }}</th>
                                @endforeach
                                <th class="text-right px-3 py-2.5 text-white text-xs font-bold uppercase tracking-wide rounded-tr-lg"
                                    style="background:#1A1A1A">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($byBrasCohortDetail as $bi => $b)
                            @php
                                $grandTotal     += $b['total'];
                                $grandMarkedTot += $b['marked'];
                                foreach ($allCohorts as $c)
                                    $grandByCohort[$c] = ($grandByCohort[$c] ?? 0) + ($b['by_cohort'][$c] ?? 0);
                            @endphp
                            {{-- ── Section bras ── --}}
                            <tr>
                                <td colspan="{{ $colCount }}"
                                    class="px-3 py-2 text-center font-bold text-xs uppercase tracking-widest border-b border-gray-200"
                                    style="background:#F1F5F9;color:#1A1A1A">
                                    <span class="inline-block w-2 h-2 rounded-sm mr-1.5 align-middle"
                                          style="background:{{ $bi === 0 ? '#C41230' : '#374151' }}"></span>
                                    {{ $b['bras'] }}
                                </td>
                            </tr>
                            {{-- ── Lignes Village / Grappe ── --}}
                            @foreach ($b['grappes'] as $gi => $g)
                            <tr class="border-b border-gray-100 {{ $gi % 2 === 0 ? '' : 'bg-gray-50' }} hover:bg-red-50 transition-colors">
                                <td class="px-3 py-2 text-gray-700 text-xs">{{ $g['label'] }}</td>
                                @foreach ($allCohorts as $c)
                                <td class="px-3 py-2 text-right text-gray-800 text-xs font-medium">
                                    {{ $g['by_cohort'][$c] ?? 0 }}
                                </td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-xs font-bold text-gray-900">{{ $g['total'] }}</td>
                            </tr>
                            @endforeach
                            {{-- ── Sous-total bras ── --}}
                            <tr style="background:#374151">
                                <td class="px-3 py-2 text-white text-xs font-bold">
                                    Sous-total — {{ $b['bras'] }}
                                </td>
                                @foreach ($allCohorts as $c)
                                <td class="px-3 py-2 text-right text-white text-xs font-bold">{{ $b['by_cohort'][$c] ?? 0 }}</td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-white text-xs font-bold">{{ $b['total'] }}</td>
                            </tr>
                        @endforeach
                        {{-- ── Total général ── --}}
                        <tr style="background:#C41230">
                            <td class="px-3 py-2.5 text-white font-bold text-xs uppercase">Total général</td>
                            @foreach ($allCohorts as $c)
                            <td class="px-3 py-2.5 text-right text-white font-bold text-xs">{{ $grandByCohort[$c] ?? 0 }}</td>
                            @endforeach
                            <td class="px-3 py-2.5 text-right text-white font-bold">{{ $grandTotal }}</td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-400 mt-2">Valeurs = moustiquaires distribuées · Marquées (code saisi) : {{ $grandMarkedTot }} / {{ $grandTotal }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="card p-5" style="background:#EFF6FF;border:1px solid #BFDBFE">
            <p class="text-sm font-semibold text-blue-700 mb-2">Le rapport d'activités PDF contient :</p>
            <ul class="text-sm text-blue-600 space-y-1 list-disc list-inside">
                <li>Activité quotidienne par binôme : ménages visités, enrôlés et moustiquaires distribuées</li>
                <li>Sous-totaux par tablette par jour et totaux de la période</li>
                <li>Résumé cumulatif à ce jour : enrôlements et moustiquaires par bras/cohorte, par village et par grappe</li>
            </ul>
        </div>

        {{-- ── Performance des binômes — graphique interactif ── --}}
        <div class="card p-6">
            <p class="section-title">Performance des binômes — enrôlements journaliers</p>

            @if (!empty($performanceChart['datasets']))
                <canvas id="chartPerformance" height="90" class="mb-6"></canvas>
            @else
                <p class="text-xs text-gray-400 italic mb-4">Aucune donnée disponible.</p>
            @endif

            <hr class="border-gray-100 mb-5">
            <p class="text-sm font-semibold text-gray-600 mb-3">Exporter en PDF</p>
            <form method="POST" id="performancePdfForm" action="{{ route('performance.pdf') }}" target="_blank" class="space-y-5">
                @csrf
                <input type="hidden" name="chart_image" id="perfChartImage">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de début
                        </label>
                        <input type="date" name="date_from"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Date de fin
                        </label>
                        <input type="date" name="date_to"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                            Tablettes / Binômes
                        </label>
                        <select name="tablets[]" multiple
                                size="{{ max(3, min(8, count($reportTablets))) }}"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            @foreach ($reportTablets as $t)
                                <option value="{{ $t }}">Tablette {{ $t }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Ctrl+clic = multi-sélection · sans sélection = toutes</p>
                    </div>

                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="button" onclick="downloadPerformancePdf()"
                            style="background:#374151"
                            class="inline-flex items-center gap-2 text-white text-sm font-bold px-6 py-2.5 rounded-xl hover:opacity-90 active:scale-95 transition shadow">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Télécharger le rapport de performance PDF
                    </button>
                    <p class="text-xs text-gray-400">Format paysage A4 · courbes journalières par tablette</p>
                </div>
            </form>
        </div>

        <div class="card p-5" style="background:#F0FDF4;border:1px solid #BBF7D0">
            <p class="text-sm font-semibold text-green-700 mb-2">Le rapport de performance PDF contient :</p>
            <ul class="text-sm text-green-600 space-y-1 list-disc list-inside">
                <li>Tableau synthèse : jours d'activité, total enrôlés, moyenne par jour actif et par jour calendaire</li>
                <li>Courbes journalières d'enrôlements par binôme (une couleur par tablette)</li>
            </ul>
        </div>

<script>
if (typeof showNetTab === 'undefined') {
    window.showNetTab = function(name) {
        ['resume', 'detail'].forEach(function(t) {
            var panel = document.getElementById('nettab-' + t);
            var btn   = document.getElementById('nettab-btn-' + t);
            if (!panel || !btn) return;
            var active = t === name;
            panel.style.display  = active ? '' : 'none';
            btn.style.background = active ? '#1A1A1A' : '#fff';
            btn.style.color      = active ? '#fff'    : '#6B7280';
        });
    };
}
if (typeof downloadPerformancePdf === 'undefined') {
    window.downloadPerformancePdf = function() {
        var canvas = document.getElementById('chartPerformance');
        if (canvas) {
            document.getElementById('perfChartImage').value = canvas.toDataURL('image/png');
        }
        document.getElementById('performancePdfForm').submit();
    };
}
(function() {
    var canvas = document.getElementById('chartPerformance');
    if (!canvas || !window.Chart) return;
    if (canvas._chartInstance) { canvas._chartInstance.destroy(); canvas._chartInstance = null; }
    canvas._chartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels:   @json($performanceChart['labels'] ?? []),
            datasets: @json($performanceChart['datasets'] ?? []),
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                x: { ticks: { font: { size: 10 }, maxTicksLimit: 20 } },
                y: { beginAtZero: true, ticks: { font: { size: 10 } } }
            }
        }
    });
})();
</script>

    </div>{{-- /tab-rapport --}}
