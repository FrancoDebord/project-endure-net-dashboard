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
        .cb-cluster { display: none; }
        .cb-cluster:checked + label { background:#FDF4F5; border-color:#C41230; color:#C41230; font-weight:600; }
        .cb-cluster:checked + label .dot { background:#C41230; }
        .chip-label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; padding:6px 12px;
                      border:1px solid #E2E8F0; border-radius:8px; background:#fff; font-size:0.8rem;
                      color:#374151; transition:all .15s; user-select:none; }
        .chip-label:hover { border-color:#C41230; background:#FDF4F5; }
        .dot { width:8px; height:8px; border-radius:50%; background:#D1D5DB; flex-shrink:0; transition:background .15s; }
        .village-head { font-size:0.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
                        color:#9CA3AF; margin-bottom:6px; }
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
                <p class="text-red-200 text-xs mt-0.5">ENDURE-Net · Fichiers GPX par village &amp; grappe</p>
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

    {{-- Alerte erreur --}}
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

        {{-- Card: Sélection des grappes --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-800 text-sm">Grappes à exporter</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="setAll(true)"
                            class="text-xs text-red-700 hover:underline font-medium">Tout cocher</button>
                    <span class="text-gray-300">·</span>
                    <button type="button" onclick="setAll(false)"
                            class="text-xs text-gray-500 hover:underline">Tout décocher</button>
                </div>
            </div>

            {{-- Djigbe --}}
            <div class="mb-4">
                <p class="village-head">Djigbe</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['1' => 'Grappe 1', '2' => 'Grappe 2'] as $val => $lbl)
                        <div>
                            <input type="checkbox" name="clusters[]" value="{{ $val }}"
                                   id="cl{{ $val }}" class="cb-cluster" checked>
                            <label for="cl{{ $val }}" class="chip-label">
                                <span class="dot"></span>{{ $lbl }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Gbonou --}}
            <div class="mb-4">
                <p class="village-head">Gbonou</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['3' => 'Grappe 1', '4' => 'Grappe 2', '5' => 'Grappe 3'] as $val => $lbl)
                        <div>
                            <input type="checkbox" name="clusters[]" value="{{ $val }}"
                                   id="cl{{ $val }}" class="cb-cluster" checked>
                            <label for="cl{{ $val }}" class="chip-label">
                                <span class="dot"></span>{{ $lbl }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Miniffi --}}
            <div>
                <p class="village-head">Miniffi</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['6' => 'Grappe 1', '7' => 'Grappe 2', '8' => 'Grappe 3'] as $val => $lbl)
                        <div>
                            <input type="checkbox" name="clusters[]" value="{{ $val }}"
                                   id="cl{{ $val }}" class="cb-cluster" checked>
                            <label for="cl{{ $val }}" class="chip-label">
                                <span class="dot"></span>{{ $lbl }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Card: Options --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
            <h2 class="font-bold text-gray-800 text-sm mb-4">Options</h2>

            {{-- Cohorte --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cohorte</label>
                <div class="flex flex-wrap gap-2">
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

            {{-- Consentement --}}
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

        {{-- Card: Informations exportées --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 mb-6 text-sm text-blue-700">
            <p class="font-semibold mb-1">Informations incluses dans chaque point GPX</p>
            <ul class="list-disc list-inside text-xs space-y-0.5 text-blue-600">
                <li>Identifiant ménage (nom du waypoint)</li>
                <li>Nom du chef de ménage (<code>b_hhh_fullname</code>)</li>
                <li>Cohorte de l'étude (<code>study_cohort</code>)</li>
                <li>Village et adresse (<code>b_hh_address</code>)</li>
            </ul>
            <p class="text-xs mt-2 text-blue-500">
                Un fichier GPX par grappe. Si plusieurs grappes sont sélectionnées, les fichiers sont groupés dans une archive ZIP.
            </p>
        </div>

        {{-- Bouton --}}
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
function setAll(checked) {
    document.querySelectorAll('.cb-cluster').forEach(cb => cb.checked = checked);
}
</script>
</body>
</html>
