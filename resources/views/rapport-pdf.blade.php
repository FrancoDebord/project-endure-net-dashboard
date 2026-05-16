<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@page {
    margin: 1.8cm 1.8cm 1.8cm 1.8cm;
    size: A4 portrait;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    color: #111;
    background: #fff;
    line-height: 1.5;
}

/* ── Doc header ── */
h1 {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 4px;
}
.meta {
    font-size: 8.5px;
    color: #555;
    margin-bottom: 2px;
}
.doc-hr {
    border: none;
    border-top: 1.5px solid #111;
    margin: 8px 0 12px 0;
}

/* ── Section headings ── */
h2 {
    font-size: 11px;
    font-weight: bold;
    border-left: 3px solid #C41230;
    padding-left: 6px;
    margin: 14px 0 6px 0;
    color: #111;
}
h3 {
    font-size: 9.5px;
    font-weight: bold;
    background: #F1F5F9;
    padding: 3px 6px;
    margin: 10px 0 4px 0;
    border-radius: 2px;
}
.tablet-label {
    font-size: 9px;
    font-weight: bold;
    color: #374151;
    margin: 5px 0 2px 8px;
}

/* ── Tables ── */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
    font-size: 8.5px;
}
table thead tr th {
    background: #1A1A1A;
    color: #fff;
    font-weight: bold;
    padding: 3px 6px;
    text-align: left;
    font-size: 8px;
    letter-spacing: 0.03em;
}
table tbody tr td {
    padding: 2.5px 6px;
    border-bottom: 1px solid #E2E8F0;
    vertical-align: top;
}
table tbody tr:last-child td {
    border-bottom: none;
}
table tbody tr:nth-child(even) td {
    background: #F8FAFC;
}
.row-subtotal td {
    background: #EEF2FF !important;
    font-weight: bold;
    font-size: 8px;
    color: #3730A3;
    border-top: 1px solid #C7D2FE;
    border-bottom: 1px solid #C7D2FE;
}
.row-day-total td {
    background: #FEF3C7 !important;
    font-weight: bold;
    font-size: 8px;
    color: #92400E;
    border-top: 1px solid #FDE68A;
}
.row-period-total td {
    background: #C41230 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 9px;
}
.num { text-align: right; }

/* ── Summary tables ── */
.summary-section {
    margin-top: 14px;
    page-break-inside: avoid;
}
.row-bras td {
    background: #F1F5F9 !important;
    font-weight: bold;
    font-size: 8.5px;
    color: #1E293B;
}
.row-cohort td {
    padding-left: 18px !important;
    font-size: 8.5px;
}
.row-grand-total td {
    background: #C41230 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 9px;
}
</style>
</head>
<body>

{{-- ── Document header ── --}}
<h1>Rapport d'activités — ENDURE-Net</h1>
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
    Tablettes / Binômes : {{ !empty($tablets) ? implode(', ', $tablets) : 'tous' }}
</p>
<hr class="doc-hr">

{{-- ══════════════════════════════════════════════════ --}}
{{-- SECTION 1 : Activité quotidienne                   --}}
{{-- ══════════════════════════════════════════════════ --}}
<h2>1. Activité quotidienne par binôme</h2>

@if (empty($daily))
    <p style="color:#6B7280;font-size:8.5px;margin:6px 0">Aucune activité sur la période sélectionnée.</p>
