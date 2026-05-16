<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@page {
    size: A4 landscape;
    margin: 1.8cm 1.8cm 1.8cm 1.8cm;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #111;
    background: #fff;
    line-height: 1.6;
}

h1 { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
.meta { font-size: 10px; color: #555; margin-bottom: 3px; }
.doc-hr { border: none; border-top: 1.5px solid #111; margin: 8px 0 12px 0; }

h2 {
    font-size: 12px;
    font-weight: bold;
    border-left: 3px solid #C41230;
    padding-left: 6px;
    margin: 14px 0 8px 0;
}

/* Table constrained — does not fill the full page width */
.tbl-wrap { width: 62%; }

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5px;
    margin-bottom: 12px;
}
table thead th {
    background: #1A1A1A;
    color: #fff;
    font-weight: bold;
    padding: 5px 10px;
    text-align: left;
    font-size: 10px;
    letter-spacing: 0.03em;
}
table thead th.num { text-align: right; }
table tbody td {
    padding: 4px 10px;
    border-bottom: 1px solid #E2E8F0;
}
table tbody td.num { text-align: right; }
table tbody tr:nth-child(even) td { background: #F8FAFC; }
.row-total td {
    background: #C41230 !important;
    color: #fff !important;
    font-weight: bold;
    font-size: 10.5px;
}
.row-total td.num { text-align: right; }

.color-dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 2px;
    margin-right: 5px;
    vertical-align: middle;
}
</style>
</head>
<body>

<h1>Performance des binômes — ENDURE-Net</h1>
<p class="meta">Exporté le {{ now()->format('d/m/Y à H:i') }}</p>
<p class="meta">
    Période :
    @if ($dateFrom || $dateTo)
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'début' }}
        →
        {{ $dateTo   ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y')   : "aujourd'hui" }}
    @else
        toutes les dates ({{ count($allDates) }} jour(s) d'activité)
    @endif
    &nbsp;·&nbsp;
    Tablettes : {{ !empty($tablets) ? implode(', ', $tablets) : 'toutes' }}
</p>
<hr class="doc-hr">

@if (empty($tabletData))
    <p style="color:#6B7280;font-size:10px">Aucune donnée sur la période sélectionnée.</p>
@else

{{-- ── Summary table (constrained width) ── --}}
<h2>Résumé de la performance par binôme</h2>

<div class="tbl-wrap">
<table>
    <thead>
        <tr>
            <th>Binôme / Tablette</th>
            <th class="num">Jours actifs</th>
            <th class="num">Total enrôlés</th>
            <th class="num">Moy. / jour actif</th>
            <th class="num">Moy. / jour calendaire</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tabletData as $t)
        <tr>
            <td>
                <span class="color-dot" style="background:{{ $t['color'] }}"></span>
                Tablette {{ $t['tablet'] }}
            </td>
            <td class="num">{{ $t['activeDays'] }}</td>
            <td class="num">{{ $t['total'] }}</td>
            <td class="num">{{ $t['avg'] }}</td>
            <td class="num">
                {{ count($allDates) > 0 ? round($t['total'] / count($allDates), 1) : '—' }}
            </td>
        </tr>
        @endforeach
        <tr class="row-total">
            <td>TOTAL / MOYENNE GLOBALE</td>
            <td class="num">{{ count($allDates) }}</td>
            <td class="num">{{ $grandTotal }}</td>
            <td class="num">{{ $grandAvg }}</td>
            <td class="num">
                {{ count($allDates) > 0 && count($tabletData) > 0
                    ? round($grandTotal / count($allDates) / count($tabletData), 1)
                    : '—' }}
            </td>
        </tr>
    </tbody>
</table>
</div>

{{-- ── SVG line chart ── --}}
<h2>Courbes de performance journalière (— réelle &nbsp;· · · moyenne)</h2>

@php
$nDates = count($allDates);

$svgW  = 940;
$svgH  = 295;
$padL  = 42;
$padR  = 148;   // legend panel
$padT  = 20;
$padB  = 50;
$plotW = $svgW - $padL - $padR;
$plotH = $svgH - $padT - $padB;

// Y max
$maxY = 0;
foreach ($tabletData as $t) {
    foreach ($t['daily'] as $v) { if ($v > $maxY) $maxY = $v; }
}
$yMax = max((int)ceil($maxY / 5) * 5, 5);

$xFor = function(int $i) use ($nDates, $padL, $plotW): float {
    return $nDates > 1 ? round($padL + $i / ($nDates - 1) * $plotW, 1) : $padL + $plotW / 2;
};
$yFor = function(float $v) use ($padT, $plotH, $yMax): float {
    return round($padT + $plotH - ($v / $yMax) * $plotH, 1);
};

// X label thinning
$lEvery = 1;
if ($nDates > 15) $lEvery = 2;
if ($nDates > 30) $lEvery = 3;
if ($nDates > 50) $lEvery = 5;
if ($nDates > 70) $lEvery = 7;

$ySteps = 5;
$legX   = $padL + $plotW + 14;
@endphp

<svg xmlns="http://www.w3.org/2000/svg" width="{{ $svgW }}" height="{{ $svgH }}">

    {{-- Plot background --}}
    <rect x="{{ $padL }}" y="{{ $padT }}" width="{{ $plotW }}" height="{{ $plotH }}"
          fill="#F8FAFC" stroke="#CBD5E1" stroke-width="0.8"/>

    {{-- Y gridlines + labels --}}
    @for ($yi = 0; $yi <= $ySteps; $yi++)
        @php $yVal = $yMax * $yi / $ySteps; $ySvg = $yFor($yVal); @endphp
        @if ($yi > 0)
            <line x1="{{ $padL }}" y1="{{ $ySvg }}"
                  x2="{{ $padL + $plotW }}" y2="{{ $ySvg }}"
                  stroke="#E2E8F0" stroke-width="0.7"/>
        @endif
        <text x="{{ $padL - 5 }}" y="{{ $ySvg + 3.5 }}"
              text-anchor="end" font-size="8.5" fill="#94A3B8">{{ round($yVal) }}</text>
    @endfor

    {{-- X-axis tick marks + labels --}}
    @foreach ($allDates as $di => $date)
        @if ($di % $lEvery === 0 || $di === $nDates - 1)
            @php $xSvg = $xFor($di); $lbl = \Carbon\Carbon::parse($date)->format('d/m'); @endphp
            <line x1="{{ $xSvg }}" y1="{{ $padT + $plotH }}"
                  x2="{{ $xSvg }}" y2="{{ $padT + $plotH + 5 }}"
                  stroke="#94A3B8" stroke-width="0.7"/>
            <text x="{{ $xSvg }}" y="{{ $padT + $plotH + 15 }}"
                  text-anchor="middle" font-size="8" fill="#64748B">{{ $lbl }}</text>
        @endif
    @endforeach

    {{-- Axes --}}
    <line x1="{{ $padL }}" y1="{{ $padT }}"
          x2="{{ $padL }}" y2="{{ $padT + $plotH }}"
          stroke="#374151" stroke-width="1.5"/>
    <line x1="{{ $padL }}" y1="{{ $padT + $plotH }}"
          x2="{{ $padL + $plotW }}" y2="{{ $padT + $plotH }}"
          stroke="#374151" stroke-width="1.5"/>

    {{-- Y axis label --}}
    <text x="11" y="{{ $padT + $plotH / 2 }}"
          text-anchor="middle" font-size="8.5" fill="#64748B"
          transform="rotate(-90, 11, {{ $padT + $plotH / 2 }})">Enrôlés / jour</text>

    {{-- ── Per-tablet: daily curve + average dashed line ── --}}
    @foreach ($tabletData as $t)
        @php
            $color = $t['color'];
            $avg   = (float)$t['avg'];
            $yAvg  = $yFor($avg);

            // Daily polyline points
            $points = '';
            foreach ($allDates as $di => $date) {
                $v = $t['daily'][$date] ?? 0;
                $points .= $xFor($di) . ',' . $yFor($v) . ' ';
            }
            $points = trim($points);
        @endphp

        {{-- Daily performance line --}}
        <polyline points="{{ $points }}"
                  fill="none" stroke="{{ $color }}" stroke-width="1.6"
                  stroke-linejoin="round" stroke-linecap="round" opacity="0.9"/>

        {{-- Data points --}}
        @foreach ($allDates as $di => $date)
            @php $v = $t['daily'][$date] ?? 0; @endphp
            @if ($v > 0)
                <circle cx="{{ $xFor($di) }}" cy="{{ $yFor($v) }}"
                        r="2.3" fill="{{ $color }}" stroke="#fff" stroke-width="0.7"/>
            @endif
        @endforeach

        {{-- Average horizontal dashed line --}}
        @if ($avg > 0)
            <line x1="{{ $padL + 1 }}" y1="{{ $yAvg }}"
                  x2="{{ $padL + $plotW - 1 }}" y2="{{ $yAvg }}"
                  stroke="{{ $color }}" stroke-width="1.2"
                  stroke-dasharray="5,3" opacity="0.7"/>
            {{-- Average label at right end of the line --}}
            <text x="{{ $padL + $plotW + 3 }}" y="{{ $yAvg + 3.5 }}"
                  font-size="7.5" fill="{{ $color }}" opacity="0.85">{{ $avg }}</text>
        @endif
    @endforeach

    {{-- ── Legend panel ── --}}
    <text x="{{ $legX }}" y="{{ $padT + 1 }}"
          font-size="9" font-weight="bold" fill="#374151">Binôme · Moy/j actif</text>

    @foreach ($tabletData as $li => $t)
        @php $ly = $padT + 15 + $li * 20; @endphp

        {{-- Solid line sample --}}
        <line x1="{{ $legX }}" y1="{{ $ly + 5 }}"
              x2="{{ $legX + 14 }}" y2="{{ $ly + 5 }}"
              stroke="{{ $t['color'] }}" stroke-width="2"/>
        <circle cx="{{ $legX + 7 }}" cy="{{ $ly + 5 }}"
                r="2.5" fill="{{ $t['color'] }}" stroke="#fff" stroke-width="0.5"/>

        {{-- Dashed line sample --}}
        <line x1="{{ $legX + 18 }}" y1="{{ $ly + 5 }}"
              x2="{{ $legX + 30 }}" y2="{{ $ly + 5 }}"
              stroke="{{ $t['color'] }}" stroke-width="1.2" stroke-dasharray="4,2" opacity="0.7"/>

        <text x="{{ $legX + 34 }}" y="{{ $ly + 9 }}"
              font-size="9" fill="#374151">T{{ $t['tablet'] }} — {{ $t['avg'] }}/j</text>
    @endforeach

    {{-- Legend note --}}
    @php $noteY = $padT + 15 + count($tabletData) * 20 + 10; @endphp
    <text x="{{ $legX }}" y="{{ $noteY }}" font-size="8" fill="#94A3B8">— réelle</text>
    <text x="{{ $legX + 35 }}" y="{{ $noteY }}" font-size="8" fill="#94A3B8">· · · moyenne</text>

</svg>
@endif

</body>
</html>
