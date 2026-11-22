    <div id="tab-suivi_a" class="tab-panel">
@php
    $fuA = $followUpA ?? [];
    $pctA = fn($n, $d) => $d > 0 ? round($n / $d * 100, 1) : 0;
@endphp

        {{-- ── En-tête : formulaires de la Cohorte A ─────────────────────────── --}}
        <section class="card p-5 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <p class="section-title mb-1">Suivi Cohorte A — Attrition et intégrité des moustiquaires</p>
                    <p class="text-xs text-gray-500">Suivis à 6, 12, 24 et 36 mois. Formulaires remplis dans chaque ménage de la Cohorte A :</p>
                </div>
                <a href="{{ route('suivi-a.menages') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white whitespace-nowrap shrink-0"
                   style="background:var(--red)">
                    Suivi des ménages &amp; listes PDF par DAG →
                </a>
            </div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mt-3">
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['Visite Ménage', 'Questionnaire Base', 'Membres du ménage', 'Avant Suivi', 'Form 7 · Attrition & intégrité', 'Form 8 · Autres moustiquaires ?', 'Form 9 · Autres moustiquaires'] as $f)
                        <span class="text-[11px] font-medium px-2 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200">{{ $f }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ── Vue d'ensemble longitudinale ─────────────────────────────────── --}}
        <section class="card overflow-hidden mb-6">
            <div class="px-5 pt-4 pb-2">
                <p class="section-title">Vue d'ensemble par suivi</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-400 uppercase border-b bg-gray-50">
                            <th class="py-2.5 px-4 text-left">Suivi</th>
                            <th class="py-2.5 px-3 text-center">Ménages attendus</th>
                            <th class="py-2.5 px-3 text-center">Visités</th>
                            <th class="py-2.5 px-3 text-center">Suivi réalisé</th>
                            <th class="py-2.5 px-3 text-center">Moustiq. évaluées</th>
                            <th class="py-2.5 px-3 text-center">Présentes</th>
                            <th class="py-2.5 px-3 text-center">Suspendues</th>
                            <th class="py-2.5 px-3 text-center">Utilisées nuit dern.</th>
                            <th class="py-2.5 px-3 text-center">pHI bon / accept. / déchiré</th>
                            <th class="py-2.5 px-3 text-center">Alertes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($fuA as $tp)
                        @php $s = $tp['summary']; $n = $s['nets']; @endphp
                        <tr class="hov-row">
                            <td class="py-2.5 px-4 font-semibold text-gray-700">{{ $tp['label'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $s['hhExpected'] }}</td>
                            <td class="py-2.5 px-3 text-center">{{ $s['hhVisited'] }} <span class="text-xs text-gray-400">({{ $pctA($s['hhVisited'], $s['hhExpected']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center font-semibold text-green-700">{{ $s['hhDone'] }} <span class="text-xs text-gray-400 font-normal">({{ $pctA($s['hhDone'], $s['hhExpected']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['assessed'] }} <span class="text-xs text-gray-400">/ {{ $s['netsExpectedDone'] }}</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['present'] }} <span class="text-xs text-gray-400">({{ $pctA($n['present'], $n['assessed']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['hanging'] }} <span class="text-xs text-gray-400">({{ $pctA($n['hanging'], $n['present']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center">{{ $n['usedLastNight'] }} <span class="text-xs text-gray-400">({{ $pctA($n['usedLastNight'], $n['present']) }}%)</span></td>
                            <td class="py-2.5 px-3 text-center text-xs">
                                <span class="text-green-700 font-semibold">{{ $n['phiGood'] }}</span> /
                                <span class="text-amber-600 font-semibold">{{ $n['phiAcceptable'] }}</span> /
                                <span class="text-red-700 font-semibold">{{ $n['phiTorn'] }}</span>
                            </td>
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
                « Moustiq. évaluées » = instances Form 7 / moustiquaires de l'étude distribuées (baseline) aux ménages dont le suivi est réalisé.
                pHI calculé sur les moustiquaires présentes et disponibles : bon ≤ 64, acceptable 65–642, déchiré ≥ 643.
            </p>
        </section>

        {{-- ── Détail par suivi ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden suivi-bloc">
            <div class="flex items-center overflow-x-auto border-b border-gray-100 px-4 gap-0">
                @foreach ($fuA as $k => $tp)
                    <button class="suivi-tp-btn {{ $loop->first ? 'active' : '' }}"
                            onclick="showSuiviTab('a-{{ $k }}', this)">
                        {{ $tp['label'] }}
                        @if ($tp['summary']['hhVisited'] > 0)
                            <span class="ml-1 text-[10px] px-1.5 rounded-full bg-amber-100 text-amber-700">{{ $tp['summary']['hhVisited'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            @foreach ($fuA as $k => $tp)
                <div id="suivitp-a-{{ $k }}" class="suivi-tp-panel {{ $loop->first ? 'active' : '' }} p-6 space-y-6">
                    @include('partials.suivi-a-timepoint', ['d' => $tp, 'tk' => 'a_' . $k])
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
window.filterFuaTable = function(tk) {
    const q  = (document.getElementById('fuaSearch-' + tk)?.value || '').toLowerCase();
    const st = document.getElementById('fuaStatus-' + tk)?.value || '';
    const ar = document.getElementById('fuaArm-' + tk)?.value || '';
    let shown = 0;
    document.querySelectorAll('#fuaTable-' + tk + ' tbody tr').forEach(row => {
        const ok = row.textContent.toLowerCase().includes(q)
            && (!st || row.dataset.status === st)
            && (!ar || row.dataset.arm === ar);
        row.style.display = ok ? '' : 'none';
        if (ok) shown++;
    });
    const c = document.getElementById('fuaCount-' + tk);
    if (c) c.textContent = shown;
};
window.filterFuaNets = function(input, tk) {
    const q = input.value.toLowerCase();
    document.querySelectorAll('#fuaNets-' + tk + ' tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
};
</script>

    </div>{{-- /tab-suivi_a --}}
