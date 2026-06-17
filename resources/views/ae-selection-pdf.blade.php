<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
/* ═══════════════════ BASE ═══════════════════ */
* { margin:0; padding:0; box-sizing:border-box; }

@page {
    margin: 0;
    size: A4 landscape;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 8.5px;
    color: #1E293B;
    background: #fff;
    line-height: 1.45;
    padding: 14mm 12mm;
}

/* ═══════════════════ EN-TÊTE ═══════════════════ */
.doc-header {
    border-bottom: 3px solid #C41230;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.header-inner {
    width: 100%;
    border-collapse: collapse;
}
.header-logo-cell {
    width: 60px;
    vertical-align: middle;
    padding-right: 12px;
}
.logo-box {
    width: 50px; height: 50px;
    background: #C41230;
    border-radius: 8px;
    text-align: center;
    vertical-align: middle;
    line-height: 50px;
    color: #fff;
    font-size: 18px;
    font-weight: bold;
    letter-spacing: .02em;
}
.header-text-cell { vertical-align: top; }
.header-org {
    font-size: 7px; font-weight: bold; color: #94A3B8;
    text-transform: uppercase; letter-spacing: .1em; margin-bottom: 2px;
}
.header-title {
    font-size: 15px; font-weight: bold; color: #1E293B; line-height: 1.2;
}
.header-sub {
    font-size: 8px; color: #64748B; margin-top: 3px;
}
.header-meta-cell {
    text-align: right;
    vertical-align: top;
    width: 200px;
}
.meta-line { font-size: 7.5px; color: #64748B; margin-bottom: 2px; }
.meta-line b { color: #1E293B; }

/* ═══════════════════ KPI RÉSUMÉ ═══════════════════ */
.kpi-table { width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:14px; }
.kpi-cell {
    background: #F8FAFC; border: 1px solid #E2E8F0;
    border-radius: 5px; padding: 8px 10px;
    text-align: center; vertical-align: middle;
}
.kpi-label { font-size:7px; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.kpi-value { font-size:22px; font-weight:bold; }
.kpi-unit  { font-size:7px; color:#94A3B8; margin-top:2px; }
.kpi-sub   { font-size:7.5px; font-weight:bold; color:#475569; margin-top:4px; }

/* ═══════════════════ BRAS CARDS ═══════════════════ */
.arm-cards-table { width:100%; border-collapse:separate; border-spacing:8px 0; margin-bottom:14px; }
.arm-card {
    border: 1.5px solid #E2E8F0; border-radius: 6px;
    overflow: hidden; vertical-align: top; width: 50%;
}
.arm-card-header {
    padding: 7px 10px;
    color: #fff;
}
.arm-pd .arm-card-header { background: #5B21B6; }
.arm-g2 .arm-card-header { background: #0369A1; }
.arm-name  { font-size: 9px; font-weight: bold; }
.arm-stats { font-size: 7px; opacity: .85; margin-top: 2px; }

.arm-card-body { padding: 6px 10px; }
.prog-row { margin-bottom: 3px; }
.prog-label { font-size:7px; color:#64748B; margin-bottom:1px; }
.prog-wrap  { background:#E2E8F0; border-radius:3px; height:5px; overflow:hidden; }
.prog-fill  { height:5px; border-radius:3px; }
.prog-pd  { background: #7C3AED; }
.prog-g2  { background: #0284C7; }
.prog-pct { font-size:8px; font-weight:bold; text-align:right; }

/* ═══════════════════ SECTION TITLE ═══════════════════ */
.section-title {
    font-size: 8px; font-weight: bold;
    text-transform: uppercase; letter-spacing: .07em;
    border-left: 3px solid #C41230; padding-left: 5px;
    margin: 12px 0 7px 0; color: #1E293B;
}

/* ═══════════════════ TABLEAU MÉNAGES ═══════════════════ */
.tbl { width:100%; border-collapse:collapse; border:1.5px solid #94A3B8; }

.tbl thead tr { background: #1E293B; }
.tbl thead th {
    color: #fff;
    font-size: 6.5px; font-weight: bold;
    text-transform: uppercase; letter-spacing: .06em;
    padding: 5px 5px;
    text-align: left;
    border-right: 1.5px solid #475569;
    white-space: nowrap;
}
.tbl thead th:last-child { border-right: none; }

.tbl tbody tr { border-bottom: 1px solid #94A3B8; }
.tbl tbody tr.odd  { background: #fff; }
.tbl tbody tr.even { background: #F8FAFC; }
.tbl tbody tr.completed { background: #F0FDF4; }
.tbl tbody tr.completed td { color: #166534; }
.tbl tbody tr.visited { background: #ECFDF5; }
.tbl tbody tr.visited td { color: #14532D; }

.tbl tbody td {
    padding: 4.5px 5px;
    font-size: 7.5px;
    vertical-align: top;
    border-right: 1px solid #94A3B8;
}
.tbl tbody td:last-child { border-right: none; }

/* Largeurs colonnes (paysage A4 ≈ 257mm — 11 colonnes) */
.col-num      { width: 14px;  text-align:center; color:#94A3B8; }
.col-arm      { width: 28px;  text-align:center; }
.col-cohort   { width: 24px;  text-align:center; }
.col-village  { width: 60px; }
.col-hhid     { width: 62px; font-family: monospace; font-size: 7px; font-weight: bold; }
.col-contacts { width: 148px; }
.col-contacts .c-name  { font-size: 8px; font-weight: bold; }
.col-contacts .c-phone { font-size: 7.5px; font-weight: bold; font-family: monospace; color: #334155; }
.col-contacts .c-sep   { border-top: 1px solid #CBD5E1; margin: 2px 0; }
.col-moment   { width: 48px; font-size:7px; }
.col-address  { width: 80px; font-size:7px; }
.col-nets     { width: 20px; text-align:center; font-weight:bold; }
.col-codes    { width: 85px; }
.col-status   { width: 32px; text-align:center; }

/* Badges */
.badge { border-radius: 3px; padding: 1px 4px; font-size: 6.5px; font-weight: bold; white-space:nowrap; }
.badge-pd { background: #EDE9FE; color: #5B21B6; }
.badge-g2 { background: #DBEAFE; color: #1D4ED8; }
.badge-ok { background: #DCFCE7; color: #15803D; }
.badge-no { background: #FEE2E2; color: #991B1B; }

/* Codes moustiquaires */
.net-code {
    display: inline-block;
    background: #E2E8F0; color: #334155;
    font-size: 6px; font-weight: bold;
    padding: 0 3px; border-radius: 2px;
    margin: 0 1px 1px 0;
}

/* Séparateur bras */
.arm-separator {
    page-break-before: always;
}
.arm-separator-first {
    page-break-before: avoid;
}

/* Pied de section */
.section-footer {
    font-size: 7px; color: #64748B;
    text-align: right; margin-top: 4px;
    font-style: italic;
}

/* Filtre appliqué */
.filter-tag {
    display: inline-block;
    background: #FEF3C7; color: #92400E;
    font-size: 6.5px; font-weight: bold;
    padding: 1px 5px; border-radius: 3px;
    margin: 0 2px 2px 0;
}

/* Signature terrain */
.field-sig {
    margin-top: 6px;
    border-top: 1px dashed #E2E8F0;
    padding-top: 4px;
    font-size: 6.5px; color: #94A3B8;
}
.sig-box {
    display: inline-block;
    border: 1px solid #CBD5E1;
    width: 80px; height: 18px;
    vertical-align: middle;
    margin-left: 5px;
    border-radius: 2px;
}
</style>
</head>
<body>

@php
    use Carbon\Carbon;
    $armLabels  = ['PD' => 'PERMANET DUAL', 'G2' => 'INTERCEPTOR G2'];
    $armColors  = ['PD' => 'pd', 'G2' => 'g2'];
    $aeVisitedIds = $aeVisitedIds ?? collect();

    $grouped    = $households->groupBy('arm');
    $now        = Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY à HH:mm');
    $isFiltered = array_filter($filters ?? []);

    $totalVisited = $households->filter(fn($hh) => $aeVisitedIds->has($hh->household_id))->count();
    $totalPending = $households->count() - $totalVisited;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     EN-TÊTE DU DOCUMENT
════════════════════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
<table class="header-inner">
<tr>
    <td class="header-logo-cell">
        <div class="logo-box">EN</div>
    </td>
    <td class="header-text-cell">
        <div class="header-org">AIRID — African Institute for Research in Infectious Diseases</div>
        <div class="header-title">Liste des ménages sélectionnés — Adverse Events</div>
        <div class="header-sub">
            Étude ENDURE-Net · REDCap Projet 46 · Sélection aléatoire par bras
            @if($isFiltered)
                &nbsp;·&nbsp;
                @foreach($filters as $key => $val)
                    @if($val)
                    <span class="filter-tag">
                        @if($key==='arm') Bras: {{ $val }}
                        @elseif($key==='village') Village: {{ $villageLabels[$val] ?? $val }}
                        @elseif($key==='cluster') Grappe: {{ $clusterLabels[$val] ?? $val }}
                        @elseif($key==='dag') DAG: {{ $val }}
                        @elseif($key==='status') Statut: {{ $val === 'visited' ? 'Visités (REDCap)' : 'En attente' }}
                        @endif
                    </span>
                    @endif
                @endforeach
            @endif
        </div>
    </td>
    <td class="header-meta-cell">
        <div class="meta-line">Généré le <b>{{ $now }}</b></div>
        <div class="meta-line">Total : <b>{{ $households->count() }} ménages</b></div>
        <div class="meta-line" style="color:#166534">Visités (REDCap) : <b>{{ $totalVisited }}</b></div>
        <div class="meta-line" style="color:#991B1B">En attente : <b>{{ $totalPending }}</b></div>
    </td>
</tr>
</table>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     RÉSUMÉ KPI GLOBAUX
════════════════════════════════════════════════════════════════════════════ --}}
<table class="kpi-table">
<tr>
    <td class="kpi-cell">
        <div class="kpi-label">Total sélectionnés</div>
        <div class="kpi-value" style="color:#1E293B">{{ $stats['total'] }}</div>
        <div class="kpi-unit">ménages</div>
    </td>
    <td class="kpi-cell" style="border:1px solid #BBF7D0">
        <div class="kpi-label">Visités (REDCap)</div>
        <div class="kpi-value" style="color:#15803D">{{ $stats['redcap_visited'] }}</div>
        <div class="kpi-unit">formulaire AE soumis</div>
        <div class="kpi-sub">{{ $stats['redcap_rate'] }}% de couverture</div>
    </td>
    <td class="kpi-cell" style="border:1px solid #FECACA">
        <div class="kpi-label">En attente (REDCap)</div>
        <div class="kpi-value" style="color:#C41230">{{ $stats['redcap_pending'] }}</div>
        <div class="kpi-unit">pas encore de données</div>
    </td>
    @foreach($stats['by_arm'] as $armKey => $arm)
    <td class="kpi-cell">
        <div class="kpi-label">{{ $armLabels[$armKey] ?? $armKey }}</div>
        <div class="kpi-value" style="color:{{ $armKey==='PD'?'#5B21B6':'#0369A1' }}">{{ $arm['total'] }}</div>
        <div class="kpi-unit">ménages</div>
        <div class="kpi-sub">{{ $arm['redcap_visited'] }} visités · {{ $arm['redcap_rate'] }}%</div>
    </td>
    @endforeach
</tr>
</table>

{{-- ═══════════════════════════════════════════════════════════════════════════
     PROGRESSION PAR BRAS
════════════════════════════════════════════════════════════════════════════ --}}
<table class="arm-cards-table">
<tr>
@foreach($stats['by_arm'] as $armKey => $arm)
<td class="arm-card arm-{{ strtolower($armKey) }}">
    <div class="arm-card-header">
        <div class="arm-name">{{ $arm['label'] }} ({{ $armKey }})</div>
        <div class="arm-stats">
            {{ $arm['redcap_visited'] }} visités REDCap · {{ $arm['redcap_pending'] }} en attente · {{ $arm['total'] }} total
        </div>
    </div>
    <div class="arm-card-body">
        <div class="prog-row">
            <div class="prog-label">Couverture REDCap (formulaires AE soumis)</div>
            <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:85%">
                    <div class="prog-wrap">
                        <div class="prog-fill prog-{{ strtolower($armKey) }}"
                             style="width:{{ $arm['redcap_rate'] }}%"></div>
                    </div>
                </td>
                <td style="width:15%;text-align:right">
                    <div class="prog-pct" style="color:{{ $armKey==='PD'?'#5B21B6':'#0369A1' }}">{{ $arm['redcap_rate'] }}%</div>
                </td>
            </tr>
            </table>
        </div>
    </div>
</td>
@endforeach
</tr>
</table>

{{-- ═══════════════════════════════════════════════════════════════════════════
     TABLEAUX DES MÉNAGES (un par bras, avec saut de page entre eux)
════════════════════════════════════════════════════════════════════════════ --}}
@php $firstArm = true; @endphp
@foreach(['PD', 'G2'] as $currentArm)
@php
    $armHouseholds  = $grouped->get($currentArm, collect());
    if($armHouseholds->isEmpty()) continue;
    $armLabel       = $armLabels[$currentArm] ?? $currentArm;
    $armDone        = $armHouseholds->where('form_completed', true)->count();
    $armTotal       = $armHouseholds->count();
    $armVisitedHere = $armHouseholds->filter(fn($hh) => $aeVisitedIds->has($hh->household_id))->count();
    $armPendingHere = $armTotal - $armVisitedHere;
@endphp

<div class="{{ $firstArm ? 'arm-separator-first' : 'arm-separator' }}">

{{-- En-tête de section bras --}}
<div class="section-title">
    {{ $armLabel }} ({{ $currentArm }}) —
    {{ $armTotal }} ménages sélectionnés ·
    <span style="color:#166534">{{ $armVisitedHere }} visités REDCap ({{ $armTotal > 0 ? round($armVisitedHere/$armTotal*100,1) : 0 }}%)</span> ·
    <span style="color:#991B1B">{{ $armPendingHere }} en attente</span>
</div>

<table class="tbl">
<thead>
    <tr>
        <th class="col-num">#</th>
        <th class="col-arm">Bras</th>
        <th class="col-cohort">Coh.</th>
        <th class="col-village">Village</th>
        <th class="col-hhid">Code ménage</th>
        <th class="col-contacts">Chef de ménage / Contact</th>
        <th class="col-moment">Meilleur moment</th>
        <th class="col-address">Adresse</th>
        <th class="col-nets">Moust.</th>
        <th class="col-codes">Codes moustiquaires</th>
        <th class="col-status">Statut AE</th>
    </tr>
</thead>
<tbody>
@php $rowN = 0; @endphp
@foreach($armHouseholds->sortBy(['village','cluster_id','household_id']) as $hh)
@php
    $rowN++;
    $isEven      = $rowN % 2 === 0;
    $isComplete  = $hh->form_completed;
    $isVisitedPdf = $aeVisitedIds->has($hh->household_id);
    $rowClass    = $isVisitedPdf ? 'visited' : ($isComplete ? 'completed' : ($isEven ? 'even' : 'odd'));
    $netCodes    = $hh->net_codes ?? [];
@endphp
<tr class="{{ $rowClass }}" style="page-break-inside:avoid">
    <td class="col-num">{{ $rowN }}</td>
    <td class="col-arm">
        <span class="badge badge-{{ strtolower($currentArm) }}">{{ $currentArm }}</span>
    </td>
    <td class="col-cohort">
        @php $c = $hh->study_cohort ?? ''; @endphp
        @if($c)
        <span class="badge" style="background:{{ $c=='1'?'#DCFCE7':'#FEF3C7' }};color:{{ $c=='1'?'#15803D':'#92400E' }}">
            {{ $c=='1'?'A':($c=='2'?'B':$c) }}
        </span>
        @else —
        @endif
    </td>
    <td class="col-village">
        <b>{{ $hh->cluster_id ? ($clusterLabels[$hh->cluster_id] ?? ($villageLabels[$hh->village] ?? $hh->village ?? '—').' '.$hh->cluster_id) : ($villageLabels[$hh->village] ?? $hh->village ?? '—') }}</b>
    </td>
    <td class="col-hhid">{{ $hh->household_id }}</td>
    <td class="col-contacts">
        <span class="c-name"><b>{{ $hh->head_name ?? '—' }}</b>@if($hh->numero_menage) <span style="font-size:6px;color:#94A3B8;font-weight:normal">N°{{ $hh->numero_menage }}</span>@endif</span>
        @if($hh->head_phone)<br><span class="c-phone">{{ $hh->head_phone }}</span>@endif
        @if($hh->contact_name || $hh->contact_phone)
        <div class="c-sep"></div>
        <span class="c-name">{{ $hh->contact_name ?? '—' }}</span>
        @if($hh->contact_phone)<br><span class="c-phone">{{ $hh->contact_phone }}</span>@endif
        @endif
    </td>
    <td class="col-moment">{{ $hh->moment_visite ?? '—' }}</td>
    <td class="col-address">{{ $hh->adresse ?? '—' }}</td>
    <td class="col-nets">
        <b>{{ $hh->nb_moustiquaires_etude ?? '—' }}</b>
    </td>
    <td class="col-codes">
        @if(!empty($netCodes))
            @foreach($netCodes as $code)
                <span class="net-code">{{ $code }}</span>
            @endforeach
        @else
            <span style="color:#CBD5E1">—</span>
        @endif
    </td>
    <td class="col-status">
        @if($isVisitedPdf)
            <span class="badge badge-ok">VISITÉ</span>
        @else
            <span class="badge badge-no">ATTENTE</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>

{{-- Pied de section --}}
<div class="section-footer">
    {{ $armLabel }} · {{ $armVisitedHere }}/{{ $armTotal }} visités REDCap
    ({{ $armTotal > 0 ? round($armVisitedHere/$armTotal*100,1) : 0 }}%)
    · {{ $armPendingHere }} ménages en attente de visite
    · Ce document est confidentiel et réservé aux équipes ENDURE-Net
</div>

</div>{{-- /arm-separator --}}
@php $firstArm = false; @endphp
@endforeach

</body>
</html>
