<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queries — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red: #C41230; --red-dark: #8B0D21; --red-lite: #FDECED; }
        body { background: #F1F5F9; }

        .card { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }
        .kpi-card { transition:transform .15s,box-shadow .15s; }
        .kpi-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,.14); }

        /* Badges */
        .badge-crit { background:#FEE2E2; color:#991B1B; font-weight:700; }
        .badge-warn { background:#FEF3C7; color:#92400E; font-weight:700; }
        .badge-info { background:#DBEAFE; color:#1E40AF; font-weight:700; }

        .detail-row { display:none; }
        .detail-row.open { display:table-row; }
        tr.hidden-row { display:none; }

        ::-webkit-scrollbar { width:5px; height:5px; }
        ::-webkit-scrollbar-track { background:#F1F5F9; }
        ::-webkit-scrollbar-thumb { background:#CBD5E1; border-radius:3px; }

        @media print {
            .no-print { display:none !important; }
            body { background:#fff !important; }
            .card { box-shadow:none !important; border:1px solid #e5e7eb; }
            .detail-row { display:table-row !important; }
            tr.hidden-row { display:table-row !important; }
            header { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .badge-crit, .badge-warn, .badge-info { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>

{{-- ══ HEADER ══ --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <img src="/icons/icon.svg" alt="AIRID" class="w-8 h-8">
            </div>
            <div>
                <h1 class="text-base font-extrabold leading-tight tracking-wide">Queries — Contrôle qualité</h1>
                <p class="text-red-200 text-xs mt-0.5">AIRID · ENDURE-Net · District de Dassa-Zoumè</p>
            </div>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Tableau de bord
            </a>
            <form method="POST" action="{{ route('queries.refresh') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Actualiser
                </button>
            </form>
            <button onclick="downloadPdf()"
                    class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Télécharger PDF
            </button>
        </div>
    </div>
</header>

{{-- ══ MAIN ══ --}}
<main class="max-w-screen-2xl mx-auto px-6 py-8 space-y-6">

    {{-- ── KPI CARDS ── --}}
    <section class="grid grid-cols-2 sm:grid-cols-4 gap-5">
        <div class="kpi-card card p-5" style="border-top:4px solid #64748B">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Total queries</p>
            <p class="text-4xl font-extrabold text-slate-700">{{ $summary['total'] }}</p>
            <p class="text-xs text-gray-400 mt-1">incohérences détectées</p>
        </div>
        <div class="kpi-card card p-5" style="border-top:4px solid #C41230">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Critiques</p>
            <p class="text-4xl font-extrabold" style="color:#C41230">{{ $summary['critical'] }}</p>
            <p class="text-xs text-gray-400 mt-1">nécessitent correction urgente</p>
        </div>
        <div class="kpi-card card p-5" style="border-top:4px solid #D97706">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Avertissements</p>
            <p class="text-4xl font-extrabold text-amber-600">{{ $summary['warning'] }}</p>
            <p class="text-xs text-gray-400 mt-1">à vérifier</p>
        </div>
        <div class="kpi-card card p-5" style="border-top:4px solid #2563EB">
            <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Informatifs</p>
            <p class="text-4xl font-extrabold text-blue-700">{{ $summary['info'] }}</p>
            <p class="text-xs text-gray-400 mt-1">points d'attention</p>
        </div>
    </section>

    {{-- ── FILTERS ── --}}
    <section class="card p-5 no-print">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Filtres</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

            {{-- Recherche libre --}}
            <div class="lg:col-span-2">
                <label class="text-xs text-gray-400 block mb-1">Recherche libre</label>
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input id="f-search" type="text" placeholder="Code, intitulé, ménage..."
                           oninput="filterQueries()"
                           class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent"
                           style="--tw-ring-color:var(--red)">
                </div>
            </div>

            {{-- Sévérité --}}
            <div>
                <label class="text-xs text-gray-400 block mb-1">Sévérité</label>
                <div class="ms-wrap relative" id="ms-severity">
                    <button type="button" onclick="toggleMs('severity',event)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left flex items-center justify-between gap-1 focus:outline-none focus:ring-2"
                            style="--tw-ring-color:var(--red)">
                        <span id="ms-severity-lbl" class="text-gray-400 truncate text-xs">Toutes</span>
                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="ms-severity-dd" class="ms-dd hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                        @foreach (['critical' => 'Critique', 'warning' => 'Avertissement', 'info' => 'Informatif'] as $val => $lbl)
                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" value="{{ $val }}" class="ms-cb-severity rounded" onchange="onMsChange('severity')">
                            <span class="text-sm text-gray-700">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Formulaire --}}
            <div>
                <label class="text-xs text-gray-400 block mb-1">Formulaire</label>
                <div class="ms-wrap relative" id="ms-form">
                    <button type="button" onclick="toggleMs('form',event)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left flex items-center justify-between gap-1 focus:outline-none focus:ring-2"
                            style="--tw-ring-color:var(--red)">
                        <span id="ms-form-lbl" class="text-gray-400 truncate text-xs">Tous</span>
                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="ms-form-dd" class="ms-dd hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                        @foreach ($filterForms as $f)
                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" value="{{ $f }}" class="ms-cb-form rounded" onchange="onMsChange('form')">
                            <span class="text-xs text-gray-700 leading-tight">{{ $f }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Tablette --}}
            <div>
                <label class="text-xs text-gray-400 block mb-1">Tablette</label>
                <div class="ms-wrap relative" id="ms-tablet">
                    <button type="button" onclick="toggleMs('tablet',event)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left flex items-center justify-between gap-1 focus:outline-none focus:ring-2"
                            style="--tw-ring-color:var(--red)">
                        <span id="ms-tablet-lbl" class="text-gray-400 truncate text-xs">Toutes</span>
                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="ms-tablet-dd" class="ms-dd hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                        @foreach ($filterTablets as $t)
                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" value="{{ $t }}" class="ms-cb-tablet rounded" onchange="onMsChange('tablet')">
                            <span class="text-sm text-gray-700">{{ $t }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Initiales --}}
            <div>
                <label class="text-xs text-gray-400 block mb-1">Initiales</label>
                <div class="ms-wrap relative" id="ms-initials">
                    <button type="button" onclick="toggleMs('initials',event)"
                            class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white text-left flex items-center justify-between gap-1 focus:outline-none focus:ring-2"
                            style="--tw-ring-color:var(--red)">
                        <span id="ms-initials-lbl" class="text-gray-400 truncate text-xs">Toutes</span>
                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="ms-initials-dd" class="ms-dd hidden absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                        @foreach ($filterInitials as $i)
                        <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" value="{{ $i }}" class="ms-cb-initials rounded" onchange="onMsChange('initials')">
                            <span class="text-sm text-gray-700">{{ $i }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ID Ménage --}}
            <div>
                <label class="text-xs text-gray-400 block mb-1">ID Ménage</label>
                <input id="f-hh" type="text" placeholder="ex: DJ-02-G2..."
                       oninput="filterQueries()"
                       class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2"
                       style="--tw-ring-color:var(--red)">
            </div>

        </div>
        <div class="mt-3 flex items-center gap-3">
            <button onclick="resetFilters()"
                    class="text-xs text-gray-500 hover:text-red-700 underline transition">
                Réinitialiser les filtres
            </button>
            <span id="filter-count" class="text-xs text-gray-400"></span>
        </div>
    </section>

    {{-- ── TABLE ── --}}
    <section class="card overflow-hidden">
        @if ($queries->isEmpty())
            <div class="py-20 text-center">
                <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 font-medium">Aucune incohérence détectée</p>
                <p class="text-gray-400 text-sm mt-1">Toutes les données passent les contrôles de cohérence.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="queriesTable">
                <thead>
                    <tr class="text-xs text-gray-400 uppercase border-b text-left" style="background:#FDF4F5">
                        <th class="py-3 px-3 w-8 no-print">
                            <input type="checkbox" id="cb-all" checked
                                   title="Tout sélectionner / désélectionner"
                                   onchange="toggleAllCheckboxes(this.checked)"
                                   class="rounded border-gray-300 cursor-pointer accent-red-700">
                        </th>
                        <th class="py-3 px-3 w-6"></th>
                        <th class="py-3 px-3">Code</th>
                        <th class="py-3 px-3">Sévérité</th>
                        <th class="py-3 px-3">Intitulé</th>
                        <th class="py-3 px-3">Ménage</th>
                        <th class="py-3 px-3">Tablette</th>
                        <th class="py-3 px-3">Initiales</th>
                        <th class="py-3 px-3">Formulaire</th>
                        <th class="py-3 px-3">Événement</th>
                        <th class="py-3 px-3">Instance</th>
                        <th class="py-3 px-3">Champ</th>
                        <th class="py-3 px-3">Valeur observée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="queriesBody">
                    @foreach ($queries as $q)
                    @php
                        $sevClass = match($q['severity']) {
                            'critical' => 'badge-crit',
                            'warning'  => 'badge-warn',
                            default    => 'badge-info',
                        };
                        $sevLabel = match($q['severity']) {
                            'critical' => 'Critique',
                            'warning'  => 'Avertissement',
                            default    => 'Informatif',
                        };
                        $idx = $q['_idx'];
                    @endphp
                    <tr class="query-row hover:bg-gray-50 transition-colors cursor-pointer"
                        data-severity="{{ $q['severity'] }}"
                        data-form="{{ $q['form_label'] }}"
                        data-tablet="{{ $q['tablet'] }}"
                        data-initials="{{ $q['initials'] }}"
                        data-hh="{{ $q['household_id'] }}"
                        data-search="{{ strtolower($q['code'] . ' ' . $q['title'] . ' ' . $q['household_id'] . ' ' . $q['field'] . ' ' . $q['value']) }}"
                        onclick="toggleDetail({{ $idx }})">
                        <td class="py-2.5 px-3 no-print" onclick="event.stopPropagation()">
                            <input type="checkbox" class="query-cb rounded border-gray-300 cursor-pointer accent-red-700"
                                   value="{{ $idx }}" checked
                                   onchange="updateSelectionCount()">
                        </td>
                        <td class="py-2.5 px-3 text-gray-300">
                            <svg id="chevron-{{ $idx }}" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="font-mono text-xs font-bold" style="color:var(--red)">{{ $q['code'] }}</span>
                        </td>
                        <td class="py-2.5 px-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $sevClass }}">{{ $sevLabel }}</span>
                        </td>
                        <td class="py-2.5 px-3 font-medium text-gray-800 max-w-xs">{{ $q['title'] }}</td>
                        <td class="py-2.5 px-3 font-mono text-xs text-gray-600">{{ $q['household_id'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-gray-600">{{ $q['tablet'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-gray-600">{{ $q['initials'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-gray-600 max-w-[180px]">{{ $q['form_label'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-gray-600 whitespace-nowrap">{{ $q['event_label'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-center text-gray-500">{{ $q['instance'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 font-mono text-xs text-gray-700">{{ $q['field'] ?: '—' }}</td>
                        <td class="py-2.5 px-3 text-xs text-gray-700 max-w-[200px] truncate" title="{{ $q['value'] }}">{{ $q['value'] ?: '—' }}</td>
                    </tr>
                    <tr class="detail-row" id="qdetail-{{ $idx }}">
                        <td colspan="13" class="px-6 pb-5 pt-0">
                            <div class="rounded-xl p-5 mt-1 space-y-3" style="background:#F9FAFB;border-left:3px solid var(--red)">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Description</p>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $q['description'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Suggestion de résolution</p>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $q['suggestion'] }}</p>
                                </div>
                                @if ($q['extra'])
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Contexte additionnel</p>
                                    <p class="text-sm font-mono text-gray-600">{{ $q['extra'] }}</p>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-2 no-print">
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span><span id="visible-count">{{ $queries->count() }}</span> / {{ $queries->count() }} affichées</span>
                <span class="text-gray-300">·</span>
                <span id="selection-count" class="font-semibold" style="color:var(--red)"></span>
                <button onclick="toggleAllCheckboxes(true)"
                        class="text-gray-500 hover:text-red-700 underline transition">Tout sélectionner</button>
                <button onclick="toggleAllCheckboxes(false)"
                        class="text-gray-500 hover:text-red-700 underline transition">Tout désélectionner</button>
            </div>
            <button onclick="expandAll()"
                    class="text-xs text-gray-500 hover:text-red-700 underline transition">
                Tout déplier (impression)
            </button>
        </div>
        @endif
    </section>

    {{-- ── FOOTER ── --}}
    <footer class="text-center text-xs text-gray-400 pb-6">
        AIRID · ENDURE-Net · REDCap #{{ config('redcap.project_id') }} · Généré le {{ now()->format('d/m/Y à H:i') }}
    </footer>

</main>

<script>
// ── Multi-select helpers ─────────────────────────────────────────────────────

const MS_LABELS = {
    severity: { critical: 'Critique', warning: 'Avertissement', info: 'Informatif' },
};
const MS_EMPTY = { severity: 'Toutes', form: 'Tous', tablet: 'Toutes', initials: 'Toutes' };

function getMsValues(key) {
    return [...document.querySelectorAll(`.ms-cb-${key}:checked`)].map(cb => cb.value);
}

function toggleMs(key, e) {
    e.stopPropagation();
    const dd = document.getElementById(`ms-${key}-dd`);
    const isHidden = dd.classList.contains('hidden');
    // close all
    document.querySelectorAll('.ms-dd').forEach(el => el.classList.add('hidden'));
    if (isHidden) dd.classList.remove('hidden');
}

function onMsChange(key) {
    const vals = getMsValues(key);
    const lbl  = document.getElementById(`ms-${key}-lbl`);
    if (vals.length === 0) {
        lbl.textContent = MS_EMPTY[key] || 'Tous';
        lbl.classList.add('text-gray-400');
        lbl.classList.remove('text-gray-800', 'font-semibold');
    } else {
        const mapped = vals.map(v => (MS_LABELS[key] && MS_LABELS[key][v]) ? MS_LABELS[key][v] : v);
        lbl.textContent = mapped.length === 1 ? mapped[0] : mapped.length + ' sélectionnés';
        lbl.classList.remove('text-gray-400');
        lbl.classList.add('text-gray-800', 'font-semibold');
    }
    filterQueries();
}

// Close dropdowns on outside click
document.addEventListener('click', () => {
    document.querySelectorAll('.ms-dd').forEach(el => el.classList.add('hidden'));
});

// ── Checkbox selection helpers ────────────────────────────────────────────────

function toggleAllCheckboxes(checked) {
    document.querySelectorAll('.query-row:not(.hidden-row) .query-cb').forEach(cb => {
        cb.checked = checked;
    });
    updateSelectionCount();
}

function updateSelectionCount() {
    const visibleRows = document.querySelectorAll('.query-row:not(.hidden-row)');
    const checkedCbs  = document.querySelectorAll('.query-row:not(.hidden-row) .query-cb:checked');
    const total   = visibleRows.length;
    const checked = checkedCbs.length;

    const el = document.getElementById('selection-count');
    if (el) {
        el.textContent = checked === total
            ? (total + ' sélectionné(s)')
            : (checked + ' / ' + total + ' sélectionné(s)');
    }

    const cbAll = document.getElementById('cb-all');
    if (cbAll) {
        cbAll.indeterminate = checked > 0 && checked < total;
        cbAll.checked = total > 0 && checked === total;
    }
}

// ── PDF download ─────────────────────────────────────────────────────────────

function downloadPdf() {
    const params = new URLSearchParams();

    // Send the explicit selection (checked + visible rows only)
    document.querySelectorAll('.query-row:not(.hidden-row) .query-cb:checked').forEach(cb => {
        params.append('selected[]', cb.value);
    });

    // Also pass active filters for PDF header display
    const search = document.getElementById('f-search').value;
    const hh     = document.getElementById('f-hh').value;
    if (search) params.set('search', search);
    if (hh)     params.set('hh', hh);
    ['severity', 'form', 'tablet', 'initials'].forEach(key => {
        getMsValues(key).forEach(v => params.append(key + '[]', v));
    });

    window.open('{{ route('queries.pdf') }}?' + params.toString(), '_blank');
}

// ── Table detail toggle ──────────────────────────────────────────────────────

function toggleDetail(idx) {
    const row = document.getElementById('qdetail-' + idx);
    const chevron = document.getElementById('chevron-' + idx);
    if (!row) return;
    const isOpen = row.classList.toggle('open');
    chevron.style.transform = isOpen ? 'rotate(90deg)' : '';
}

function expandAll() {
    document.querySelectorAll('.detail-row').forEach(r => r.classList.add('open'));
    document.querySelectorAll('[id^="chevron-"]').forEach(c => c.style.transform = 'rotate(90deg)');
}

// ── Filter logic ─────────────────────────────────────────────────────────────

function filterQueries() {
    const search   = document.getElementById('f-search').value.toLowerCase();
    const hh       = document.getElementById('f-hh').value.toLowerCase();
    const severity = getMsValues('severity');
    const form     = getMsValues('form');
    const tablet   = getMsValues('tablet');
    const initials = getMsValues('initials');

    let visible = 0;
    document.querySelectorAll('.query-row').forEach(row => {
        const show =
            (!search   || row.dataset.search.includes(search)) &&
            (!hh       || row.dataset.hh.toLowerCase().includes(hh)) &&
            (!severity.length || severity.includes(row.dataset.severity)) &&
            (!form.length     || form.includes(row.dataset.form)) &&
            (!tablet.length   || tablet.includes(row.dataset.tablet)) &&
            (!initials.length || initials.includes(row.dataset.initials));

        row.classList.toggle('hidden-row', !show);
        const idx = row.getAttribute('onclick').match(/\d+/)?.[0];
        if (idx) {
            const detail = document.getElementById('qdetail-' + idx);
            if (detail) detail.classList.toggle('hidden-row', !show);
        }
        if (show) visible++;
    });

    document.getElementById('visible-count').textContent = visible;
    document.getElementById('filter-count').textContent =
        visible < {{ $queries->count() }} ? visible + ' résultat(s) correspondant aux filtres' : '';
    updateSelectionCount();
}

function resetFilters() {
    ['f-search', 'f-hh'].forEach(id => document.getElementById(id).value = '');
    document.querySelectorAll('.ms-cb-severity, .ms-cb-form, .ms-cb-tablet, .ms-cb-initials')
        .forEach(cb => cb.checked = false);
    ['severity', 'form', 'tablet', 'initials'].forEach(key => {
        const lbl = document.getElementById(`ms-${key}-lbl`);
        lbl.textContent = MS_EMPTY[key] || 'Tous';
        lbl.classList.add('text-gray-400');
        lbl.classList.remove('text-gray-800', 'font-semibold');
    });
    filterQueries();
}

// Init selection count on load
updateSelectionCount();
</script>

</body>
</html>
