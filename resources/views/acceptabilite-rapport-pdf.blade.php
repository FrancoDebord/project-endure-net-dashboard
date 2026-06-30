<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page { margin:0; size: A4 portrait; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 8.5px;
    color: #1E293B;
    background: #fff;
    line-height: 1.5;
    padding: 14mm 13mm;
}

/* ── EN-TÊTE ── */
.doc-header { border-bottom: 3px solid #C41230; padding-bottom: 8px; margin-bottom: 14px; }
.header-inner { width:100%; border-collapse:collapse; }
.logo-box {
    width:42px; height:42px; background:#C41230; border-radius:6px;
    text-align:center; line-height:42px; color:#fff;
    font-size:16px; font-weight:bold; display:inline-block;
}
.header-text-cell { vertical-align:top; padding-left:10px; }
.header-org  { font-size:6.5px; font-weight:bold; color:#94A3B8; text-transform:uppercase; letter-spacing:.12em; margin-bottom:2px; }
.header-title{ font-size:13px; font-weight:bold; color:#1E293B; }
.header-sub  { font-size:7.5px; color:#64748B; margin-top:3px; }
.header-meta-cell { text-align:right; vertical-align:top; width:155px; }
.meta-line   { font-size:7px; color:#64748B; margin-bottom:2px; }
.meta-line b { color:#1E293B; }

/* ── RÉSUMÉ KPI ── */
.kpi-row { width:100%; border-collapse:separate; border-spacing:5px 0; margin-bottom:14px; }
.kpi-cell {
    background:#F8FAFC; border:1px solid #E2E8F0; border-radius:4px;
    padding:6px 10px; text-align:center;
}
.kpi-label { font-size:6px; color:#64748B; text-transform:uppercase; letter-spacing:.07em; margin-bottom:3px; }
.kpi-value { font-size:18px; font-weight:bold; }
.kpi-sub   { font-size:6.5px; color:#475569; margin-top:1px; }

/* ── BLOC DATE ── */
.date-block { margin-bottom:18px; }
.date-header {
    background:#1E293B; color:#fff;
    padding:5px 8px; font-size:9px; font-weight:bold;
    text-transform:uppercase; letter-spacing:.06em;
    border-radius:3px 3px 0 0;
}

/* ── TABLEAU ── */
.tbl { width:100%; border-collapse:collapse; border:1.5px solid #94A3B8; }

.tbl thead tr.hdr-group th {
    font-size:7px; font-weight:bold; text-align:center;
    padding:4px 8px; border:1px solid #94A3B8; white-space:nowrap;
}
.hdr-tablet { background:#334155; color:#fff; vertical-align:middle; }
.hdr-ua     { background:#065F46; color:#fff; }
.hdr-ae     { background:#1E3A5F; color:#fff; }

.tbl thead tr.hdr-sub th {
    font-size:6.5px; font-weight:bold; text-align:center;
    text-transform:uppercase; letter-spacing:.04em;
    padding:3px 5px; border:1px solid #94A3B8;
    background:#F1F5F9; color:#475569;
}

.tbl tbody td {
    padding:4px 6px; font-size:8.5px; vertical-align:middle;
    border-right:1px solid #CBD5E1; text-align:center;
}
.tbl tbody td:first-child  { text-align:left; border-right:1px solid #94A3B8; }
.tbl tbody td:last-child   { border-right:none; }
.tbl tbody tr              { border-bottom:1px solid #CBD5E1; }
.tbl tbody tr.odd          { background:#fff; }
.tbl tbody tr.even         { background:#F8FAFC; }

.tbl tbody tr.total-row    { background:#1E293B; border-top:2px solid #1E293B; }
.tbl tbody tr.total-row td { color:#fff; font-weight:bold; font-size:8.5px; border-right:1px solid #334155; }
.tbl tbody tr.total-row td:first-child { letter-spacing:.08em; font-size:7.5px; }
.tbl tbody tr.total-row td:last-child  { border-right:none; }

/* Séparateur colonnes UA / AE */
.sep-left  { border-left:2px solid #94A3B8 !important; }

/* Valeurs */
.tablet-lbl { font-weight:bold; font-size:9px; color:#1E293B; }
.v-visited  { color:#1E293B; font-weight:bold; }
.v-present  { color:#15803D; font-weight:bold; }
.v-absent   { color:#B91C1C; font-weight:bold; }
.v-zero     { color:#CBD5E1; }

/* ── WIDTHS ── */
.col-tablet   { width:90px; }
.col-num      { width:46px; }
</style>
</head>
<body>

{{-- ── EN-TÊTE ── --}}
<div class="doc-header">
<table class="header-inner">
<tr>
    <td style="width:52px;vertical-align:middle">
        <div class="logo-box">EN</div>
    </td>
    <td class="header-text-cell">
        <div class="header-org">AIRID · ENDURE-Net Study · District de Dassa-Zoumè</div>
        <div class="header-title">Rapport Journalier — Acceptabilité &amp; Adverse Events</div>
        <div class="header-sub">
            Activité par tablette · Ménages visités, présents et absents
            @if($dateDebut || $dateFin)
            &nbsp;·&nbsp;
            @if($dateDebut && $dateFin)
                Du <b>{{ \Carbon\Carbon::parse($dateDebut)->locale('fr')->isoFormat('D MMM YYYY') }}</b>
                au <b>{{ \Carbon\Carbon::parse($dateFin)->locale('fr')->isoFormat('D MMM YYYY') }}</b>
            @elseif($dateDebut)
                À partir du <b>{{ \Carbon\Carbon::parse($dateDebut)->locale('fr')->isoFormat('D MMM YYYY') }}</b>
            @else
                Jusqu'au <b>{{ \Carbon\Carbon::parse($dateFin)->locale('fr')->isoFormat('D MMM YYYY') }}</b>
            @endif
            @endif
        </div>
    </td>
    <td class="header-meta-cell">
        <div class="meta-line">Généré le <b>{{ $now }}</b></div>
        <div class="meta-line"><b>{{ count($reportData) }}</b> jour(s) dans ce rapport</div>
    </td>
</tr>
</table>
</div>

{{-- ── KPI PÉRIODE ── --}}
@php
    $grandUA = ['visited' => 0, 'present' => 0, 'absent' => 0];
    $grandAE = ['visited' => 0, 'present' => 0, 'absent' => 0];
    foreach ($reportData as $rows) {
        foreach ($rows as $row) {
            $grandUA['visited'] += $row['ua']['visited'];
            $grandUA['present'] += $row['ua']['present'];
            $grandUA['absent']  += $row['ua']['absent'];
            $grandAE['visited'] += $row['ae']['visited'];
            $grandAE['present'] += $row['ae']['present'];
            $grandAE['absent']  += $row['ae']['absent'];
        }
    }
@endphp
@if(!empty($reportData))
<table class="kpi-row">
<tr>
    <td class="kpi-cell">
        <div class="kpi-label">UA — Visités (total)</div>
        <div class="kpi-value" style="color:#1E293B">{{ $grandUA['visited'] }}</div>
        <div class="kpi-sub">ménages Acceptabilité</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">UA — Présents</div>
        <div class="kpi-value" style="color:#15803D">{{ $grandUA['present'] }}</div>
        <div class="kpi-sub">{{ $grandUA['visited'] > 0 ? round($grandUA['present']/$grandUA['visited']*100) : 0 }}% de présence</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">UA — Absents</div>
        <div class="kpi-value" style="color:#B91C1C">{{ $grandUA['absent'] }}</div>
        <div class="kpi-sub">{{ $grandUA['visited'] > 0 ? round($grandUA['absent']/$grandUA['visited']*100) : 0 }}% d'absence</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">AE — Visités (total)</div>
        <div class="kpi-value" style="color:#1E3A5F">{{ $grandAE['visited'] }}</div>
        <div class="kpi-sub">ménages Adverse Events</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">AE — Présents</div>
        <div class="kpi-value" style="color:#15803D">{{ $grandAE['present'] }}</div>
        <div class="kpi-sub">{{ $grandAE['visited'] > 0 ? round($grandAE['present']/$grandAE['visited']*100) : 0 }}% de présence</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">AE — Absents</div>
        <div class="kpi-value" style="color:#B91C1C">{{ $grandAE['absent'] }}</div>
        <div class="kpi-sub">{{ $grandAE['visited'] > 0 ? round($grandAE['absent']/$grandAE['visited']*100) : 0 }}% d'absence</div>
    </td>
</tr>
</table>
@endif

{{-- ── PAS DE DONNÉES ── --}}
@if(empty($reportData))
<div style="text-align:center;padding:50px 0;color:#94A3B8;">
    <div style="font-size:14px;font-weight:bold;margin-bottom:6px;">Aucune donnée pour la période sélectionnée</div>
    <div style="font-size:8px;">Vérifiez les dates ou élargissez la période.</div>
</div>
@endif

{{-- ── SECTIONS PAR DATE ── --}}
@foreach($reportData as $date => $rows)
@php
    $dateFr = \Carbon\Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    $totUA  = ['visited' => 0, 'present' => 0, 'absent' => 0];
    $totAE  = ['visited' => 0, 'present' => 0, 'absent' => 0];
    foreach ($rows as $row) {
        $totUA['visited'] += $row['ua']['visited'];
        $totUA['present'] += $row['ua']['present'];
        $totUA['absent']  += $row['ua']['absent'];
        $totAE['visited'] += $row['ae']['visited'];
        $totAE['present'] += $row['ae']['present'];
        $totAE['absent']  += $row['ae']['absent'];
    }
    $rowN = 0;
@endphp

<div class="date-block" style="page-break-inside:avoid">
    <div class="date-header">{{ $dateFr }}</div>

    <table class="tbl">
        <thead>
            <tr class="hdr-group">
                <th class="hdr-tablet col-tablet" rowspan="2" style="vertical-align:middle">Tablette</th>
                <th class="hdr-ua" colspan="3">Acceptabilité (UA)</th>
                <th class="hdr-ae sep-left" colspan="3">Adverse Events (AE)</th>
            </tr>
            <tr class="hdr-sub">
                <th class="col-num">Visités</th>
                <th class="col-num">Présents</th>
                <th class="col-num">Absents</th>
                <th class="col-num sep-left">Visités</th>
                <th class="col-num">Présents</th>
                <th class="col-num">Absents</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
        @php $rowN++; @endphp
        <tr class="{{ $rowN % 2 === 0 ? 'even' : 'odd' }}" style="page-break-inside:avoid">
            <td>
                <span class="tablet-lbl">Tablette&nbsp;{{ $row['tablet'] }}</span>
            </td>
            <td class="{{ $row['ua']['visited'] > 0 ? 'v-visited' : 'v-zero' }}">
                {{ $row['ua']['visited'] > 0 ? $row['ua']['visited'] : '—' }}
            </td>
            <td class="{{ $row['ua']['present'] > 0 ? 'v-present' : 'v-zero' }}">
                {{ $row['ua']['present'] > 0 ? $row['ua']['present'] : '—' }}
            </td>
            <td class="{{ $row['ua']['absent'] > 0 ? 'v-absent' : 'v-zero' }}">
                {{ $row['ua']['absent'] > 0 ? $row['ua']['absent'] : '—' }}
            </td>
            <td class="{{ $row['ae']['visited'] > 0 ? 'v-visited' : 'v-zero' }} sep-left">
                {{ $row['ae']['visited'] > 0 ? $row['ae']['visited'] : '—' }}
            </td>
            <td class="{{ $row['ae']['present'] > 0 ? 'v-present' : 'v-zero' }}">
                {{ $row['ae']['present'] > 0 ? $row['ae']['present'] : '—' }}
            </td>
            <td class="{{ $row['ae']['absent'] > 0 ? 'v-absent' : 'v-zero' }}">
                {{ $row['ae']['absent'] > 0 ? $row['ae']['absent'] : '—' }}
            </td>
        </tr>
        @endforeach

        {{-- Ligne de total --}}
        <tr class="total-row">
            <td>TOTAL</td>
            <td>{{ $totUA['visited'] }}</td>
            <td>{{ $totUA['present'] }}</td>
            <td>{{ $totUA['absent'] }}</td>
            <td class="sep-left">{{ $totAE['visited'] }}</td>
            <td>{{ $totAE['present'] }}</td>
            <td>{{ $totAE['absent'] }}</td>
        </tr>
        </tbody>
    </table>
</div>

@endforeach

</body>
</html>
