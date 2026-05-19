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
    margin: 16px 0 2px 0;
}
.tablet-stats {
    font-size: 9px;
    color: #4B5563;
    padding-left: 9px;
    margin-bottom: 6px;
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
}
table tbody td.num { text-align: right; }
table tbody tr:nth-child(even) td { background: #F8FAFC; }
.row-subtotal td {
    background: #374151 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 9.5px;
}
.row-subtotal td.num { text-align: right; }
.row-grand td {
    background: #C41230 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 10px;
}
.row-grand td.num { text-align: right; }

.legend {
    font-size: 8px;
    color: #6B7280;
    margin-top: 4px;
    margin-bottom: 12px;
}
</style>
</head>
<body>

<h1>Résumé de la distribution des moustiquaires — ENDURE-Net</h1>
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

@if ($summary->isEmpty())
    <p style="color:#6B7280;font-size:10px">Aucune donnée sur la période sélectionnée.</p>
@else

@foreach ($summary as $tabletGroup)

<h2>Tablette {{ $tabletGroup['tablet'] }}</h2>
<p class="tablet-stats">
    {{ $tabletGroup['visited'] }} ménage(s) visité(s)
    &nbsp;·&nbsp; {{ $tabletGroup['consented'] }} ménage(s) enrôlé(s)
    &nbsp;·&nbsp; {{ $tabletGroup['total_hh'] }} ménage(s) ayant reçu des moustiquaires
</p>

<table>
    <thead>
        <tr>
            <th style="width:28%">Date</th>
            <th class="num" style="width:18%">Ménages</th>
            <th class="num" style="width:27%">Distribuées</th>
            <th class="num" style="width:27%">Marquées</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tabletGroup['byDate'] as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
            <td class="num">{{ $row['hh_count'] }}</td>
            <td class="num">{{ $row['distributed'] }}</td>
            <td class="num">{{ $row['marked'] }}</td>
        </tr>
        @endforeach
        <tr class="row-subtotal">
            <td>Total Tablette {{ $tabletGroup['tablet'] }}</td>
            <td class="num">{{ $tabletGroup['total_hh'] }}</td>
            <td class="num">{{ $tabletGroup['total_distributed'] }}</td>
            <td class="num">{{ $tabletGroup['total_marked'] }}</td>
        </tr>
    </tbody>
</table>
<p class="legend">Distribuées = total moustiquaires enregistrées · Marquées = moustiquaires avec code identifiant saisi</p>

@endforeach

<table style="margin-top:10px">
    <tbody>
        <tr class="row-grand">
            <td style="width:28%">TOTAL GÉNÉRAL</td>
            <td class="num" style="width:18%">{{ $grandHh }}</td>
            <td class="num" style="width:27%">{{ $grandDistributed }}</td>
            <td class="num" style="width:27%">{{ $grandMarked }}</td>
        </tr>
    </tbody>
</table>

@if ($byBrasCohortNets->count())
<h2 style="margin-top:20px">Synthèse par bras et par cohorte</h2>
<table>
    <thead>
        <tr>
            <th style="width:46%">Bras / Cohorte</th>
            <th class="num" style="width:27%">Distribuées</th>
            <th class="num" style="width:27%">Marquées</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($byBrasCohortNets as $b)
        <tr style="background:#F1F5F9;font-weight:bold">
            <td>{{ $b['bras'] }}</td>
            <td class="num">{{ $b['nets'] }}</td>
            <td class="num">{{ $b['marked'] }}</td>
        </tr>
        @foreach ($b['cohorts'] as $c)
        <tr>
            <td style="padding-left:18px">{{ $c['cohort'] }}</td>
            <td class="num">{{ $c['nets'] }}</td>
            <td class="num">{{ $c['marked'] }}</td>
        </tr>
        @endforeach
        @endforeach
        <tr class="row-grand">
            <td>TOTAL GÉNÉRAL</td>
            <td class="num">{{ $grandDistributed }}</td>
            <td class="num">{{ $grandMarked }}</td>
        </tr>
    </tbody>
</table>
@endif

@endif

</body>
</html>
