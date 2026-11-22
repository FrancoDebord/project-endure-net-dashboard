<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
@page { margin: 12mm 10mm 14mm 10mm; size: A4 landscape; }
body { font-family: DejaVu Sans, sans-serif; font-size: 7.6px; color:#1E293B; line-height:1.35; }

.page { page-break-after: always; }
.page:last-child { page-break-after: auto; }

.hdr { border-bottom: 3px solid #C41230; padding-bottom: 5px; margin-bottom: 7px; width:100%; border-collapse:collapse; }
.hdr td { vertical-align: bottom; }
.org { font-size:6.5px; font-weight:bold; color:#94A3B8; text-transform:uppercase; letter-spacing:.1em; }
.title { font-size:13px; font-weight:bold; }
.sub { font-size:7.5px; color:#64748B; margin-top:2px; }
.dag { font-size:15px; font-weight:bold; color:#C41230; text-align:right; }
.meta { font-size:7px; color:#64748B; text-align:right; }

table.list { width:100%; border-collapse:collapse; }
table.list th { background:#1E293B; color:#fff; font-size:6.6px; text-transform:uppercase; letter-spacing:.03em;
                padding:4px 3px; border:1px solid #1E293B; text-align:left; }
table.list td { border:1px solid #94A3B8; padding:3px 3px; vertical-align:top; }
table.list tr.alt td { background:#F8FAFC; }
.mono { font-family: DejaVu Sans Mono, monospace; font-size:7.2px; }
.small { font-size:6.6px; color:#475569; }
.c { text-align:center; }
.blank { background:#fff !important; }
.pd { color:#C41230; font-weight:bold; }
.g2 { color:#374151; font-weight:bold; }
.st { font-size:6.4px; font-weight:bold; padding:1px 3px; border-radius:2px; }
.st-realise { background:#DCFCE7; color:#15803D; }
.st-non_realise { background:#FEE2E2; color:#991B1B; }
.st-en_cours { background:#FEF3C7; color:#92400E; }
.st-non_visite { color:#94A3B8; }
.box { display:inline-block; width:8px; height:8px; border:1px solid #475569; margin-right:2px; vertical-align:middle; }

.sign { margin-top:10px; width:100%; border-collapse:collapse; font-size:7.5px; }
.sign td { padding:4px 6px; }
.line { border-bottom:1px solid #475569; display:inline-block; width:150px; height:10px; }
.empty { padding:20px; text-align:center; color:#94A3B8; font-size:10px; }
@if (!empty($printMode))
/* Version navigateur (Imprimer → Enregistrer en PDF) */
@media screen { body { background:#E2E8F0; } .page { background:#fff; width:277mm; margin:10px auto; padding:8mm; box-shadow:0 2px 8px rgba(0,0,0,.15); } }
@media print { * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .noprint { display:none; } }
.noprint { position:sticky; top:0; background:#1E293B; color:#fff; padding:8px 14px; font-size:12px; z-index:5; }
.noprint button { background:#C41230; color:#fff; border:0; padding:5px 14px; border-radius:4px; font-weight:bold; cursor:pointer; margin-left:10px; }
@endif
</style>
</head>
<body>
@if (!empty($printMode))
<div class="noprint">
    {{ $groups->count() }} DAG · {{ $groups->sum(fn($g) => $g['rows']->count()) }} ménages — utilisez « Imprimer » puis « Enregistrer au format PDF » (paysage).
    <button onclick="window.print()">Imprimer / PDF</button>
</div>
@endif


@forelse ($groups as $g)
<div class="page">
    <table class="hdr">
        <tr>
            <td>
                <div class="org">AIRID-Africa · CREC · ENDURE-Net (REDCap #46)</div>
                <div class="title">Suivi Cohorte {{ $cfg['letter'] }} — {{ $suiviLabel }} · Liste des ménages {{ $only === 'remaining' ? 'restant à suivre' : 'à suivre' }}</div>
                <div class="sub">{{ $cfg['forms'] }}</div>
            </td>
            <td style="width:220px">
                <div class="dag">{{ $g['label'] }}</div>
                <div class="meta">{{ $g['rows']->count() }} ménage(s) · {{ $g['rows']->sum(fn($r) => count($r['netCodes'])) }} moustiquaire(s) de l'étude</div>
            </td>
        </tr>
    </table>

    @foreach ($g['rows']->chunk(17) as $ci => $chunk)
    @if ($ci > 0)<div style="page-break-before: always;"></div>
    <div class="meta" style="text-align:left;margin-bottom:4px"><b style="color:#C41230">{{ $g['label'] }}</b> · Suivi Cohorte {{ $cfg['letter'] }} — {{ $suiviLabel }} (suite)</div>@endif
    <table class="list">
        <thead>
            <tr>
                <th style="width:18px">N°</th>
                <th style="width:88px">ID ménage</th>
                <th style="width:72px">Village / Grappe</th>
                <th style="width:48px">Bras</th>
                <th style="width:110px">Chef de ménage · Tél.</th>
                <th style="width:110px">Point de contact · Tél.</th>
                <th>{{ $cfg['netsHeader'] }}</th>
                <th style="width:50px">Statut actuel</th>
                <th style="width:52px">Date visite</th>
                <th style="width:58px">Ménage trouvé</th>
                @if ($cfg['replacementColumn'])<th style="width:120px">Codes de remplacement</th>@endif
                <th style="width:110px">Observations</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chunk as $i => $r)
            @php $st = $r['current']['status']; @endphp
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                <td class="mono">{{ $r['id'] }}</td>
                <td>{{ $r['cluster'] }}</td>
                <td class="{{ $r['brasKey'] === '1' ? 'pd' : 'g2' }}">{{ $r['brasKey'] === '1' ? 'PD' : ($r['brasKey'] === '2' ? 'G2' : '—') }}</td>
                <td>{{ $r['chef'] ?: '—' }}<br><span class="small">{{ $r['chefPhone'] }}</span></td>
                <td>{{ $r['contact'] ?: '—' }}<br><span class="small">{{ $r['contactPhone'] }}</span></td>
                <td class="small">
                    <b>{{ count($r['netCodes']) }}</b> —
                    @foreach ($r['netCodes'] as $num => $code){{ $num }} : <span class="mono">{{ $code !== '' ? $code : '(sans code)' }}</span>@if (!$loop->last) · @endif @endforeach
                </td>
                <td class="c"><span class="st st-{{ $st }}">{{ $statusLabels[$st] }}</span></td>
                <td class="blank"></td>
                <td class="blank small"><span class="box"></span>Oui &nbsp;<span class="box"></span>Non</td>
                @if ($cfg['replacementColumn'])<td class="blank"></td>@endif
                <td class="blank"></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach

    <table class="sign">
        <tr>
            <td>Enquêteur(s) : <span class="line"></span></td>
            <td>Superviseur : <span class="line"></span></td>
            <td>Date : <span class="line" style="width:90px"></span></td>
            <td>Signature : <span class="line" style="width:110px"></span></td>
        </tr>
    </table>
</div>
@empty
<div class="empty">Aucun ménage pour cette sélection.</div>
@endforelse

</body>
</html>
