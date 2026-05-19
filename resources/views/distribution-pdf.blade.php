<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@page {
    size: A4 portrait;
    margin: 1.5cm 1.5cm 1.5cm 1.5cm;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #111;
    background: #fff;
    line-height: 1.5;
}

h1 { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
.meta { font-size: 9px; color: #555; margin-bottom: 3px; }
.doc-hr { border: none; border-top: 1.5px solid #111; margin: 8px 0 12px 0; }

h2 {
    font-size: 11px;
    font-weight: bold;
    border-left: 3px solid #C41230;
    padding-left: 6px;
    margin: 16px 0 6px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9.5px;
    margin-bottom: 6px;
}
table thead th {
    background: #1A1A1A;
    color: #fff;
    font-weight: bold;
    padding: 4px 8px;
    text-align: left;
    font-size: 9px;
    letter-spacing: 0.03em;
}
table thead th.num { text-align: right; }
table tbody td {
    padding: 3px 8px;
    border-bottom: 1px solid #E2E8F0;
    vertical-align: top;
}
table tbody td.num { text-align: right; font-weight: bold; }
table tbody tr:nth-child(even) td { background: #F8FAFC; }
.row-first td { border-top: 1px solid #94A3B8; }
.row-total td {
    background: #C41230 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 9.5px;
}
.row-total td.num { text-align: right; }
</style>
</head>
<body>

<h1>Distribution des moustiquaires par binôme — ENDURE-Net</h1>
<p class="meta">Exporté le {{ now()->format('d/m/Y à H:i') }}</p>
<p class="meta">
    Période :
    @if ($dateFrom || $dateTo)
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'début' }}
        →
        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : "aujourd'hui" }}
    @else
        toutes les dates
    @endif
    &nbsp;·&nbsp;
    Tablettes : {{ !empty($tablets) ? implode(', ', $tablets) : 'toutes' }}
</p>
<hr class="doc-hr">

@if ($netsByTablet->isEmpty())
    <p style="color:#6B7280;font-size:10px">Aucune donnée sur la période sélectionnée.</p>
@else

@foreach ($netsByTablet as $tabletGroup)
<h2>Tablette {{ $tabletGroup['tablet'] }}
    <span style="font-weight:normal;font-size:9.5px;color:#4B5563">
        — {{ $tabletGroup['visited'] }} visité(s) · {{ $tabletGroup['consented'] }} enrôlé(s) · {{ $tabletGroup['total'] }} moustiquaire(s)
    </span>
</h2>
<table>
    <thead>
        <tr>
            <th style="width:42%">Code Ménage</th>
            <th style="width:38%">Code Moustiquaire</th>
            <th class="num" style="width:20%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tabletGroup['households'] as $hh)
            @foreach ($hh['nets'] as $ni => $net)
            <tr class="{{ $ni === 0 ? 'row-first' : '' }}">
                <td>{{ $ni === 0 ? $hh['household_id'] : '' }}</td>
                <td>{{ $net }}</td>
                <td class="num">{{ $ni === 0 ? $hh['total'] : '' }}</td>
            </tr>
            @endforeach
        @endforeach
        <tr class="row-total">
            <td colspan="2">Total Tablette {{ $tabletGroup['tablet'] }}</td>
            <td class="num">{{ $tabletGroup['total'] }}</td>
        </tr>
    </tbody>
</table>
@endforeach

@endif

</body>
</html>
