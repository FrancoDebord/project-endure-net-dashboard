<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cohorte B — Ménages sélectionnés — Suivi {{ $suiviLabel }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; color: #1A1A1A; margin: 0; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 0 0 6px; }
        .sub { color: #64748B; font-size: 9px; margin-bottom: 8px; }
        .arm { page-break-after: always; }
        .arm:last-child { page-break-after: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #CBD5E1; padding: 4px 5px; vertical-align: top; }
        th { background: #F1F5F9; font-size: 8.5px; text-transform: uppercase; text-align: left; }
        .c { text-align: center; } .mono { font-family: monospace; }
        .fill { width: 70px; }
        .toolbar { padding: 10px; background: #F1F5F9; margin-bottom: 10px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">🖨 Imprimer / Enregistrer en PDF</button></div>
@forelse ($arms as $a)
    @php
        $s = $a['state'];
        $rows = collect($s['rows'])->when($onlyPending, fn($c) => $c->where('state', 'pending'), fn($c) => $c->where('state', '!=', 'released'))
            ->when($tablet !== '', fn($c) => $c->filter(fn($r) => \App\Services\CohortHouseholds::tablet($r['id']) === $tablet))
            ->when($village !== '', fn($c) => $c->where('village', $village));
        if (($tablet !== '' || $village !== '') && $rows->isEmpty()) continue;
    @endphp
    <div class="arm">
        <h1>ENDURE-Net · Cohorte B · Suivi {{ $suiviLabel }} — prélèvement des moustiquaires</h1>
        <h2 style="color:{{ $a['color'] }}">{{ $a['label'] }}@if ($village !== '') — {{ $village }}@endif @if ($tablet !== '') — Tablette {{ $tablet }}@endif</h2>
        <p class="sub">
            Cible : {{ $s['target'] }} moustiquaires · prélevées : {{ $s['withdrawn'] }} · reste à prélever : {{ $s['plannedNets'] }}
            · {{ $onlyPending ? 'ménages restant à visiter' : 'tous les ménages sélectionnés' }} · visiter dans l'ordre indiqué et prélever toutes les moustiquaires de l'étude de chaque ménage (« réserve » : uniquement si un ménage précédent n'a pas pu fournir sa part)
            · graine(s) : {{ collect($s['draws'])->whereNull('resetAt')->map(fn($d) => ($d['batch'] === 1 ? 'initial ' : 'compl. ' . ($d['batch'] - 1) . ' ') . $d['seed'])->implode(', ') ?: '—' }}
            · imprimé le {{ now()->format('d/m/Y H:i') }}
        </p>
        <table>
            <thead>
                <tr>
                    <th class="c">Ordre</th><th>Ménage</th><th>Village / grappe</th><th>DAG</th><th>Chef de ménage</th><th>Contact</th><th>Membres du ménage (baseline) : nom (sexe, âge)</th><th>Adresse</th>
                    <th class="c">À prélever</th><th>Moustiquaires disponibles (n° : code)</th><th class="fill">Prélevées</th><th class="fill">Codes remplacement</th><th class="fill">Observations</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="c"><b>{{ $r['rank'] }}</b>@if ($r['reserve'])<br><i>réserve</i>@endif</td>
                    <td class="mono"><b>{{ $r['id'] }}</b></td>
                    <td>{{ $r['village'] ?? '' }}<br>{{ $r['cluster'] ?? '' }}</td>
                    <td>{{ $r['dagLabel'] ?? '' }}</td>
                    <td>{{ $r['chef'] ?? '' }}<br>{{ $r['chefPhone'] ?? '' }}</td>
                    <td>{{ $r['contact'] ?? '' }}<br>{{ $r['contactPhone'] ?? '' }}</td>
                    <td>@include('partials.hh-members', ['list' => $members[$r['id']] ?? []])</td>
                    <td>{{ $r['address'] ?? '' }}</td>
                    <td class="c">@if ($r['state'] === 'pending')<b style="font-size:12px">{{ $r['reserve'] ? '(' . $r['netsAvailable'] . ')' : $r['toTake'] }}</b> @else — @endif</td>
                    <td class="mono">@foreach ($r['availableCodes'] ?? [] as $n => $c){{ $n }} : {{ $c ?: '—' }}<br>@endforeach</td>
                    <td>@if ($r['state'] === 'visited') {{ $r['tp']['withdrawn'] }} @endif</td>
                    <td></td><td></td>
                </tr>
            @empty
                <tr><td colspan="13" class="c">Aucun ménage.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@empty
    <p>Aucune sélection pour ce suivi.</p>
@endforelse
</body>
</html>
