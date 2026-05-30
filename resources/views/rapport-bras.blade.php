<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d'exécution par bras — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --pmd:      #7C3AED;
            --pmd-lite: #EDE9FE;
            --pmd-mid:  #A78BFA;
            --ig2:      #0284C7;
            --ig2-lite: #E0F2FE;
            --ig2-mid:  #38BDF8;
            --red:      #C41230;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page-break { page-break-before: always; }
            .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
        }

        body { background: #F1F5F9; font-family: system-ui, sans-serif; }

        .card { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }

        /* Progress bars */
        .prog      { background:#E2E8F0; border-radius:9999px; height:.6rem; overflow:hidden; }
        .prog-fill { border-radius:9999px; height:.6rem; transition: width .5s ease; }
        .prog-pmd  { background: linear-gradient(90deg, var(--pmd-mid), var(--pmd)); }
        .prog-ig2  { background: linear-gradient(90deg, var(--ig2-mid), var(--ig2)); }
        .prog-ok   { background: linear-gradient(90deg, #34D399, #059669); }
        .prog-warn { background: linear-gradient(90deg, #FCD34D, #D97706); }
        .prog-crit { background: linear-gradient(90deg, #FCA5A5, #DC2626); }

        /* Section titles */
        .arm-header-pmd { background: linear-gradient(135deg, #5B21B6, var(--pmd)); }
        .arm-header-ig2 { background: linear-gradient(135deg, #0369A1, var(--ig2)); }

        /* Rate badge */
        .rate-ok   { background:#DCFCE7; color:#15803D; }
        .rate-warn { background:#FEF3C7; color:#92400E; }
        .rate-crit { background:#FEE2E2; color:#991B1B; }

        ::-webkit-scrollbar { width:5px; height:5px; }
        ::-webkit-scrollbar-track { background:#F1F5F9; }
        ::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:3px; }
    </style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50 no-print">
    <div class="max-w-screen-xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5">
                <img src="/icons/icon.svg" alt="AIRID" class="w-8 h-8">
            </div>
            <div>
                <h1 class="text-base font-extrabold tracking-wide">Rapport d'exécution — Par bras</h1>
                <p class="text-red-200 text-xs mt-0.5">AIRID · ENDURE-Net · District de Dassa-Zoumè</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('rapport.bras.pdf') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Télécharger PDF
            </a>
        </div>
    </div>
</header>

{{-- ══ PAGE ══ --}}
<div class="max-w-screen-xl mx-auto px-6 py-8 space-y-8">

    {{-- En-tête du rapport (visible à l'impression) --}}
    <div class="card p-6 flex items-start justify-between gap-6">
        <div class="flex items-center gap-5">
            <img src="/icons/icon.svg" alt="AIRID" class="w-14 h-14 hidden print:block">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">Rapport d'exécution</p>
                <h2 class="text-2xl font-extrabold text-slate-800 leading-tight">Enrôlement & Distribution par bras</h2>
                <p class="text-slate-500 text-sm mt-1">Étude ENDURE-Net · District de Dassa-Zoumè · Bénin</p>
            </div>
        </div>
        <div class="text-right flex-shrink-0">
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-0.5">Généré le</p>
            <p class="text-sm font-bold text-slate-700">{{ now()->format('d/m/Y') }}</p>
            <p class="text-xs text-slate-400">{{ now()->format('H:i') }}</p>
            <div class="mt-3 text-xs text-slate-400 space-y-0.5">
                <p>Cible : <span class="font-semibold text-slate-600">921 ménages/bras</span></p>
                <p>Moy. : <span class="font-semibold text-slate-600">3 moustiquaires/ménage</span></p>
            </div>
        </div>
    </div>

    {{-- ══ KPI GLOBAUX ══ --}}
    @php
        function rateClass(float $r): string {
            if ($r >= 90) return 'rate-ok';
            if ($r >= 70) return 'rate-warn';
            return 'rate-crit';
        }
        function progClass(float $r): string {
            if ($r >= 90) return 'prog-ok';
            if ($r >= 70) return 'prog-warn';
            return 'prog-crit';
        }
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $globalKpis = [
                ['label' => 'Ménages enrôlés',        'val' => $global['hh'],          'target' => $global['target_hh'],   'rate' => $global['rate_hh'],   'unit' => 'ménages'],
                ['label' => 'Moustiquaires distribuées','val' => $global['nets'],        'target' => $global['target_nets'], 'rate' => $global['rate_nets'], 'unit' => 'moustiquaires'],
                ['label' => 'Ménages restants',        'val' => $global['gap_hh'],      'target' => null, 'rate' => null,   'unit' => 'à compléter'],
                ['label' => 'Filets attendus',          'val' => $global['gap_nets'],    'target' => null, 'rate' => null,   'unit' => 'supplémentaires'],
            ];
        @endphp
        @foreach($globalKpis as $kpi)
        <div class="card p-5">
            <p class="text-xs font-700 uppercase tracking-widest text-slate-400 mb-1">{{ $kpi['label'] }}</p>
            <p class="text-3xl font-extrabold {{ $kpi['rate'] !== null ? ($kpi['rate'] >= 90 ? 'text-emerald-600' : ($kpi['rate'] >= 70 ? 'text-amber-500' : 'text-red-600')) : 'text-slate-700' }}">
                {{ number_format($kpi['val']) }}
            </p>
            <p class="text-xs text-slate-400 mt-0.5">{{ $kpi['unit'] }}</p>
            @if($kpi['rate'] !== null)
                <div class="mt-2 prog"><div class="{{ progClass($kpi['rate']) }} prog-fill" style="width:{{ min(100, $kpi['rate']) }}%"></div></div>
                <p class="text-xs mt-1 font-semibold {{ $kpi['rate'] >= 90 ? 'text-emerald-600' : ($kpi['rate'] >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $kpi['rate'] }}% / cible {{ number_format($kpi['target']) }}
                </p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ══ GRILLE PAR BRAS ══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($arms as $brasKey => $arm)
        @php $color = $arm['color']; @endphp

        <div class="card overflow-hidden">

            {{-- En-tête bras --}}
            <div class="arm-header-{{ $color }} text-white px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-1">Bras {{ $brasKey }}</p>
                        <h3 class="text-xl font-extrabold tracking-wide">{{ $arm['label'] }}</h3>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-extrabold">{{ number_format($arm['hh']) }}</p>
                        <p class="text-xs opacity-80 mt-0.5">ménages / {{ number_format($arm['target_hh']) }}</p>
                    </div>
                </div>

                {{-- Barre globale bras --}}
                <div class="mt-4 space-y-1.5">
                    <div class="flex justify-between text-xs opacity-90">
                        <span>Ménages — {{ $arm['rate_hh'] }}%</span>
                        <span>{{ number_format($arm['gap_hh']) }} restants</span>
                    </div>
                    <div class="prog" style="background:rgba(255,255,255,.25)">
                        <div class="prog-fill" style="width:{{ min(100, $arm['rate_hh']) }}%; background:rgba(255,255,255,.85)"></div>
                    </div>
                    <div class="flex justify-between text-xs opacity-90">
                        <span>Moustiquaires — {{ $arm['rate_nets'] }}%</span>
                        <span>{{ number_format($arm['nets']) }} / {{ number_format($arm['target_nets']) }}</span>
                    </div>
                    <div class="prog" style="background:rgba(255,255,255,.25)">
                        <div class="prog-fill" style="width:{{ min(100, $arm['rate_nets']) }}%; background:rgba(255,255,255,.7)"></div>
                    </div>
                </div>
            </div>

            {{-- Cohortes --}}
            <div class="divide-y divide-slate-100">
                @foreach($arm['cohorts'] as $cohortKey => $cohort)
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full" style="background: var(--{{ $color }})"></div>
                            <h4 class="font-extrabold text-slate-700 uppercase tracking-wider text-sm">{{ $cohort['label'] }}</h4>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ rateClass($cohort['rate_hh']) }}">
                            {{ $cohort['rate_hh'] }}%
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        {{-- Ménages --}}
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Ménages</p>
                            <div class="flex items-end gap-2 mb-2">
                                <span class="text-2xl font-extrabold text-slate-800">{{ number_format($cohort['hh']) }}</span>
                                <span class="text-sm text-slate-400 mb-0.5">/ {{ number_format($cohort['target_hh']) }}</span>
                            </div>
                            <div class="prog mb-1.5">
                                <div class="{{ progClass($cohort['rate_hh']) }} prog-fill"
                                     style="width:{{ min(100, $cohort['rate_hh']) }}%"></div>
                            </div>
                            @if($cohort['gap_hh'] > 0)
                                <p class="text-xs text-slate-500 mt-1">
                                    <span class="font-semibold text-red-600">{{ number_format($cohort['gap_hh']) }}</span>
                                    ménages à compléter
                                </p>
                            @else
                                <p class="text-xs text-emerald-600 font-semibold mt-1">Cible atteinte</p>
                            @endif
                        </div>

                        {{-- Moustiquaires --}}
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Moustiquaires</p>
                            <div class="flex items-end gap-2 mb-2">
                                <span class="text-2xl font-extrabold text-slate-800">{{ number_format($cohort['nets']) }}</span>
                                <span class="text-sm text-slate-400 mb-0.5">/ {{ number_format($cohort['target_nets']) }}</span>
                            </div>
                            <div class="prog mb-1.5">
                                <div class="{{ progClass($cohort['rate_nets']) }} prog-fill"
                                     style="width:{{ min(100, $cohort['rate_nets']) }}%"></div>
                            </div>
                            @if($cohort['gap_nets'] > 0)
                                <p class="text-xs text-slate-500 mt-1">
                                    <span class="font-semibold text-red-600">{{ number_format($cohort['gap_nets']) }}</span>
                                    filets attendus
                                </p>
                            @else
                                <p class="text-xs text-emerald-600 font-semibold mt-1">Cible atteinte</p>
                            @endif
                        </div>

                    </div>

                    {{-- Mini-résumé ligne --}}
                    <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                        <span>Taux moustiquaires :
                            <span class="font-bold {{ $cohort['rate_nets'] >= 90 ? 'text-emerald-600' : ($cohort['rate_nets'] >= 70 ? 'text-amber-600' : 'text-red-600') }}">
                                {{ $cohort['rate_nets'] }}%
                            </span>
                        </span>
                        <span class="text-slate-300">|</span>
                        <span>Moy. filets/ménage :
                            <span class="font-bold text-slate-700">
                                {{ $cohort['hh'] > 0 ? number_format($cohort['nets'] / $cohort['hh'], 1) : '—' }}
                            </span>
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pied bras --}}
            <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"
                 style="background: var(--{{ $color }}-lite)">
                <span class="font-semibold" style="color: var(--{{ $color }})">Total {{ $arm['label'] }}</span>
                <span>
                    <span class="font-bold text-slate-700">{{ number_format($arm['hh']) }}</span> ménages ·
                    <span class="font-bold text-slate-700">{{ number_format($arm['nets']) }}</span> moustiquaires ·
                    Moy. <span class="font-bold text-slate-700">{{ $arm['hh'] > 0 ? number_format($arm['nets'] / $arm['hh'], 1) : '—' }}</span>/ménage
                </span>
            </div>

        </div>
        @endforeach
    </div>

    {{-- ══ TABLEAU RÉCAPITULATIF ══ --}}
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100"
             style="background:linear-gradient(135deg,#1E293B,#334155)">
            <h3 class="text-white font-extrabold uppercase tracking-wider text-sm">Tableau récapitulatif — Exécution vs Protocole</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-5 py-3 text-left font-700">Bras</th>
                        <th class="px-5 py-3 text-left font-700">Cohorte</th>
                        <th class="px-4 py-3 text-right font-700">Cible ménages</th>
                        <th class="px-4 py-3 text-right font-700">Enrôlés</th>
                        <th class="px-4 py-3 text-right font-700">Taux</th>
                        <th class="px-4 py-3 text-right font-700">Restants</th>
                        <th class="px-4 py-3 text-right font-700">Cible filets</th>
                        <th class="px-4 py-3 text-right font-700">Distribués</th>
                        <th class="px-4 py-3 text-right font-700">Taux</th>
                        <th class="px-4 py-3 text-right font-700">Manquants</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($arms as $brasKey => $arm)
                        @php $color = $arm['color']; $first = true; @endphp
                        @foreach($arm['cohorts'] as $cohortKey => $cohort)
                        <tr class="hover:bg-slate-50 transition">
                            @if($first)
                            <td class="px-5 py-3 font-bold whitespace-nowrap" rowspan="{{ count($arm['cohorts']) }}"
                                style="border-left: 4px solid var(--{{ $color }}); color: var(--{{ $color }})">
                                {{ $arm['label'] }}
                            </td>
                            @php $first = false; @endphp
                            @endif
                            <td class="px-5 py-3 text-slate-600 font-medium whitespace-nowrap">{{ $cohort['label'] }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-600">{{ number_format($cohort['target_hh']) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">{{ number_format($cohort['hh']) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($cohort['rate_hh']) }}">
                                    {{ $cohort['rate_hh'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $cohort['gap_hh'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $cohort['gap_hh'] > 0 ? number_format($cohort['gap_hh']) : '✓' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-600">{{ number_format($cohort['target_nets']) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">{{ number_format($cohort['nets']) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($cohort['rate_nets']) }}">
                                    {{ $cohort['rate_nets'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $cohort['gap_nets'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $cohort['gap_nets'] > 0 ? number_format($cohort['gap_nets']) : '✓' }}
                            </td>
                        </tr>
                        @endforeach

                        {{-- Sous-total bras --}}
                        <tr style="background: var(--{{ $color }}-lite)">
                            <td class="px-5 py-2.5 font-extrabold text-xs uppercase tracking-wider"
                                style="color: var(--{{ $color }}); border-left: 4px solid var(--{{ $color }})">
                                Sous-total
                            </td>
                            <td class="px-5 py-2.5 font-bold text-slate-600 text-xs">{{ $arm['label'] }}</td>
                            <td class="px-4 py-2.5 text-right font-bold font-mono text-slate-700">{{ number_format($arm['target_hh']) }}</td>
                            <td class="px-4 py-2.5 text-right font-extrabold text-slate-800">{{ number_format($arm['hh']) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($arm['rate_hh']) }}">
                                    {{ $arm['rate_hh'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold {{ $arm['gap_hh'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $arm['gap_hh'] > 0 ? number_format($arm['gap_hh']) : '✓' }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold font-mono text-slate-700">{{ number_format($arm['target_nets']) }}</td>
                            <td class="px-4 py-2.5 text-right font-extrabold text-slate-800">{{ number_format($arm['nets']) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($arm['rate_nets']) }}">
                                    {{ $arm['rate_nets'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-bold {{ $arm['gap_nets'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $arm['gap_nets'] > 0 ? number_format($arm['gap_nets']) : '✓' }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Total global --}}
                    <tr class="bg-slate-800 text-white">
                        <td class="px-5 py-3 font-extrabold text-xs uppercase tracking-wider">TOTAL</td>
                        <td class="px-5 py-3 font-bold text-slate-300 text-xs">Tous bras confondus</td>
                        <td class="px-4 py-3 text-right font-bold font-mono">{{ number_format($global['target_hh']) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold">{{ number_format($global['hh']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($global['rate_hh']) }}">
                                {{ $global['rate_hh'] }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold {{ $global['gap_hh'] > 0 ? 'text-red-300' : 'text-emerald-300' }}">
                            {{ $global['gap_hh'] > 0 ? number_format($global['gap_hh']) : '✓' }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold font-mono">{{ number_format($global['target_nets']) }}</td>
                        <td class="px-4 py-3 text-right font-extrabold">{{ number_format($global['nets']) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold {{ rateClass($global['rate_nets']) }}">
                                {{ $global['rate_nets'] }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold {{ $global['gap_nets'] > 0 ? 'text-red-300' : 'text-emerald-300' }}">
                            {{ $global['gap_nets'] > 0 ? number_format($global['gap_nets']) : '✓' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══ NOTE MÉTHODOLOGIQUE ══ --}}
    <div class="card p-5 border-l-4 text-sm text-slate-600 space-y-1.5" style="border-color:#94A3B8">
        <p class="font-bold text-slate-700 uppercase tracking-wider text-xs mb-2">Note méthodologique</p>
        <p>· <span class="font-semibold">Cible protocole :</span> 921 ménages par bras (611 Cohorte A + 310 Cohorte B), soit 1 842 ménages au total.</p>
        <p>· <span class="font-semibold">Moustiquaires :</span> moyenne de 3 moustiquaires par ménage enrôlé → cible de 2 763 moustiquaires par bras (5 526 au total).</p>
        <p>· <span class="font-semibold">Ménages enrôlés :</span> ménages ayant donné leur consentement éclairé (<code class="bg-slate-100 px-1 rounded text-xs">consent_accepted = 1</code>).</p>
        <p>· <span class="font-semibold">Moustiquaires :</span> comptage des moustiquaires d'étude enregistrées dans REDCap pour chaque ménage.</p>
        <p>· <span class="font-semibold">Taux d'exécution :</span> <span class="text-emerald-600 font-semibold">Vert ≥ 90%</span> · <span class="text-amber-600 font-semibold">Orange ≥ 70%</span> · <span class="text-red-600 font-semibold">Rouge &lt; 70%</span></p>
    </div>

    <p class="text-center text-xs text-slate-400 pb-4">
        ENDURE-Net Dashboard · AIRID · {{ now()->format('d/m/Y à H:i') }} · Données REDCap
    </p>

</div>

</body>
</html>
