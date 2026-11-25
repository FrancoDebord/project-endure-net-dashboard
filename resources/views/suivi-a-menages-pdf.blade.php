<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
@page { margin: 12mm 10mm 14mm 10mm; size: A4 landscape; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9.2px; color:#1E293B; line-height:1.35; }

.page { page-break-after: always; }
.page:last-child { page-break-after: auto; }

.hdr { border-bottom: 3px solid #C41230; padding-bottom: 5px; margin-bottom: 7px; width:100%; border-collapse:collapse; }
.hdr td { vertical-align: bottom; }
.org { font-size:6.5px; font-weight:bold; color:#94A3B8; text-transform:uppercase; letter-spacing:.1em; }
.title { font-size:11px; font-weight:bold; }
.sub { font-size:7px; color:#64748B; margin-top:2px; }
.place { font-size:15px; font-weight:bold; color:#1E293B; text-align:right; }
.arm { display:inline-block; font-size:9px; font-weight:bold; color:#fff; padding:2px 7px; border-radius:3px; margin-left:6px; vertical-align:middle; }
.arm-1 { background:#C41230; } .arm-2 { background:#374151; }
.meta { font-size:7px; color:#64748B; text-align:right; }

h3 { font-size:10.5px; font-weight:bold; margin:8px 0 4px; padding-left:5px; border-left:3px solid #C41230; }
h3 .n { font-weight:normal; color:#64748B; }

table.list { border-collapse:collapse; margin:0 auto; width:94%; table-layout:fixed; }
table.list td { word-wrap:break-word; }
table.list th { background:#1E293B; color:#fff; font-size:7.8px; text-transform:uppercase; letter-spacing:.03em;
                padding:5px 5px; border:1px solid #1E293B; text-align:left; }
table.list td { border:1px solid #94A3B8; padding:4px 5px; vertical-align:top; }
table.list tr.alt td { background:#F8FAFC; }
table.list.b th { background:#0369A1; border-color:#0369A1; }
.mono { font-family: DejaVu Sans Mono, monospace; font-size:8.8px; }
.small { font-size:8.2px; color:#475569; }
.c { text-align:center; }
.blank { background:#fff !important; }
.st { font-size:7.8px; font-weight:bold; padding:1px 3px; border-radius:2px; white-space:nowrap; }
.st-realise { background:#DCFCE7; color:#15803D; }
.st-non_realise { background:#FEE2E2; color:#991B1B; }
.st-en_cours { background:#FEF3C7; color:#92400E; }
.st-non_visite { color:#94A3B8; }
.st-pending { background:#FEF3C7; color:#92400E; }
.st-reserve { background:#E0F2FE; color:#075985; }
.st-visited { background:#DCFCE7; color:#15803D; }
.kobo-yes { font-weight:bold; color:#6D28D9; white-space:nowrap; }
.kobo-done { color:#15803D; }
.kobo-no { color:#CBD5E1; }
.box { display:inline-block; width:10px; height:10px; border:1px solid #475569; margin-right:2px; vertical-align:middle; }
.none { font-size:9px; text-align:center; color:#94A3B8; padding:4px 0 2px 8px; font-style:italic; }
.warn { font-size:8.5px; text-align:center; color:#B45309; margin:3px 0; }

.sign { margin-top:12px; width:100%; border-collapse:collapse; font-size:9px; }
.sign td { padding:4px 6px; }
.line { border-bottom:1px solid #475569; display:inline-block; width:150px; height:10px; }
.empty { padding:20px; text-align:center; color:#94A3B8; font-size:10px; }
@if (!empty($printMode))
@media screen { body { background:#E2E8F0; } .page { background:#fff; width:277mm; margin:10px auto; padding:8mm; box-shadow:0 2px 8px rgba(0,0,0,.15); } }
@media print { * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .noprint { display:none; } }
.noprint { position:sticky; top:0; background:#1E293B; color:#fff; padding:8px 14px; font-size:12px; z-index:5; }
.noprint button { background:#C41230; color:#fff; border:0; padding:5px 14px; border-radius:4px; font-weight:bold; cursor:pointer; margin-left:10px; }
@endif
</style>
</head>
<body>
@php
    $bStates = ['pending' => 'À visiter', 'reserve' => 'Réserve', 'visited' => 'Visité'];
    // Cellule Kobo : « Oui » + statut de l'entretien si le ménage est dans la liste cible Kobo, sinon « Non »
    $koboCell = function (string $id) use ($kobo, $koboLabels) {
        if (!array_key_exists($id, $kobo)) return '<span class="kobo-no">Non</span>';
        $st = $kobo[$id];
        return '<span class="kobo-yes">Oui</span><br><span class="small ' . ($st === 'realise' ? 'kobo-done' : '') . '">'
             . e($st === 'non_visite' ? 'à faire' : ($koboLabels[$st] ?? $st)) . '</span>';
    };
@endphp

@if (!empty($printMode))
<div class="noprint">
    {{ $sections->count() }} village(s)/grappe(s) · {{ $sections->sum(fn($s) => $s['rowsA']->count()) }} ménages Cohorte A
    · {{ $sections->sum(fn($s) => $s['rowsB']->count()) }} ménages Cohorte B — « Imprimer » puis « Enregistrer au format PDF » (paysage).
    <button onclick="window.print()">Imprimer / PDF</button>
</div>
@endif

@forelse ($sections as $sec)
<div class="page">
    <table class="hdr">
        <tr>
            <td>
                <div class="org">AIRID-Africa · CREC · ENDURE-Net (REDCap #46) · {{ $tablet !== '' ? trim(($village !== '' ? $village . ' · ' : '') . 'Tablette ' . $tablet) : ($village !== '' ? $village : $dagLabel) }}</div>
                <div class="title">Suivi Cohorte A — {{ $suiviLabel }} · Ménages {{ $only === 'remaining' ? 'restant à suivre' : 'à suivre' }}</div>
                <div class="sub">{{ $cfg['forms'] }}</div>
            </td>
            <td style="width:300px">
                <div class="place">{{ $sec['cluster'] }}<span class="arm arm-{{ $sec['brasKey'] }}">{{ $sec['bras'] }}</span></div>
                <div class="meta">Village : <b>{{ $sec['village'] }}</b> · DAG : {{ $sec['dags'] }}@if ($tablet !== '') · <b>Tablette {{ $tablet }}</b>@endif</div>
            </td>
        </tr>
    </table>

    {{-- ── Cohorte A ── --}}
    <h3>Cohorte A <span class="n">— {{ $sec['rowsA']->count() }} ménage(s) · {{ $sec['rowsA']->sum(fn($r) => count($r['netCodes'])) }} moustiquaire(s) de l'étude</span></h3>
    @if ($showKobo && $koboError)<div class="warn">⚠ {{ $koboError }} — colonne Kobo non renseignée.</div>@endif
    @if ($sec['rowsA']->isEmpty())
        <div class="none">Aucun ménage Cohorte A {{ $only === 'remaining' ? 'restant à suivre' : '' }} dans cette grappe.</div>
    @else
    <table class="list">
        <thead>
            <tr>
                <th style="width:3.2%">N°</th>
                <th style="width:10.5%">ID ménage</th>
                <th style="width:12.5%">Chef de ménage · Tél.</th>
                <th style="width:12.5%">Point de contact · Tél.</th>
                <th style="width:20%">Membres du ménage (baseline) : nom (sexe, âge)</th>
                <th style="width:13.5%">Moustiquaires (n° : code)</th>
                @if ($showKobo)<th style="width:7.3%">Kobo Accum. MII</th>@endif
                <th style="width:6.5%">Statut actuel</th>
                <th style="width:6.5%">Date visite</th>
                <th style="width:7.5%">Ménage trouvé</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sec['rowsA'] as $i => $r)
            @php $st = $r['current']['status']; @endphp
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                <td class="mono">{{ $r['id'] }}</td>
                <td>{{ $r['chef'] ?: '—' }}<br><span class="small">{{ $r['chefPhone'] }}</span></td>
                <td>{{ $r['contact'] ?: '—' }}<br><span class="small">{{ $r['contactPhone'] }}</span></td>
                <td class="small">@include('partials.hh-members', ['list' => $members[$r['id']] ?? []])</td>
                <td class="small">
                    @forelse ($r['netCodes'] as $num => $code)
                        {{ $num }} : <span class="mono">{{ $code !== '' ? $code : '(sans code)' }}</span>@if (!$loop->last)<br>@endif
                    @empty — @endforelse
                </td>
                @if ($showKobo)<td class="c">{!! $koboCell($r['id']) !!}</td>@endif
                <td class="c"><span class="st st-{{ $st }}">{{ $statusLabels[$st] }}</span></td>
                <td class="blank"></td>
                <td class="blank small"><span class="box"></span>Oui &nbsp;<span class="box"></span>Non</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ── Cohorte B sélectionnés dans la même grappe ── --}}
    <h3>Cohorte B — ménages sélectionnés pour le prélèvement <span class="n">— {{ $sec['rowsB']->count() }} ménage(s) · {{ $sec['rowsB']->sum('toTake') }} moustiquaire(s) à prélever · visiter dans l'ordre de tirage · prélever toutes les moustiquaires du ménage · (n) = réserve</span></h3>
    @if ($sec['rowsB']->isEmpty())
        <div class="none">Aucun ménage Cohorte B sélectionné {{ $only === 'remaining' ? 'restant à visiter ' : '' }}dans cette grappe pour ce suivi.</div>
    @else
    <table class="list b">
        <thead>
            <tr>
                <th style="width:3.6%">Ordre</th>
                <th style="width:10.5%">ID ménage</th>
                <th style="width:11.5%">Chef de ménage · Tél.</th>
                <th style="width:11%">Point de contact · Tél.</th>
                <th style="width:18%">Membres du ménage (baseline) : nom (sexe, âge)</th>
                <th style="width:5%">À prélever</th>
                <th style="width:12.5%">Moustiquaires disponibles</th>
                @if ($showKobo)<th style="width:6.9%">Kobo Accum. MII</th>@endif
                <th style="width:6%">Statut</th>
                <th style="width:5%">Prélevées</th>
                <th style="width:10%">Codes de remplacement</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sec['rowsB'] as $i => $r)
            @php $bk = $r['state'] === 'pending' && $r['reserve'] ? 'reserve' : $r['state']; @endphp
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c"><b>{{ $r['rank'] }}</b></td>
                <td class="mono">{{ $r['id'] }}</td>
                <td>{{ ($r['chef'] ?? '') ?: '—' }}<br><span class="small">{{ $r['chefPhone'] ?? '' }}</span></td>
                <td>{{ ($r['contact'] ?? '') ?: '—' }}<br><span class="small">{{ $r['contactPhone'] ?? '' }}</span></td>
                <td class="small">@include('partials.hh-members', ['list' => $members[$r['id']] ?? []])</td>
                <td class="c">
                    @if ($r['state'] === 'pending')
                        <b style="font-size:11px">{{ $r['reserve'] ? '(' . $r['netsAvailable'] . ')' : $r['toTake'] }}</b>
                    @else — @endif
                </td>
                <td class="small">
                    @forelse ($r['availableCodes'] ?? [] as $num => $code)
                        {{ $num }} : <span class="mono">{{ $code !== '' ? $code : '(sans code)' }}</span>@if (!$loop->last)<br>@endif
                    @empty — @endforelse
                </td>
                @if ($showKobo)<td class="c">{!! $koboCell($r['id']) !!}</td>@endif
                <td class="c"><span class="st st-{{ $bk }}">{{ $bStates[$bk] ?? $bk }}</span></td>
                <td class="{{ $r['state'] === 'visited' ? 'c' : 'blank' }}">@if ($r['state'] === 'visited'){{ $r['tp']['withdrawn'] }}@endif</td>
                <td class="blank"></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

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
