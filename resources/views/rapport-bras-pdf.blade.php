<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

@page { margin: 2cm 2.5cm; size: A4 portrait; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    color: #111;
    background: #fff;
    line-height: 1.5;
}

/* ── En-tête ── */
.doc-header { border-bottom: 2px solid #C41230; padding-bottom: 8px; margin-bottom: 12px; }
.doc-title   { font-size: 15px; font-weight: bold; color: #111; }
.doc-sub     { font-size: 9px; color: #555; margin-top: 2px; }
.doc-meta    { font-size: 8px; color: #777; text-align: right; }

/* ── Section titles ── */
.section-title {
    font-size: 8.5px; font-weight: bold;
    text-transform: uppercase; letter-spacing: .06em;
    border-left: 3px solid #C41230; padding-left: 5px;
    margin: 14px 0 8px 0; color: #1A1A1A;
}

/* ── KPI cards (simulés en tableau) ── */
.kpi-table { width: 100%; border-collapse: separate; border-spacing: 5px; margin-bottom: 14px; }
.kpi-cell {
    background: #F8FAFC; border: 1px solid #E2E8F0;
    border-radius: 4px; padding: 7px 8px;
    text-align: center; vertical-align: middle;
    width: 25%;
}
.kpi-label { font-size: 7.5px; color: #64748B; text-transform: uppercase; letter-spacing:.04em; margin-bottom:3px; }
.kpi-value { font-size: 20px; font-weight: bold; }
.kpi-unit  { font-size: 7.5px; color: #64748B; margin-top: 1px; }
.kpi-sub   { font-size: 8px; font-weight: bold; margin-top: 3px; }

/* ── Bras cards ── */
.bras-table { width: 100%; border-collapse: separate; border-spacing: 8px; margin-bottom: 14px; }
.bras-cell  { vertical-align: top; width: 50%; border: 1px solid #E2E8F0; border-radius: 4px; overflow: hidden; }

.bras-header-pmd { background: #5B21B6; color: #fff; padding: 8px 10px; }
.bras-header-ig2 { background: #0369A1; color: #fff; padding: 8px 10px; }
.bras-header-name { font-size: 11px; font-weight: bold; letter-spacing: .02em; }
.bras-header-count { font-size: 20px; font-weight: bold; text-align: right; }
.bras-header-sub   { font-size: 7.5px; opacity: .85; text-align: right; }

.bras-body { padding: 8px 10px; }

/* ── Progress bar ── */
.prog-wrap { background: #E2E8F0; border-radius: 3px; height: 6px; margin: 3px 0 1px 0; overflow: hidden; }
.prog-fill-pmd  { background: #7C3AED; height: 6px; }
.prog-fill-ig2  { background: #0284C7; height: 6px; }
.prog-fill-ok   { background: #059669; height: 6px; }
.prog-fill-warn { background: #D97706; height: 6px; }
.prog-fill-crit { background: #DC2626; height: 6px; }

/* ── Cohorte blocks ── */
.cohort-block { border: 1px solid #E2E8F0; border-radius: 3px; padding: 6px 8px; margin-bottom: 5px; background: #FAFAFA; }
.cohort-title { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing:.04em; color: #374151; margin-bottom: 4px; }
.cohort-grid  { width: 100%; border-collapse: collapse; }
.cohort-grid td { vertical-align: top; padding: 0 4px 0 0; width: 50%; }

.stat-label { font-size: 7px; color: #64748B; text-transform: uppercase; letter-spacing:.04em; }
.stat-value { font-size: 14px; font-weight: bold; color: #111; }
.stat-target { font-size: 7.5px; color: #94A3B8; }
.stat-gap    { font-size: 7.5px; font-weight: bold; }
.text-ok   { color: #059669; }
.text-warn { color: #D97706; }
.text-crit { color: #DC2626; }

/* ── Rate badge ── */
.badge { padding: 1px 5px; border-radius: 3px; font-size: 7.5px; font-weight: bold; }
.badge-ok   { background: #DCFCE7; color: #15803D; }
.badge-warn { background: #FEF3C7; color: #92400E; }
.badge-crit { background: #FEE2E2; color: #991B1B; }

/* ── Divider bras ── */
.bras-divider-pmd { border-top: 1px solid #DDD6FE; margin: 5px 0; }
.bras-divider-ig2 { border-top: 1px solid #BAE6FD; margin: 5px 0; }

/* ── Pied bras ── */
.bras-footer { font-size: 7.5px; padding: 5px 10px; color: #374151; }
.bras-footer-pmd { background: #EDE9FE; border-top: 1px solid #DDD6FE; }
.bras-footer-ig2 { background: #E0F2FE; border-top: 1px solid #BAE6FD; }

/* ── Tableau récap ── */
table.recap { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 4px; }
table.recap thead th {
    background: #1E293B; color: #fff; font-size: 7.5px;
    padding: 4px 5px; text-align: right; font-weight: bold; white-space: nowrap;
}
table.recap thead th:first-child,
table.recap thead th:nth-child(2) { text-align: left; }
table.recap tbody td {
    padding: 3px 5px; border-bottom: 1px solid #F1F5F9;
    text-align: right; white-space: nowrap;
}
table.recap tbody td:first-child,
table.recap tbody td:nth-child(2) { text-align: left; }
table.recap tr.subtotal-pmd td { background: #EDE9FE; font-weight: bold; font-size: 7.5px; }
table.recap tr.subtotal-ig2 td { background: #E0F2FE; font-weight: bold; font-size: 7.5px; }
table.recap tr.total-row td {
    background: #1E293B; color: #fff; font-weight: bold; padding: 4px 5px;
}

/* ── Note ── */
.note { border-left: 3px solid #94A3B8; padding: 6px 8px; margin-top: 12px; font-size: 7.5px; color: #64748B; background: #F8FAFC; }
.note strong { color: #374151; }

.footer-doc { text-align: center; font-size: 7px; color: #94A3B8; margin-top: 14px; border-top: 1px solid #E2E8F0; padding-top: 5px; }
</style>
</head>
<body>

@php
function rateClass(float $r): string {
    if ($r >= 90) return 'badge-ok';
    if ($r >= 70) return 'badge-warn';
    return 'badge-crit';
}
function progClass(float $r): string {
    if ($r >= 90) return 'prog-fill-ok';
    if ($r >= 70) return 'prog-fill-warn';
    return 'prog-fill-crit';
}
function textClass(float $r): string {
    if ($r >= 90) return 'text-ok';
    if ($r >= 70) return 'text-warn';
    return 'text-crit';
}
@endphp

{{-- ══ EN-TÊTE ══ --}}
<table style="width:100%;border-collapse:collapse;">
    <tr>
        <td style="vertical-align:middle">
            <div class="doc-title">Rapport d'exécution — Enrôlement & Distribution par bras</div>
            <div class="doc-sub">Étude ENDURE-Net · District de Dassa-Zoumè · Bénin</div>
        </td>
        <td style="vertical-align:top; text-align:right; width:120px">
            <div class="doc-meta">Généré le {{ now()->format('d/m/Y') }}</div>
            <div class="doc-meta">{{ now()->format('H:i') }}</div>
            <div class="doc-meta" style="margin-top:4px">Cible : <strong>921 ménages/bras</strong></div>
            <div class="doc-meta">Moy. : <strong>3 moustiquaires/ménage</strong></div>
        </td>
    </tr>
</table>
<div class="doc-header" style="margin-top:8px"></div>

{{-- ══ KPI GLOBAUX ══ --}}
<div class="section-title">Synthèse globale</div>
<table class="kpi-table">
    <tr>
        @php
        $kpis = [
            ['label'=>'Ménages enrôlés',         'val'=>$global['hh'],    'unit'=>'ménages',        'rate'=>$global['rate_hh'],   'target'=>$global['target_hh']],
            ['label'=>'Moustiquaires distribuées','val'=>$global['nets'],  'unit'=>'moustiquaires',  'rate'=>$global['rate_nets'], 'target'=>$global['target_nets']],
            ['label'=>'Ménages restants',         'val'=>$global['gap_hh'],'unit'=>'à compléter',    'rate'=>null, 'target'=>null],
            ['label'=>'Filets manquants',         'val'=>$global['gap_nets'],'unit'=>'supplémentaires','rate'=>null,'target'=>null],
        ];
        @endphp
        @foreach($kpis as $k)
        <td class="kpi-cell">
            <div class="kpi-label">{{ $k['label'] }}</div>
            <div class="kpi-value {{ $k['rate'] !== null ? textClass($k['rate']) : 'color:#334155' }}">{{ number_format($k['val']) }}</div>
            <div class="kpi-unit">{{ $k['unit'] }}</div>
            @if($k['rate'] !== null)
                <div class="prog-wrap" style="margin-top:5px">
                    <div class="{{ progClass($k['rate']) }}" style="width:{{ min(100, $k['rate']) }}%"></div>
                </div>
                <div class="kpi-sub {{ textClass($k['rate']) }}">{{ $k['rate'] }}% <span style="color:#94A3B8;font-weight:normal">/ cible {{ number_format($k['target']) }}</span></div>
            @endif
        </td>
        @endforeach
    </tr>
</table>

{{-- ══ CARTES PAR BRAS ══ --}}
<div class="section-title">Détail par bras et par cohorte</div>
<table class="bras-table">
    <tr>
        @foreach($arms as $brasKey => $arm)
        @php $c = $arm['color']; @endphp
        <td class="bras-cell">

            {{-- Header bras --}}
            <div class="bras-header-{{ $c }}">
                <table style="width:100%;border-collapse:collapse">
                    <tr>
                        <td style="vertical-align:middle">
                            <div style="font-size:7px;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.04em">Bras {{ $brasKey }}</div>
                            <div class="bras-header-name">{{ $arm['label'] }}</div>
                        </td>
                        <td style="vertical-align:middle; text-align:right">
                            <div class="bras-header-count">{{ number_format($arm['hh']) }}</div>
                            <div class="bras-header-sub">ménages / {{ number_format($arm['target_hh']) }}</div>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:6px">
                    <table style="width:100%;border-collapse:collapse;font-size:7px;color:rgba(255,255,255,.85)">
                        <tr>
                            <td>Ménages — {{ $arm['rate_hh'] }}%</td>
                            <td style="text-align:right">{{ number_format($arm['gap_hh']) }} restants</td>
                        </tr>
                    </table>
                    <div class="prog-wrap" style="background:rgba(255,255,255,.25)">
                        <div style="background:rgba(255,255,255,.85); height:6px; width:{{ min(100, $arm['rate_hh']) }}%"></div>
                    </div>
                    <table style="width:100%;border-collapse:collapse;font-size:7px;color:rgba(255,255,255,.85);margin-top:3px">
                        <tr>
                            <td>Moustiquaires — {{ $arm['rate_nets'] }}%</td>
                            <td style="text-align:right">{{ number_format($arm['nets']) }} / {{ number_format($arm['target_nets']) }}</td>
                        </tr>
                    </table>
                    <div class="prog-wrap" style="background:rgba(255,255,255,.25)">
                        <div style="background:rgba(255,255,255,.65); height:6px; width:{{ min(100, $arm['rate_nets']) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Cohortes --}}
            <div class="bras-body">
                @foreach($arm['cohorts'] as $cohortKey => $cohort)
                    @if(!$loop->first)<div class="bras-divider-{{ $c }}"></div>@endif
                    <div class="cohort-block">
                        <table style="width:100%;border-collapse:collapse;margin-bottom:4px">
                            <tr>
                                <td><div class="cohort-title">{{ $cohort['label'] }}</div></td>
                                <td style="text-align:right"><span class="badge {{ rateClass($cohort['rate_hh']) }}">{{ $cohort['rate_hh'] }}%</span></td>
                            </tr>
                        </table>
                        <table class="cohort-grid">
                            <tr>
                                <td>
                                    <div class="stat-label">Ménages</div>
                                    <div class="stat-value">{{ number_format($cohort['hh']) }}</div>
                                    <div class="stat-target">/ {{ number_format($cohort['target_hh']) }}</div>
                                    <div class="prog-wrap">
                                        <div class="{{ progClass($cohort['rate_hh']) }}" style="width:{{ min(100, $cohort['rate_hh']) }}%"></div>
                                    </div>
                                    @if($cohort['gap_hh'] > 0)
                                        <div class="stat-gap text-crit">{{ number_format($cohort['gap_hh']) }} à compléter</div>
                                    @else
                                        <div class="stat-gap text-ok">Cible atteinte</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="stat-label">Moustiquaires</div>
                                    <div class="stat-value">{{ number_format($cohort['nets']) }}</div>
                                    <div class="stat-target">/ {{ number_format($cohort['target_nets']) }}</div>
                                    <div class="prog-wrap">
                                        <div class="{{ progClass($cohort['rate_nets']) }}" style="width:{{ min(100, $cohort['rate_nets']) }}%"></div>
                                    </div>
                                    @if($cohort['gap_nets'] > 0)
                                        <div class="stat-gap text-crit">{{ number_format($cohort['gap_nets']) }} manquants</div>
                                    @else
                                        <div class="stat-gap text-ok">Cible atteinte</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:7px;color:#64748B;margin-top:4px">
                            Taux moustiquaires : <span class="{{ textClass($cohort['rate_nets']) }}" style="font-weight:bold">{{ $cohort['rate_nets'] }}%</span>
                            &nbsp;·&nbsp;
                            Moy. filets/ménage : <strong>{{ $cohort['hh'] > 0 ? number_format($cohort['nets'] / $cohort['hh'], 1) : '—' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pied bras --}}
            <div class="bras-footer bras-footer-{{ $c }}">
                <strong style="color:{{ $c === 'pmd' ? '#5B21B6' : '#0369A1' }}">Total {{ $arm['label'] }}</strong>
                &nbsp;·&nbsp;
                <strong>{{ number_format($arm['hh']) }}</strong> ménages
                &nbsp;·&nbsp;
                <strong>{{ number_format($arm['nets']) }}</strong> moustiquaires
                &nbsp;·&nbsp;
                Moy. <strong>{{ $arm['hh'] > 0 ? number_format($arm['nets'] / $arm['hh'], 1) : '—' }}</strong>/ménage
            </div>

        </td>
        @endforeach
    </tr>
</table>

{{-- ══ TABLEAU RÉCAPITULATIF ══ --}}
<div class="section-title" style="margin-top:10px">Tableau récapitulatif — Exécution vs Protocole</div>
<table class="recap">
    <thead>
        <tr>
            <th style="text-align:left">Bras</th>
            <th style="text-align:left">Cohorte</th>
            <th>Cible ménages</th>
            <th>Enrôlés</th>
            <th>Taux</th>
            <th>Restants</th>
            <th>Cible filets</th>
            <th>Distribués</th>
            <th>Taux</th>
            <th>Manquants</th>
        </tr>
    </thead>
    <tbody>
        @foreach($arms as $brasKey => $arm)
            @php $first = true; $c = $arm['color']; @endphp
            @foreach($arm['cohorts'] as $cohortKey => $cohort)
            <tr>
                @if($first)
                <td rowspan="{{ count($arm['cohorts']) }}"
                    style="font-weight:bold; color:{{ $c === 'pmd' ? '#5B21B6' : '#0369A1' }}; border-left:3px solid {{ $c === 'pmd' ? '#7C3AED' : '#0284C7' }}; padding-left:6px">
                    {{ $arm['label'] }}
                </td>
                @php $first = false; @endphp
                @endif
                <td style="text-align:left; color:#374151; font-weight:600">{{ $cohort['label'] }}</td>
                <td>{{ number_format($cohort['target_hh']) }}</td>
                <td style="font-weight:bold">{{ number_format($cohort['hh']) }}</td>
                <td><span class="badge {{ rateClass($cohort['rate_hh']) }}">{{ $cohort['rate_hh'] }}%</span></td>
                <td style="font-weight:bold; color:{{ $cohort['gap_hh'] > 0 ? '#DC2626' : '#059669' }}">{{ $cohort['gap_hh'] > 0 ? number_format($cohort['gap_hh']) : '✓' }}</td>
                <td>{{ number_format($cohort['target_nets']) }}</td>
                <td style="font-weight:bold">{{ number_format($cohort['nets']) }}</td>
                <td><span class="badge {{ rateClass($cohort['rate_nets']) }}">{{ $cohort['rate_nets'] }}%</span></td>
                <td style="font-weight:bold; color:{{ $cohort['gap_nets'] > 0 ? '#DC2626' : '#059669' }}">{{ $cohort['gap_nets'] > 0 ? number_format($cohort['gap_nets']) : '✓' }}</td>
            </tr>
            @endforeach

            {{-- Sous-total bras --}}
            <tr class="subtotal-{{ $c }}">
                <td style="color:{{ $c === 'pmd' ? '#5B21B6' : '#0369A1' }}; font-size:7px; text-transform:uppercase; letter-spacing:.04em">Sous-total</td>
                <td style="text-align:left; color:#374151">{{ $arm['label'] }}</td>
                <td>{{ number_format($arm['target_hh']) }}</td>
                <td style="font-weight:bold">{{ number_format($arm['hh']) }}</td>
                <td><span class="badge {{ rateClass($arm['rate_hh']) }}">{{ $arm['rate_hh'] }}%</span></td>
                <td style="font-weight:bold; color:{{ $arm['gap_hh'] > 0 ? '#DC2626' : '#059669' }}">{{ $arm['gap_hh'] > 0 ? number_format($arm['gap_hh']) : '✓' }}</td>
                <td>{{ number_format($arm['target_nets']) }}</td>
                <td style="font-weight:bold">{{ number_format($arm['nets']) }}</td>
                <td><span class="badge {{ rateClass($arm['rate_nets']) }}">{{ $arm['rate_nets'] }}%</span></td>
                <td style="font-weight:bold; color:{{ $arm['gap_nets'] > 0 ? '#DC2626' : '#059669' }}">{{ $arm['gap_nets'] > 0 ? number_format($arm['gap_nets']) : '✓' }}</td>
            </tr>
        @endforeach

        {{-- Total global --}}
        <tr class="total-row">
            <td>TOTAL</td>
            <td>Tous bras confondus</td>
            <td>{{ number_format($global['target_hh']) }}</td>
            <td>{{ number_format($global['hh']) }}</td>
            <td><span class="badge {{ rateClass($global['rate_hh']) }}">{{ $global['rate_hh'] }}%</span></td>
            <td style="color:{{ $global['gap_hh'] > 0 ? '#FCA5A5' : '#6EE7B7' }}">{{ $global['gap_hh'] > 0 ? number_format($global['gap_hh']) : '✓' }}</td>
            <td>{{ number_format($global['target_nets']) }}</td>
            <td>{{ number_format($global['nets']) }}</td>
            <td><span class="badge {{ rateClass($global['rate_nets']) }}">{{ $global['rate_nets'] }}%</span></td>
            <td style="color:{{ $global['gap_nets'] > 0 ? '#FCA5A5' : '#6EE7B7' }}">{{ $global['gap_nets'] > 0 ? number_format($global['gap_nets']) : '✓' }}</td>
        </tr>
    </tbody>
</table>

{{-- ══ NOTE ══ --}}
<div class="note">
    <strong>Note méthodologique</strong> &nbsp;·&nbsp;
    Cible protocole : 921 ménages/bras (611 Cohorte A + 310 Cohorte B) = 1 842 au total.
    Moustiquaires : 3/ménage → 2 763/bras (5 526 au total).
    Enrôlés = ménages avec consentement éclairé (<em>consent_accepted = 1</em>).
    Taux : <span style="color:#059669;font-weight:bold">Vert ≥ 90%</span> ·
    <span style="color:#D97706;font-weight:bold">Orange ≥ 70%</span> ·
    <span style="color:#DC2626;font-weight:bold">Rouge &lt; 70%</span>
</div>

<div class="footer-doc">
    ENDURE-Net Dashboard · AIRID · {{ now()->format('d/m/Y à H:i') }} · Données REDCap
</div>

</body>
</html>