@else
    @foreach ($daily as $day)
        <h3>{{ \Carbon\Carbon::parse($day['date'])->isoFormat('dddd D MMMM YYYY') }}</h3>

        <table>
            <thead>
                <tr>
                    <th>Tablette / Binôme</th>
                    <th class="num">Ménages visités</th>
                    <th class="num">Ménages enrôlés</th>
                    <th class="num">Moustiquaires</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($day['tablets'] as $td)
                    <tr>
                        <td>Tablette {{ $td['tablet'] }}</td>
                        <td class="num">{{ $td['visited'] }}</td>
                        <td class="num">{{ $td['enrolled'] }}</td>
                        <td class="num">{{ $td['nets'] }}</td>
                    </tr>
                @endforeach
                <tr class="row-day-total">
                    <td>Total du {{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                    <td class="num">{{ $day['visited'] }}</td>
                    <td class="num">{{ $day['enrolled'] }}</td>
                    <td class="num">{{ $day['nets'] }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    {{-- Period summary --}}
    <table style="margin-top:12px">
        <thead>
            <tr>
                <th>TOTAL DE LA PÉRIODE</th>
                <th class="num">Ménages visités</th>
                <th class="num">Ménages enrôlés</th>
                <th class="num">Moustiquaires</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-period-total">
                <td>{{ count($daily) }} jour(s) d'activité</td>
                <td class="num">{{ $periodVisited }}</td>
                <td class="num">{{ $periodEnrolled }}</td>
                <td class="num">{{ $periodNets }}</td>
            </tr>
        </tbody>
    </table>
@endif

<hr class="doc-hr" style="margin-top:16px">

{{-- ══════════════════════════════════════════════════ --}}
{{-- SECTION 2 : Résumé cumulatif                       --}}
{{-- ══════════════════════════════════════════════════ --}}
<h2>2. Résumé cumulatif au {{ now()->format('d/m/Y') }}</h2>

{{-- 2a: Par bras × cohorte --}}
<div class="summary-section">
<h3>Par bras et cohorte</h3>
<table>
    <thead>
        <tr>
            <th>Bras / Cohorte</th>
            <th class="num">Ménages enrôlés</th>
            <th class="num">Moustiquaires distribuées</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($byBrasCohort as $b)
            <tr class="row-bras">
                <td>{{ $b['bras'] }}</td>
                <td class="num">{{ $b['total'] }}</td>
                <td class="num">{{ $b['nets'] }}</td>
            </tr>
            @foreach ($b['cohorts'] as $c)
                <tr class="row-cohort">
                    <td>{{ $c['cohort'] }}</td>
                    <td class="num">{{ $c['total'] }}</td>
                    <td class="num">{{ $c['nets'] }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="row-grand-total">
            <td>TOTAL GÉNÉRAL</td>
            <td class="num">{{ $totalEnrolled }}</td>
            <td class="num">{{ $totalNets }}</td>
        </tr>
    </tbody>
</table>
</div>

{{-- 2b: Par village --}}
<div class="summary-section">
<h3>Par village</h3>
<table>
    <thead>
        <tr>
            <th>Village</th>
            <th class="num">Ménages enrôlés</th>
            <th class="num">Moustiquaires distribuées</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($byVillage as $v)
            <tr>
                <td>{{ $v['village'] }}</td>
                <td class="num">{{ $v['total'] }}</td>
                <td class="num">{{ $v['nets'] }}</td>
            </tr>
        @endforeach
        <tr class="row-grand-total">
            <td>TOTAL</td>
            <td class="num">{{ $totalEnrolled }}</td>
            <td class="num">{{ $totalNets }}</td>
        </tr>
    </tbody>
</table>
</div>

{{-- 2c: Par grappe --}}
<div class="summary-section">
<h3>Par grappe</h3>
<table>
    <thead>
        <tr>
            <th>Village / Grappe</th>
            <th class="num">Ménages enrôlés</th>
            <th class="num">Moustiquaires distribuées</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($byCluster as $c)
            <tr>
                <td>{{ $c['cluster'] }}</td>
                <td class="num">{{ $c['total'] }}</td>
                <td class="num">{{ $c['nets'] }}</td>
            </tr>
        @endforeach
        <tr class="row-grand-total">
            <td>TOTAL</td>
            <td class="num">{{ $totalEnrolled }}</td>
            <td class="num">{{ $totalNets }}</td>
        </tr>
    </tbody>
</table>
</div>

</body>
</html>
