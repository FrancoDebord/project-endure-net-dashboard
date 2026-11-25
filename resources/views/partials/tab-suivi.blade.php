    <div id="tab-suivi" class="tab-panel">
@php
    $fuB  = $followUpB ?? [];
    $pctB = fn($n, $d) => $d > 0 ? round($n / $d * 100, 1) : 0;
@endphp

        {{-- ── En-tête ─────────────────────────────────────────────────────── --}}
        <section class="card p-5 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <p class="section-title mb-1">Suivi Cohorte B — Prélèvement et remplacement des moustiquaires</p>
                    <p class="text-xs text-gray-500">
                        Suivis à 6, 12, 18, 24 et 36 mois · <b>Form 10 uniquement</b>, une instance par moustiquaire de l'étude.
                        Analyses : contenu chimique (M6, M12, M18, M24, M36) · caractéristiques physiques, bioessais, semi-terrain (M12, M24, M36).
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <a href="{{ route('cb.selection.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap border-2"
                       style="border-color:var(--red);color:var(--red)">
                        🎲 Sélection des ménages (30 moustiq./bras)
                    </a>
                    <a href="{{ route('suivi-b.menages') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white whitespace-nowrap"
                       style="background:var(--red)">
                        Suivi des ménages &amp; listes PDF par DAG →
                    </a>
                </div>
            </div>
        </section>

        {{-- ── Vue d'ensemble ─────────────────────────────────────────────── --}}
        <section class="card overflow-hidden mb-6">
            <div class="px-5 pt-4 pb-2"><p class="section-title">Vue d'ensemble par suivi</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2.5 px-4 text-left">Suivi</th>
                            <th class="py-2.5 px-3 text-center">Ménages attendus</th>
                            <th class="py-2.5 px-3 text-center">Visités</th>
                            <th class="py-2.5 px-3 text-center">Complets</th>
                            <th class="py-2.5 px-3 text-center">Moustiq. prélevées (Form 10)</th>
                            <th class="py-2.5 px-3 text-center">Vérifiées étude</th>
                            <th class="py-2.5 px-3 text-center">Remplacées</th>
                            <th class="py-2.5 px-3 text-center">Utilisées nuit dern.</th>
                            <th class="py-2.5 px-3 text-center">Cumul prélevées</th>
                            <th class="py-2.5 px-3 text-center">Alertes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($fuB as $tp)
                        @php $s = $tp['summary']; $n = $s['nets']; @endphp
                        <tr class="hov-row">
                            <td class="py-2.5 px-4 font-semibold text-gray-700">{{ $tp['label'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $s['hhExpected'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $s['hhVisited'] }} <span class="text-xs text-gray-400">({{ $pctB($s['hhVisited'], $s['hhExpected']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center font-semibold text-green-700">{{ $s['hhDone'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $n['assessed'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $n['verified'] }} <span class="text-xs text-gray-400">({{ $pctB($n['verified'], $n['assessed']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['replaced'] }} <span class="text-xs text-gray-400">({{ $pctB($n['replaced'], $n['verified']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['usedLastNight'] }} <span class="text-xs text-gray-400">({{ $pctB($n['usedLastNight'], $n['verified']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $tp['cumulativeWithdrawn'] }}</td>
                            <td class="py-2.5 px-3 text-center">
                                @if (count($tp['alerts']))
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700">{{ count($tp['alerts']) }}</span>
                                @else
                                    <span class="text-xs text-gray-300">0</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="px-5 py-2 text-[11px] text-gray-400 italic border-t border-gray-50">
                « Vérifiées étude » = Q01 Oui (moustiquaire reçue dans l'étude et de la bonne marque).
                « Remplacées » = code de la moustiquaire de remplacement saisi. « Cumul » = codes distincts prélevés depuis le premier suivi.
            </p>
        </section>

        {{-- ── Détail par suivi ───────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden suivi-bloc">
            <div class="flex items-center overflow-x-auto border-b border-gray-100 px-4 gap-0">
                @foreach ($fuB as $k => $tp)
                    <button class="suivi-tp-btn {{ $loop->first ? 'active' : '' }}" onclick="showSuiviTab('b-{{ $k }}', this)">
                        {{ $tp['label'] }}
                        @if ($tp['summary']['hhVisited'] > 0)
                            <span class="ml-1 text-[10px] px-1.5 rounded-full bg-slate-100 text-slate-700">{{ $tp['summary']['hhVisited'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            @foreach ($fuB as $k => $tp)
                <div id="suivitp-b-{{ $k }}" class="suivi-tp-panel {{ $loop->first ? 'active' : '' }} p-6 space-y-6">
                    @include('partials.suivi-b-timepoint', ['d' => $tp, 'tk' => 'b_' . $k])
                </div>
            @endforeach
        </div>

<script>
if (typeof showSuiviTab === 'undefined') {
    window.showSuiviTab = function(key, btn) {
        const bloc = btn.closest('.suivi-bloc');
        if (!bloc) return;
        bloc.querySelectorAll('.suivi-tp-panel').forEach(p => p.classList.remove('active'));
        bloc.querySelectorAll('.suivi-tp-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('suivitp-' + key)?.classList.add('active');
        btn.classList.add('active');
    };
}
window.filterFubTable = function(tk) {
    const q  = (document.getElementById('fubSearch-' + tk)?.value || '').toLowerCase();
    const st = document.getElementById('fubStatus-' + tk)?.value || '';
    const ar = document.getElementById('fubArm-' + tk)?.value || '';
    let shown = 0;
    document.querySelectorAll('#fubTable-' + tk + ' tbody tr').forEach(row => {
        const ok = row.textContent.toLowerCase().includes(q) && (!st || row.dataset.status === st) && (!ar || row.dataset.arm === ar);
        row.style.display = ok ? '' : 'none';
        if (ok) shown++;
    });
    const c = document.getElementById('fubCount-' + tk);
    if (c) c.textContent = shown;
};
window.filterFubNets = function(input, tk) {
    const q = input.value.toLowerCase();
    document.querySelectorAll('#fubNets-' + tk + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
};
</script>

    </div>{{-- /tab-suivi --}}
