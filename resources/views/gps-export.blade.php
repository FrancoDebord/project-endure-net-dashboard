<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export GPS — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red: #C41230; --red-dark: #8B0D21; }
        body { background: #F8F9FB; font-family: system-ui, sans-serif; }

        .cb-item { display: none; }
        .cb-item:checked + label { background:#FDF4F5; border-color:#C41230; color:#C41230; font-weight:600; }
        .cb-item:checked + label .dot { background:#C41230; }
        .chip-label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; padding:6px 12px;
                      border:1px solid #E2E8F0; border-radius:8px; background:#fff; font-size:0.8rem;
                      color:#374151; transition:all .15s; user-select:none; }
        .chip-label:hover { border-color:#C41230; background:#FDF4F5; }
        .dot { width:8px; height:8px; border-radius:50%; background:#D1D5DB; flex-shrink:0; transition:background .15s; }
        .village-head { font-size:0.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
                        color:#9CA3AF; margin-bottom:6px; }

        /* Groupement radio tabs */
        .grp-radio { display:none; }
        .grp-label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; padding:7px 16px;
                     border:1px solid #E2E8F0; border-radius:8px; background:#fff; font-size:0.82rem;
                     color:#374151; transition:all .15s; font-weight:500; }
        .grp-label:hover { border-color:#C41230; }
        .grp-radio:checked + .grp-label { background:#FDF4F5; border-color:#C41230; color:#C41230; font-weight:700; }

        .grp-section { display:none; }
        .grp-section.active { display:block; }
    </style>
</head>
<body class="min-h-screen">

{{-- HEADER --}}
<header style="background:linear-gradient(135deg,#8B0D21 0%,#C41230 60%,#D91B35 100%)"
        class="text-white shadow-lg">
    <div class="max-w-2xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="bg-white rounded-lg p-1.5 flex-shrink-0">
                <img src="/icons/icon.svg" alt="AIRID" class="w-7 h-7">
            </div>
            <div>
                <h1 class="text-sm font-extrabold leading-tight tracking-wide">Export GPS — OsmAnd</h1>
                <p class="text-red-200 text-xs mt-0.5">ENDURE-Net · Fichiers GPX par grappe, tablette ou DAG</p>
            </div>
        </div>
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-1.5 text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg border border-white/30 transition font-medium">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Dashboard
        </a>
    </div>
</header>

<main class="max-w-2xl mx-auto px-6 py-8">

    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('gps.export.download') }}">
        @csrf

        {{-- ── Regroupement ────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
            <h2 class="font-bold text-gray-800 text-sm mb-3">Regroupement des fichiers GPX</h2>
            <div class="flex flex-wrap gap-2">
                <div>
                    <input type="radio" name="group_by" value="cluster"
                           id="grp_cluster" class="grp-radio" checked onchange="switchGroup('cluster')">
                    <label for="grp_cluster" class="grp-label">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        Par Grappe
                    </label>
                </div>
                <div>
                    <input type="radio" name="group_by" value="village"
                           id="grp_village" class="grp-radio" onchange="switchGroup('village')">
                    <label for="grp_village" class="grp-label">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Par Village
                    </label>
                </div>
                <div>
                    <input type="radio" name="group_by" value="tablet"
                           id="grp_tablet" class="grp-radio" onchange="switchGroup('tablet')">
                    <label for="grp_tablet" class="grp-label">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Par Tablette
                    </label>
                </div>
                <div>
                    <input type="radio" name="group_by" value="dag"
                           id="grp_dag" class="grp-radio" onchange="switchGroup('dag')">
                    <label for="grp_dag" class="grp-label">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Par DAG
                    </label>
                </div>
            </div>
            <p id="grp_hint" class="text-xs text-gray-400 mt-2">Un fichier GPX par grappe — plusieurs grappes → archive ZIP</p>
        </div>

        {{-- ── Sélection ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-800 text-sm" id="sel_title">Grappes à exporter</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="setAll(true)"
                            class="text-xs text-red-700 hover:underline font-medium">Tout cocher</button>
                    <span class="text-gray-300">·</span>
                    <button type="button" onclick="setAll(false)"
                            class="text-xs text-gray-500 hover:underline">Tout décocher</button>
                </div>
            </div>

            {{-- Section Grappe (défaut) --}}
            <div id="section_cluster" class="grp-section active">
                <div class="mb-4">
                    <p class="village-head">Djigbe</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['1' => 'Grappe 1', '2' => 'Grappe 2'] as $val => $lbl)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $val }}"
                                       id="cl{{ $val }}" class="cb-item" checked>
                                <label for="cl{{ $val }}" class="chip-label">
                                    <span class="dot"></span>{{ $lbl }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-4">
                    <p class="village-head">Gbonou</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['3' => 'Grappe 1', '4' => 'Grappe 2', '5' => 'Grappe 3'] as $val => $lbl)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $val }}"
                                       id="cl{{ $val }}" class="cb-item" checked>
                                <label for="cl{{ $val }}" class="chip-label">
                                    <span class="dot"></span>{{ $lbl }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="village-head">Miniffi</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['6' => 'Grappe 1', '7' => 'Grappe 2', '8' => 'Grappe 3', '9' => 'Grappe 4'] as $val => $lbl)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $val }}"
                                       id="cl{{ $val }}" class="cb-item" checked>
                                <label for="cl{{ $val }}" class="chip-label">
                                    <span class="dot"></span>{{ $lbl }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mb-4">
                    <p class="village-head">Dogbo</p>
                    <div class="flex flex-wrap gap-2">
                        <div>
                            <input type="checkbox" name="selection[]" value="10"
                                   id="cl10" class="cb-item" checked>
                            <label for="cl10" class="chip-label">
                                <span class="dot"></span>Grappe 1
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="village-head">Agao</p>
                    <div class="flex flex-wrap gap-2">
                        <div>
                            <input type="checkbox" name="selection[]" value="11"
                                   id="cl11" class="cb-item" checked>
                            <label for="cl11" class="chip-label">
                                <span class="dot"></span>Grappe 1
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <p class="village-head">Akoba</p>
                    <div class="flex flex-wrap gap-2">
                        <div>
                            <input type="checkbox" name="selection[]" value="12"
                                   id="cl12" class="cb-item" checked>
                            <label for="cl12" class="chip-label">
                                <span class="dot"></span>Grappe 1
                            </label>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="village-head">Agbondjedo</p>
                    <div class="flex flex-wrap gap-2">
                        <div>
                            <input type="checkbox" name="selection[]" value="13"
                                   id="cl13" class="cb-item" checked>
                            <label for="cl13" class="chip-label">
                                <span class="dot"></span>Grappe 2
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Village --}}
            <div id="section_village" class="grp-section">
                @if (!empty($villages))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($villages as $vil)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $vil }}"
                                       id="vil_{{ $vil }}" class="cb-item" checked>
                                <label for="vil_{{ $vil }}" class="chip-label">
                                    <span class="dot"></span>
                                    {{ ['DJ'=>'Djigbe','GB'=>'Gbonou','MI'=>'Miniffi','DO'=>'Dogbo','AG'=>'Agao','AK'=>'Akoba','AD'=>'Agbondjedo','1'=>'Djigbe','2'=>'Gbonou','3'=>'Miniffi'][$vil] ?? $vil }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">
                        Aucun village détecté dans les données REDCap
                        (<code class="bg-gray-100 px-1 rounded text-xs">village</code>).
                    </p>
                @endif
            </div>

            {{-- Section Tablette --}}
            <div id="section_tablet" class="grp-section">
                @if (!empty($tablets))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tablets as $tab)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $tab }}"
                                       id="tab_{{ $tab }}" class="cb-item" checked>
                                <label for="tab_{{ $tab }}" class="chip-label">
                                    <span class="dot"></span>{{ $tab }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">
                        Aucune tablette détectée dans les identifiants ménage.
                        <br><span class="text-xs">Format attendu : VILLAGE-GRAPPE-BRAS-<strong>TABLETTE</strong>-MENAGE</span>
                    </p>
                @endif
            </div>

            {{-- Section DAG --}}
            <div id="section_dag" class="grp-section">
                @if (!empty($dags))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($dags as $dag)
                            <div>
                                <input type="checkbox" name="selection[]" value="{{ $dag }}"
                                       id="dag_{{ Str::slug($dag) }}" class="cb-item" checked>
                                <label for="dag_{{ Str::slug($dag) }}" class="chip-label">
                                    <span class="dot"></span>{{ $dag }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">
                        Aucun DAG configuré dans ce projet REDCap, ou le champ
                        <code class="bg-gray-100 px-1 rounded text-xs">redcap_data_access_group</code>
                        n'est pas renseigné.
                    </p>
                @endif
            </div>
        </div>

        {{-- ── Options ──────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
            <h2 class="font-bold text-gray-800 text-sm mb-4">Options</h2>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cohorte</label>
                <div class="flex flex-wrap gap-4">
                    @foreach (['' => 'Toutes', '1' => 'Cohorte A', '2' => 'Cohorte B'] as $val => $lbl)
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                            <input type="radio" name="cohorts[]" value="{{ $val }}"
                                   {{ $val === '' ? 'checked' : '' }}
                                   class="accent-red-700">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Ménages</label>
                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                        <input type="radio" name="consent_only" value="1" checked class="accent-red-700">
                        Consentis uniquement <span class="text-xs text-gray-400">(recommandé)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                        <input type="radio" name="consent_only" value="0" class="accent-red-700">
                        Tous les ménages avec GPS
                    </label>
                </div>
            </div>
        </div>

        {{-- Info --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 mb-6 text-sm text-blue-700">
            <p class="font-semibold mb-1">Informations incluses dans chaque point GPX</p>
            <ul class="list-disc list-inside text-xs space-y-0.5 text-blue-600">
                <li>Identifiant ménage (nom du waypoint)</li>
                <li>Nom du chef de ménage</li>
                <li>Cohorte de l'étude</li>
                <li>Village, adresse et tablette</li>
            </ul>
            <p class="text-xs mt-2 text-blue-500" id="info_hint">
                Un fichier GPX par grappe sélectionnée. Plusieurs sélections → archive ZIP.
            </p>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-3 px-6 rounded-xl font-bold text-sm text-white transition"
                style="background:var(--red)" onmouseover="this.style.background='#8B0D21'" onmouseout="this.style.background='#C41230'">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Générer et télécharger les fichiers GPX
        </button>
    </form>
</main>

<script>
const HINTS = {
    cluster: 'Un fichier GPX par grappe — plusieurs grappes → archive ZIP.',
    village: 'Un fichier GPX par village — plusieurs villages → archive ZIP.',
    tablet:  'Un fichier GPX par tablette — plusieurs tablettes → archive ZIP.',
    dag:     'Un fichier GPX par DAG — plusieurs DAG → archive ZIP.',
};
const TITLES = {
    cluster: 'Grappes à exporter',
    village: 'Villages à exporter',
    tablet:  'Tablettes à exporter',
    dag:     'DAG à exporter',
};

function switchGroup(val) {
    ['cluster', 'village', 'tablet', 'dag'].forEach(g => {
        document.getElementById('section_' + g).classList.toggle('active', g === val);
    });
    document.getElementById('grp_hint').textContent  = HINTS[val]  ?? '';
    document.getElementById('info_hint').textContent = HINTS[val]  ?? '';
    document.getElementById('sel_title').textContent = TITLES[val] ?? 'Sélection';
}

function setAll(checked) {
    const active = document.querySelector('.grp-section.active');
    if (active) active.querySelectorAll('.cb-item').forEach(cb => cb.checked = checked);
}
</script>
</body>
</html>
