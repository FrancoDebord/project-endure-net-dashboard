<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sélection Cohorte B — Prélèvement des moustiquaires | ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red:#C41230; --red-dark:#8B0D21; --dark:#1A1A1A; }
        body { background:#F1F5F9; font-family: system-ui, sans-serif; }
        .card  { background:#fff; border-radius:1rem; box-shadow:0 2px 12px rgba(0,0,0,.09); }
        .section-title { font-size:.78rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
                         color:var(--dark); border-left:4px solid var(--red); padding-left:.65rem; }
        .btn { border-radius:.5rem; padding:.4rem .9rem; font-size:.75rem; font-weight:700;
               display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; transition:background .15s; }
        .btn-primary { background:var(--red); color:#fff; } .btn-primary:hover { background:var(--red-dark); }
        .btn-secondary { background:#fff; color:var(--dark); border:1.5px solid #E2E8F0; } .btn-secondary:hover { background:#F8FAFC; }
        .btn-green { background:#16A34A; color:#fff; } .btn-green:hover { background:#15803D; }
        .btn-amber { background:#D97706; color:#fff; } .btn-amber:hover { background:#B45309; }
        .btn-xs { padding:.2rem .5rem; font-size:.65rem; }
        .inp { border:1.5px solid #E2E8F0; border-radius:.5rem; padding:.35rem .6rem; font-size:.8rem; }
        .tbl { width:100%; border-collapse:collapse; font-size:.75rem; }
        .tbl thead th { background:#F1F5F9; color:#475569; font-size:.65rem; font-weight:700; letter-spacing:.05em;
                        text-transform:uppercase; padding:.55rem .6rem; border-bottom:2px solid #CBD5E1; white-space:nowrap; }
        .tbl tbody tr { border-bottom:1px solid #E2E8F0; }
        .tbl tbody td { padding:.45rem .6rem; vertical-align:middle; }
        .st { border-radius:.35rem; padding:.1rem .45rem; font-size:.65rem; font-weight:700; white-space:nowrap; border:1px solid; }
        .st-visited  { background:#DCFCE7; color:#15803D; border-color:#BBF7D0; }
        .st-pending  { background:#FEF3C7; color:#92400E; border-color:#FDE68A; }
        .st-reserve  { background:#E0F2FE; color:#075985; border-color:#BAE6FD; }
        .st-released { background:#F8FAFC; color:#94A3B8; border-color:#E2E8F0; }
        tr.row-released td { color:#94A3B8; }
        .tp-tab { padding:.6rem 1.1rem; font-size:.8rem; font-weight:700; color:#64748B; border-bottom:3px solid transparent; }
        .tp-tab.active { color:var(--red); border-color:var(--red); }
        .bar { background:#E2E8F0; border-radius:9999px; height:.7rem; overflow:hidden; display:flex; }
        .striped { background-image:repeating-linear-gradient(45deg,rgba(255,255,255,.45) 0 4px,transparent 4px 8px); }
    </style>
</head>
<body>
@php
    $suiviLabel = $suivis[$tp];
    $stateLabels = ['visited' => 'Visité', 'pending' => 'À visiter', 'reserve' => 'Réserve', 'released' => 'Libéré'];
    $noPlanArms = collect($arms)->filter(fn($a) => $a['state'] === null || $a['state']['pendingCount'] + $a['state']['visitedCount'] === 0);
@endphp

<header style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="text-white">
    <div class="max-w-screen-2xl mx-auto px-6 py-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-widest text-white/70">ENDURE-Net · Suivi Cohorte B</p>
            <h1 class="text-lg font-extrabold">Sélection aléatoire des ménages — prélèvement des moustiquaires</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('cb.selection.index', ['suivi' => $tp, 'refresh' => 1]) }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30"
               title="Recharger les Form 10 depuis REDCap pour mettre à jour les moustiquaires prélevées">⟳ Actualiser REDCap</a>
            <a href="{{ route('suivi-b.menages', ['suivi' => $tp]) }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30">Suivi des ménages</a>
            <a href="{{ route('dashboard') }}" class="btn bg-white/15 hover:bg-white/25 border border-white/30">← Dashboard</a>
        </div>
    </div>
</header>

<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

    @foreach (['success' => 'bg-green-50 border-green-200 text-green-800', 'error' => 'bg-red-50 border-red-200 text-red-800'] as $k => $cls)
        @if (session($k))
            <div class="border rounded-xl px-4 py-3 text-sm whitespace-pre-line {{ $cls }}">{{ session($k) }}</div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="border rounded-xl px-4 py-3 text-sm bg-red-50 border-red-200 text-red-800">{{ $errors->first() }}</div>
    @endif

    <nav class="card px-4 flex items-center gap-1 overflow-x-auto">
        @foreach ($suivis as $sk => $sl)
            <a href="{{ route('cb.selection.index', ['suivi' => $sk]) }}" class="tp-tab {{ $tp === $sk ? 'active' : '' }}">
                Suivi {{ $sl }}
                @if (!empty($activeCounts[$sk]))<span class="ml-1 text-[10px] bg-gray-100 text-gray-600 rounded-full px-1.5 py-0.5">{{ $activeCounts[$sk] }} mén.</span>@endif
            </a>
        @endforeach
    </nav>

    <section class="card p-5 text-xs text-gray-600 leading-relaxed">
        <p class="section-title mb-2">Principe</p>
        <ul class="list-disc pl-5 space-y-1">
            <li>Pour chaque bras, des ménages Cohorte B sont <b>tirés au hasard</b> et ajoutés jusqu'à couvrir la cible de moustiquaires. L'ordre de tirage est l'<b>ordre de visite</b>.</li>
            <li>La colonne <b>« À prélever »</b> donne le nombre à prendre dans chaque ménage : <b>toutes ses moustiquaires de l'étude</b> (pas de retrait partiel). Le total peut donc dépasser légèrement la cible. Elle se recalcule d'après les Form 10 saisis : si un ménage fournit moins que prévu, les ménages de réserve sont mobilisés.</li>
            <li>Ménages éligibles : bon bras, au moins une moustiquaire de l'étude non encore prélevée, pas de Form 10 à ce suivi, et pas en attente de visite à un autre suivi. Un ménage dont une moustiquaire n'a pas été trouvée reste tirable aux suivis suivants pour celle-ci.</li>
            <li><b>Reproductible</b> : chaque tirage utilise une graine. L'ordre aléatoire = tri des ménages par SHA-256(« graine:code ménage »). La graine, les paramètres et la liste des ménages éligibles sont enregistrés (journal des tirages, export CSV) : le même tirage peut être recalculé à l'identique, ici ou dans un autre logiciel.</li>
            <li>Les moustiquaires prélevées sont lues dans les <b>Form 10 vérifiés</b> de REDCap (bouton « Actualiser REDCap »).</li>
            <li><b>Compléter</b> : si prélevées + attendues des ménages restants &lt; cible, de nouveaux ménages sont tirés pour combler le déficit.
                <b>Libérer</b> : une fois la cible atteinte, les ménages non visités sont désélectionnés et redeviennent disponibles pour les autres suivis.</li>
        </ul>
    </section>

    {{-- ── Tirage initial ─────────────────────────────────────────── --}}
    @if ($noPlanArms->isNotEmpty())
    <section class="card p-5">
        <p class="section-title mb-3">Nouveau tirage — Suivi {{ $suiviLabel }}</p>
        <form method="POST" action="{{ route('cb.selection.draw') }}" class="flex flex-wrap items-end gap-4"
              onsubmit="return confirm('Lancer le tirage aléatoire pour le suivi {{ $suiviLabel }} ?')">
            @csrf
            <input type="hidden" name="suivi" value="{{ $tp }}">
            <label class="text-xs font-semibold text-gray-600">Bras<br>
                <select name="arm" class="inp mt-1">
                    @if ($noPlanArms->count() === 2)<option value="">Les deux bras</option>@endif
                    @foreach ($noPlanArms as $ak => $a)<option value="{{ $ak }}">{{ $a['label'] }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-semibold text-gray-600">Moustiquaires à prélever par bras<br>
                <input type="number" name="target" value="{{ $defaultTarget }}" min="1" max="500" class="inp mt-1 w-28">
            </label>
            <label class="text-xs font-semibold text-gray-600" title="Ménages supplémentaires tirés au-delà de la cible, au cas où des ménages seraient absents ou des moustiquaires introuvables">
                Ménages de réserve<br>
                <input type="number" name="reserve" value="0" min="0" max="50" class="inp mt-1 w-24">
            </label>
            <label class="text-xs font-semibold text-gray-600 flex items-center gap-2 pb-2">
                <input type="hidden" name="spread" value="0">
                <input type="checkbox" name="spread" value="1" checked> Répartir entre les grappes
            </label>
            <label class="text-xs font-semibold text-gray-600" title="Entier de 1 à 999 999 999. Laisser vide : une graine aléatoire est générée et enregistrée. Saisir une graine permet de rejouer un tirage (ex. graine tirée publiquement).">
                Graine (facultatif)<br>
                <input type="number" name="seed" min="1" max="999999999" placeholder="auto" class="inp mt-1 w-36">
            </label>
            <button class="btn btn-primary">🎲 Tirer au sort</button>
        </form>
        <p class="text-[11px] text-gray-400 mt-3">
            Éligibles :
            @foreach ($noPlanArms as $a) <b>{{ $a['label'] }}</b> {{ $a['poolSize'] }} ménages ({{ $a['poolNets'] }} moustiquaires)@if (!$loop->last) · @endif @endforeach
        </p>
    </section>
    @endif

    {{-- ── Un bloc par bras ────────────────────────────────────────── --}}
    @foreach ($arms as $ak => $a)
        @php
            $s = $a['state'];
            $hasRows = $s && count($s['rows']) > 0;   // plan conservé après réinitialisation, mais sans ménage
            $locked  = $hasRows && $s['lockedAt'];      // tirage définitif : ni réinitialisation ni libération
        @endphp
        <section class="card overflow-hidden" style="border-top:5px solid {{ $a['color'] }}">
            <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100">
                <div>
                    <p class="text-base font-extrabold" style="color:{{ $a['color'] }}">{{ $a['label'] }} — Suivi {{ $suiviLabel }}</p>
                    <p class="text-[11px] text-gray-400">Pool éligible restant : {{ $a['poolSize'] }} ménages · {{ $a['poolNets'] }} moustiquaires</p>
                </div>
                @if ($hasRows)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('cb.selection.print', ['suivi' => $tp, 'arm' => $ak, 'only' => 'pending']) }}" target="_blank" class="btn btn-secondary">🖨 Ménages à visiter</a>
                    <a href="{{ route('cb.selection.print', ['suivi' => $tp, 'arm' => $ak]) }}" target="_blank" class="btn btn-secondary">🖨 Liste complète</a>
                    @php $tabs = collect($s['rows'])->where('state', '!=', 'released')->map(fn($r) => ($r['village'] ?? '') . '|' . \App\Services\CohortHouseholds::tablet($r['id']))->unique()->sort()->values(); @endphp
                    <form method="GET" action="{{ route('cb.selection.print') }}" target="_blank" class="flex items-center gap-1">
                        <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                        <select name="vt" class="inp py-1">
                            @foreach ($tabs as $tb)<option value="{{ $tb }}">{{ str_replace('|', ' — tablette ', $tb) }}</option>@endforeach
                        </select>
                        <button class="btn btn-secondary">🖨 Par tablette</button>
                    </form>
                    @if ($locked)
                    <span class="btn bg-gray-800 text-white cursor-default" title="Verrouillé le {{ $s['lockedAt']->format('d/m/Y H:i') }} par {{ $s['lockedBy'] }}">🔒 Tirage verrouillé</span>
                    @else
                    <form method="POST" action="{{ route('cb.selection.lock') }}"
                          onsubmit="return confirm('Verrouiller DÉFINITIVEMENT le tirage {{ $a['label'] }} du suivi {{ $suiviLabel }} ?

Il ne sera plus possible de réinitialiser la sélection ni de libérer des ménages. Seul l'ajout de ménages (Compléter / Réserve) restera possible.

Cette action ne peut pas être annulée depuis l'application.')">
                        @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                        <button class="btn bg-gray-800 text-white hover:bg-black">🔒 Verrouiller le tirage</button>
                    </form>
                    <form method="POST" action="{{ route('cb.selection.reset') }}"
                          onsubmit="return confirm('Réinitialiser la sélection {{ $a['label'] }} du suivi {{ $suiviLabel }} ? Les ménages sélectionnés seront retirés ; le journal des tirages est conservé (tirages marqués « annulés »).')">
                        @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                        <button class="btn btn-secondary text-red-600">Réinitialiser</button>
                    </form>
                    @endif
                </div>
                @endif
            </div>

            @if (!$hasRows)
                <p class="px-5 pt-6 pb-3 text-sm text-gray-400">
                    {{ $s ? 'Aucune sélection active pour ce bras à ce suivi (sélection réinitialisée).' : 'Aucun tirage pour ce bras à ce suivi.' }}
                </p>
                @if ($s)
                    <div class="px-5 pb-5">@include('partials.cb-draw-journal', ['draws' => $s['draws']])</div>
                @endif
            @else
                @php
                    $w = $s['target'] > 0 ? min(100, $s['withdrawn'] / $s['target'] * 100) : 0;
                    $p = $s['target'] > 0 ? min(100 - $w, $s['plannedNets'] / $s['target'] * 100) : 0;
                @endphp
                <div class="px-5 py-4 grid grid-cols-2 md:grid-cols-6 gap-4 text-center">
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Cible</p><p class="text-2xl font-extrabold">{{ $s['target'] }}</p></div>
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Prélevées</p><p class="text-2xl font-extrabold text-green-700">{{ $s['withdrawn'] }}</p><p class="text-[10px] text-gray-400">{{ $s['visitedCount'] }} mén. visités</p></div>
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Attendues</p><p class="text-2xl font-extrabold text-amber-600">{{ $s['plannedNets'] }}</p><p class="text-[10px] text-gray-400">{{ $s['pendingCount'] - $s['reserveCount'] }} mén. à visiter</p></div>
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Projection</p><p class="text-2xl font-extrabold">{{ $s['projected'] }}</p><p class="text-[10px] text-gray-400">+{{ $s['spareNets'] }} en réserve</p></div>
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Déficit</p><p class="text-2xl font-extrabold {{ $s['deficit'] ? 'text-red-600' : 'text-gray-300' }}">{{ $s['deficit'] }}</p></div>
                    <div><p class="text-[10px] uppercase text-gray-400 tracking-widest">Libérés</p><p class="text-2xl font-extrabold text-gray-400">{{ $s['releasedCount'] }}</p></div>
                </div>
                <div class="px-5 pb-4">
                    <div class="bar"><div style="width:{{ $w }}%;background:#16A34A"></div><div class="striped" style="width:{{ $p }}%;background:#F59E0B"></div></div>
                    <p class="text-[10px] text-gray-400 mt-1">Vert : prélevées · hachuré : attendues des ménages à visiter</p>
                </div>

                {{-- Actions selon l'état --}}
                <div class="px-5 pb-4 space-y-2">
                    @if ($locked)
                        <div class="rounded-lg bg-gray-100 border border-gray-300 px-4 py-2 text-xs text-gray-700">
                            🔒 Tirage <b>verrouillé</b> le {{ $s['lockedAt']->format('d/m/Y H:i') }} par {{ $s['lockedBy'] }} :
                            la sélection ne peut plus être réinitialisée et aucun ménage ne peut être libéré. Seul l'ajout de ménages (compléter / réserve) reste possible.
                        </div>
                    @endif
                    @if ($s['reached'])
                        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-green-800"><b>Cible atteinte</b> ({{ $s['withdrawn'] }} / {{ $s['target'] }}).
                                @if ($s['pendingCount'] && !$locked) {{ $s['pendingCount'] }} ménage(s) non visité(s) peuvent être libérés pour les autres suivis. @endif</p>
                            @if ($s['pendingCount'] && !$locked)
                            <form method="POST" action="{{ route('cb.selection.release') }}" onsubmit="return confirm('Libérer les {{ $s['pendingCount'] }} ménage(s) non visité(s) ?')">
                                @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                                <button class="btn btn-green">Libérer les ménages restants</button>
                            </form>
                            @endif
                        </div>
                    @elseif ($s['deficit'] > 0)
                        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-red-800"><b>Déficit de {{ $s['deficit'] }} moustiquaire(s)</b> : prélevées ({{ $s['withdrawn'] }}) + disponibles dans les ménages à visiter ({{ $s['plannedNets'] + $s['spareNets'] }}) &lt; cible ({{ $s['target'] }}).</p>
                            <form method="POST" action="{{ route('cb.selection.complete') }}" class="flex items-center gap-2"
                                  onsubmit="return confirm('Tirer de nouveaux ménages pour combler le déficit ?')">
                                @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                                <label class="text-[11px] text-red-800">Réserve <input type="number" name="reserve" value="0" min="0" max="50" class="inp w-16 py-1"></label>
                                <input type="number" name="seed" min="1" max="999999999" placeholder="Graine (auto)" class="inp w-32 py-1" title="Laisser vide pour une graine aléatoire">
                                <button class="btn btn-primary">🎲 Compléter la sélection</button>
                            </form>
                        </div>
                    @else
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-amber-800">Collecte en cours : les ménages à visiter devraient permettre d'atteindre la cible
                                @if ($s['reserveCount']) ({{ $s['reserveCount'] }} ménage(s) en réserve) @endif.</p>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('cb.selection.complete') }}" class="flex items-center gap-2"
                                      onsubmit="return confirm('Ajouter des ménages de réserve ?')">
                                    @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                                    <input type="number" name="reserve" value="1" min="1" max="50" class="inp w-16 py-1">
                                    <input type="number" name="seed" min="1" max="999999999" placeholder="Graine (auto)" class="inp w-32 py-1" title="Laisser vide pour une graine aléatoire">
                                    <button class="btn btn-secondary">+ Réserve</button>
                                </form>
                                @if ($s['pendingCount'] && !$locked)
                                <form method="POST" action="{{ route('cb.selection.release') }}"
                                      onsubmit="return confirm('La cible n\'est pas encore atteinte. Libérer quand même les {{ $s['pendingCount'] }} ménage(s) non visité(s) ?')">
                                    @csrf <input type="hidden" name="suivi" value="{{ $tp }}"><input type="hidden" name="arm" value="{{ $ak }}">
                                    <button class="btn btn-secondary">Libérer les non visités</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if (count($s['offSelection']))
                        <div class="rounded-lg bg-orange-50 border border-orange-200 px-4 py-2 text-xs text-orange-800">
                            ⚠ {{ count($s['offSelection']) }} ménage(s) de ce bras ont un Form 10 à ce suivi sans être sélectionnés (non comptés dans la cible) :
                            {{ collect($s['offSelection'])->map(fn($o) => $o['id'] . ' (' . $o['withdrawn'] . ')')->implode(', ') }}
                        </div>
                    @endif

                    @include('partials.cb-draw-journal', ['draws' => $s['draws']])
                </div>

                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Ordre</th><th class="text-left">Ménage</th><th class="text-left">Village / grappe</th><th class="text-left">DAG</th>
                                <th class="text-left">Chef de ménage</th><th class="text-left">Contact</th>
                                <th title="Nombre à prélever / moustiquaires disponibles dans le ménage">À prélever</th><th class="text-left">Codes disponibles</th><th>Prélevées</th>
                                <th>Statut</th><th>Tirage</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($s['rows'] as $r)
                            @php $stKey = $r['state'] === 'pending' && $r['reserve'] ? 'reserve' : $r['state']; @endphp
                            <tr class="{{ $r['state'] === 'released' ? 'row-released' : '' }}">
                                <td class="text-center font-bold">{{ $r['rank'] }}</td>
                                <td class="font-mono font-semibold whitespace-nowrap">{{ $r['id'] }}</td>
                                <td>{{ $r['village'] ?? '' }}<br><span class="text-[10px] text-gray-400">{{ $r['cluster'] ?? '' }}</span></td>
                                <td class="text-[11px]">{{ $r['dagLabel'] ?? '' }}</td>
                                <td>{{ $r['chef'] ?? '' }}<br><span class="text-[10px] text-gray-400">{{ $r['chefPhone'] ?? '' }}</span></td>
                                <td>{{ $r['contact'] ?? '' }}<br><span class="text-[10px] text-gray-400">{{ $r['contactPhone'] ?? '' }}</span></td>
                                <td class="text-center whitespace-nowrap">
                                    @if ($r['state'] === 'pending')
                                        <b>{{ $r['toTake'] ?: '—' }}</b>@if ($r['reserve'])<span class="text-gray-400"> ({{ $r['netsAvailable'] }} si mobilisé)</span>@endif
                                    @else
                                        <span class="text-gray-300">{{ $r['netsAvailable'] }}</span>
                                    @endif
                                </td>
                                <td class="font-mono text-[10px]">
                                    @foreach ($r['availableCodes'] ?? [] as $n => $c){{ $n }}: {{ $c ?: '—' }}@if (!$loop->last)<br>@endif @endforeach
                                </td>
                                <td class="text-center">
                                    @if ($r['state'] === 'visited')
                                        <b class="text-green-700">{{ $r['tp']['withdrawn'] }}</b><span class="text-gray-400"> / {{ $r['tp']['assessed'] }} Form 10</span>
                                        <br><span class="text-[10px] text-gray-400">{{ $r['tp']['date'] }} {{ $r['tp']['agent'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="st st-{{ $stKey }}" @if ($r['releaseReason']) title="{{ $r['releaseReason'] }} — {{ $r['releasedAt']?->format('d/m/Y H:i') }}" @endif>{{ $stateLabels[$stKey] }}</span>
                                </td>
                                <td class="text-center text-[11px] text-gray-500">{{ $r['batch'] === 1 ? 'Initial' : 'Compl. ' . ($r['batch'] - 1) }}</td>
                                <td class="text-right">
                                    @if ($r['state'] === 'pending' && !$locked)
                                    <form method="POST" action="{{ route('cb.selection.release-one', $r['rowId']) }}"
                                          onsubmit="return confirm('Libérer le ménage {{ $r['id'] }} ?')">
                                        @csrf <button class="btn btn-secondary btn-xs">Libérer</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
</main>
</body>
</html>
