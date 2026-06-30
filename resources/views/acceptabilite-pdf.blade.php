<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page { margin:0; size: A4 landscape; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 8.5px;
    color: #1E293B;
    background: #fff;
    line-height: 1.45;
    padding: 14mm 12mm;
}

/* ── EN-TÊTE ── */
.doc-header { border-bottom: 3px solid #C41230; padding-bottom: 8px; margin-bottom: 12px; }
.header-inner { width:100%; border-collapse:collapse; }
.logo-box {
    width:46px; height:46px; background:#C41230; border-radius:7px;
    text-align:center; line-height:46px; color:#fff;
    font-size:17px; font-weight:bold; display:inline-block;
}
.header-text-cell { vertical-align:top; padding-left:10px; }
.header-org  { font-size:7px; font-weight:bold; color:#94A3B8; text-transform:uppercase; letter-spacing:.1em; margin-bottom:2px; }
.header-title{ font-size:14px; font-weight:bold; color:#1E293B; }
.header-sub  { font-size:8px; color:#64748B; margin-top:3px; }
.header-meta-cell { text-align:right; vertical-align:top; width:200px; }
.meta-line   { font-size:7.5px; color:#64748B; margin-bottom:2px; }
.meta-line b { color:#1E293B; }

/* ── KPI RÉSUMÉ ── */
.kpi-table { width:100%; border-collapse:separate; border-spacing:6px 0; margin-bottom:12px; }
.kpi-cell { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:5px; padding:7px 10px; text-align:center; }
.kpi-label { font-size:6.5px; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
.kpi-value { font-size:20px; font-weight:bold; }
.kpi-sub   { font-size:7px; color:#475569; margin-top:2px; }

/* ── SECTION GRAPPE ── */
.grappe-block { margin-bottom:14px; }
.grappe-title {
    font-size:8.5px; font-weight:bold; text-transform:uppercase; letter-spacing:.07em;
    border-left:3px solid #C41230; padding-left:5px;
    margin-bottom:5px; color:#1E293B;
}
.grappe-block + .grappe-block { page-break-before: auto; }

/* ── TABLEAU ── */
.tbl { width:100%; border-collapse:collapse; }

.tbl { border:1.5px solid #94A3B8; }
.tbl thead tr { background:#1E293B; }
.tbl thead th {
    color:#fff; font-size:6.5px; font-weight:bold;
    text-transform:uppercase; letter-spacing:.06em;
    padding:5px 5px; text-align:left;
    border-right:1.5px solid #475569; white-space:nowrap;
}
.tbl thead th:last-child { border-right:none; }
.tbl thead th.th-center { text-align:center; }

.tbl tbody tr { border-bottom:1px solid #94A3B8; }
.tbl tbody tr.odd  { background:#fff; }
.tbl tbody tr.even { background:#F8FAFC; }
.tbl tbody tr.ua-visited { background:#ECFDF5; }
.tbl tbody tr.ua-visited td { color:#14532D; }
.tbl tbody tr.ae-visited-only { background:#EFF6FF; }
.tbl tbody tr.ae-visited-only td { color:#1E3A5F; }
.tbl tbody tr.row-absent { background:#FFF7ED; }
.tbl tbody tr.row-absent td { color:#7C2D12; }

.tbl tbody td {
    padding:4px 5px; font-size:7.5px; vertical-align:top;
    border-right:1px solid #94A3B8;
}
.tbl tbody td:last-child { border-right:none; }
.tbl tbody td.td-center { text-align:center; vertical-align:middle; }

/* ── COLONNES ── */
.col-num     { width:14px;  text-align:center; color:#94A3B8; }
.col-arm     { width:28px;  text-align:center; }
.col-cohort  { width:22px;  text-align:center; }
.col-code    { width:76px;  font-family:monospace; font-size:7px; font-weight:bold; }
.col-village { width:58px; }
.col-contact { width:158px; }
.col-moment  { width:60px;  font-size:7px; }
.col-address { width:80px;  font-size:7px; }
.col-cb      { width:54px;  text-align:center; vertical-align:middle; }

.c-name  { font-size:8px;   font-weight:bold; }
.c-phone { font-size:7.5px; font-weight:bold; font-family:monospace; color:#334155; }
.c-sep   { border-top:1px solid #CBD5E1; margin:2px 0; }

/* ── CASES À COCHER ── */
.cb {
    display:inline-block;
    width:13px; height:13px; border:1.5px solid #94A3B8; border-radius:2px;
    text-align:center; line-height:13px; font-size:9px; font-weight:bold;
}
.cb-checked   { background:#DCFCE7; color:#15803D; border-color:#16A34A; }
.cb-pending   { background:#FEF3C7; color:#92400E; border-color:#D97706; }
.cb-unchecked { background:#F8FAFC; color:transparent; }
.cb-label     { font-size:6px; display:block; margin-top:1px; }
.cb-label-ok  { color:#15803D; }
.cb-label-pnd { color:#92400E; }
.cb-label-no  { color:#CBD5E1; }
.cb-label-abs { color:#C2410C; }
.cb-absent    { background:#FED7AA; color:#9A3412; border-color:#FB923C; }

/* ── BADGES ── */
.badge { border-radius:3px; padding:1px 4px; font-size:6.5px; font-weight:bold; white-space:nowrap; }
.badge-pd { background:#EDE9FE; color:#5B21B6; }
.badge-g2 { background:#DBEAFE; color:#1D4ED8; }

/* ── PIED DE PAGE SECTION ── */
.section-footer { font-size:7px; color:#64748B; text-align:right; margin-top:2px; font-style:italic; }
</style>
</head>
<body>

{{-- ── EN-TÊTE DOCUMENT ── --}}
<div class="doc-header">
<table class="header-inner">
<tr>
    <td style="width:56px;vertical-align:middle">
        <div class="logo-box">EN</div>
    </td>
    <td class="header-text-cell">
        <div class="header-org">AIRID · ENDURE-Net Study · District de Dassa-Zoumè</div>
        <div class="header-title">Acceptabilité &amp; Adverse Events — Cohorte A</div>
        <div class="header-sub">
            Suivi des visites par ménage
            @if($arm ?? null)                  &nbsp;·&nbsp; Bras : <b>{{ $armLabels[$arm] ?? $arm }}</b> @endif
            @if($cluster ?? null)              &nbsp;·&nbsp; <b>{{ $clusterLabel }}</b> @endif
            @if(($filters['ua_status'] ?? '')) &nbsp;·&nbsp; UA : <b>{{ match($filters['ua_status']) { 'visited' => 'Visités', 'pending' => 'En attente', 'absent' => 'Absents', default => $filters['ua_status'] } }}</b> @endif
            @if(($filters['ae_status'] ?? '')) &nbsp;·&nbsp; AE : <b>{{ match($filters['ae_status']) { 'selected' => 'Sélectionnés', 'visited' => 'Visités', 'pending' => 'En attente', 'absent' => 'Absents', 'none' => 'Non sélectionnés', default => $filters['ae_status'] } }}</b> @endif
            @if(($filters['search'] ?? ''))    &nbsp;·&nbsp; Recherche : <b>"{{ $filters['search'] }}"</b> @endif
            @if(($filters['tablet'] ?? ''))       &nbsp;·&nbsp; Tablette : <b>{{ $filters['tablet'] }}</b> @endif
            @if(($filters['visit_status'] ?? '')) &nbsp;·&nbsp; Visites : <b>{{ match($filters['visit_status']) { 'pending' => 'Non visités (UA ou AE)', 'done' => 'Tout visité', 'absent' => 'Absents (UA ou AE)', default => $filters['visit_status'] } }}</b> @endif
        </div>
    </td>
    <td class="header-meta-cell">
        <div class="meta-line">Généré le <b>{{ $now }}</b></div>
        <div class="meta-line">Cohorte A — <b>{{ $stats['ua_total'] }}</b> ménages</div>
        <div class="meta-line">AE sélectionnés — <b>{{ $stats['ae_total'] }}</b></div>
    </td>
</tr>
</table>
</div>

{{-- ── KPI RÉSUMÉ ── --}}
<table class="kpi-table">
<tr>
    <td class="kpi-cell">
        <div class="kpi-label">Acceptabilité — Total</div>
        <div class="kpi-value" style="color:#1E293B">{{ $stats['ua_total'] }}</div>
        <div class="kpi-sub">ménages Cohorte A</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">Acceptabilité — Visités</div>
        <div class="kpi-value" style="color:#15803D">{{ $stats['ua_visited'] }}</div>
        <div class="kpi-sub">{{ $stats['ua_rate'] }}% &nbsp;·&nbsp; {{ $stats['ua_pending'] }} restants</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">Adverse Events — Sélectionnés</div>
        <div class="kpi-value" style="color:#C41230">{{ $stats['ae_total'] }}</div>
        <div class="kpi-sub">ménages retenus AE</div>
    </td>
    <td class="kpi-cell">
        <div class="kpi-label">Adverse Events — Visités</div>
        <div class="kpi-value" style="color:#0369A1">{{ $stats['ae_visited'] }}</div>
        <div class="kpi-sub">{{ $stats['ae_rate'] }}% &nbsp;·&nbsp; {{ $stats['ae_pending'] }} restants</div>
    </td>
</tr>
</table>

{{-- ── TABLEAUX PAR GRAPPE ── --}}
@foreach($grouped as $clusterId => $clusterHouseholds)
@php
    $clusterLabel = $clusterDisplay[$clusterId] ?? 'Grappe '.$clusterId;
    $uaCount = $clusterHouseholds->filter(fn($h) => $h['in_acceptabilite'] ?? false)->count();
    $uaDone  = $clusterHouseholds->where('ua_visited', true)->count();
    $aeCount = $clusterHouseholds->filter(fn($h) => $h['in_ae'] ?? $h['is_ae_selected'] ?? false)->count();
    $aeDone  = $clusterHouseholds->where('ae_visited', true)->count();
    $rowN    = 0;
@endphp

<div class="grappe-block">
    <div class="grappe-title">
        {{ $clusterLabel }}
        <span style="font-weight:normal;color:#64748B;font-size:7.5px">
            — {{ $clusterHouseholds->count() }} ménage(s) ·
            Accept. {{ $uaDone }}/{{ $uaCount }} ·
            AE {{ $aeDone }}/{{ $aeCount }}
        </span>
    </div>

    <table class="tbl">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-arm">Bras</th>
                <th class="col-cohort th-center">Coh.</th>
                <th class="col-code">Code ménage</th>
                <th class="col-village">Village</th>
                <th class="col-contact">Chef de ménage / Contact</th>
                <th class="col-moment">Meilleur moment</th>
                <th class="col-address">Adresse</th>
                <th class="col-cb th-center">Acceptabilité</th>
                <th class="col-cb th-center">Adverse Event</th>
            </tr>
        </thead>
        <tbody>
        @foreach($clusterHouseholds->sortBy(['arm','household_id']) as $hh)
        @php
            $rowN++;
            $isAbsent = ($hh['ua_absent'] ?? false) || ($hh['ae_absent'] ?? false);
            $rowClass = $isAbsent
                ? 'row-absent'
                : ($hh['ua_visited']
                    ? 'ua-visited'
                    : ($hh['ae_visited'] ? 'ae-visited-only' : ($rowN % 2 === 0 ? 'even' : 'odd')));
        @endphp
        <tr class="{{ $rowClass }}" style="page-break-inside:avoid">
            <td class="col-num">{{ $rowN }}</td>
            <td class="col-arm td-center">
                <span class="badge badge-{{ strtolower($hh['arm']) }}">{{ $hh['arm'] }}</span>
            </td>
            <td class="col-cohort td-center">
                @php $coh = $hh['study_cohort'] ?? ''; @endphp
                @if($coh)
                <span class="badge" style="background:{{ $coh=='1'?'#DCFCE7':'#FEF3C7' }};color:{{ $coh=='1'?'#15803D':'#92400E' }}">
                    {{ $coh=='1'?'A':($coh=='2'?'B':$coh) }}
                </span>
                @else —
                @endif
            </td>
            <td class="col-code">{{ $hh['household_id'] }}</td>
            <td class="col-village">
                <b>{{ $clusterDisplay[$hh['cluster_id'] ?? ''] ?? ($hh['village_label'] ?? '—') }}</b>
            </td>
            <td class="col-contact">
                <span class="c-name">{{ $hh['head_name'] ?? '—' }}</span>
                @if($hh['head_phone'])<br><span class="c-phone">{{ $hh['head_phone'] }}</span>@endif
                @if($hh['contact_name'] || $hh['contact_phone'])
                <div class="c-sep"></div>
                <span class="c-name">{{ $hh['contact_name'] ?? '—' }}</span>
                @if($hh['contact_phone'])<br><span class="c-phone">{{ $hh['contact_phone'] }}</span>@endif
                @endif
            </td>
            <td class="col-moment">{{ $hh['moment_visite'] ?? '—' }}</td>
            <td class="col-address">{{ $hh['adresse'] ?? '—' }}</td>

            {{-- Acceptabilité --}}
            <td class="col-cb td-center">
                @if(!($hh['in_acceptabilite'] ?? true))
                <span class="cb-label cb-label-no" style="opacity:.35">—</span>
                @elseif($hh['ua_absent'])
                <span class="cb cb-absent">✗</span>
                <span class="cb-label cb-label-abs">Absent</span>
                @elseif($hh['ua_visited'])
                <span class="cb cb-checked">✓</span>
                <span class="cb-label cb-label-ok">Visité</span>
                @else
                <span class="cb cb-unchecked"></span>
                <span class="cb-label cb-label-no">À visiter</span>
                @endif
            </td>

            {{-- Adverse Event --}}
            <td class="col-cb td-center">
                @if($hh['is_ae_selected'])
                    @if($hh['ae_absent'])
                    <span class="cb cb-absent">✗</span>
                    <span class="cb-label cb-label-abs">Absent</span>
                    @elseif($hh['ae_visited'])
                    <span class="cb cb-checked">✓</span>
                    <span class="cb-label cb-label-ok">Visité</span>
                    @else
                    <span class="cb cb-pending">○</span>
                    <span class="cb-label cb-label-pnd">À visiter</span>
                    @endif
                @else
                <span style="color:#CBD5E1;font-size:7px">—</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-footer">
        {{ $clusterLabel }} ·
        Acceptabilité {{ $uaDone }}/{{ $uaCount }} ({{ $uaCount > 0 ? round($uaDone/$uaCount*100) : 0 }}%) ·
        AE {{ $aeDone }}/{{ $aeCount > 0 ? $aeCount : '—' }}
        @if($aeCount > 0) ({{ round($aeDone/$aeCount*100) }}%) @endif
    </div>
</div>
@endforeach

</body>
</html>
