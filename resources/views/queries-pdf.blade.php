<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@page {
    margin: 2cm 2cm 2cm 2cm;
    size: A4 portrait;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9.5px;
    color: #000;
    background: #fff;
    line-height: 1.55;
}

/* ── Document header ── */
h1 {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 5px;
}
.doc-meta {
    font-size: 9px;
    margin-bottom: 3px;
}
.doc-summary {
    font-size: 9px;
    margin-bottom: 3px;
}
.doc-hr {
    border: none;
    border-top: 1.5px solid #000;
    margin: 10px 0 14px 0;
}

/* ── Per-query block ── */
.query-block {
    margin-bottom: 16px;
    page-break-inside: avoid;
}
.query-code {
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 0.3px;
    margin-bottom: 3px;
}
.query-sep {
    border: none;
    border-top: 1px solid #000;
    margin-bottom: 6px;
}
ul.qf {
    list-style-type: disc;
    padding-left: 18px;
}
ul.qf li {
    margin-bottom: 2px;
    font-size: 9.5px;
    line-height: 1.5;
}
ul.qf li b {
    font-weight: bold;
}
</style>
</head>
<body>

{{-- ── Header ── --}}
<h1>Liste des queries pour le projet ENDURE-Net</h1>
<p class="doc-meta">Exporté ce {{ now()->format('d/m/Y à H:i') }}</p>
<p class="doc-summary">
    <b>Total :</b> {{ $summary['total'] }} queries
    &nbsp;—&nbsp; <b>Critiques :</b> {{ $summary['critical'] }}
    &nbsp;—&nbsp; <b>Avertissements :</b> {{ $summary['warning'] }}
    &nbsp;—&nbsp; <b>Informatifs :</b> {{ $summary['info'] }}
</p>
@if (!empty($activeFilters))
<p class="doc-summary" style="margin-top:5px">
    <b>Filtres appliqués :</b>
    @foreach ($activeFilters as $key => $value)
        {{ $filterLabels[$key] ?? $key }} = <b>{{ $key === 'severity' ? ($severityLabels[$value] ?? $value) : $value }}</b>@if (!$loop->last) &nbsp;·&nbsp; @endif
    @endforeach
</p>
@endif
<hr class="doc-hr">

{{-- ── Queries ── --}}
@foreach ($queries as $q)
@php
    $sevLabel = match($q['severity']) {
        'critical' => 'Critique',
        'warning'  => 'Avertissement',
        default    => 'Informatif',
    };
@endphp
<div class="query-block">
    <p class="query-code">{{ $q['code'] }}</p>
    <hr class="query-sep">
    <ul class="qf">
        <li><b>Query date :</b> {{ now()->format('Y-m-d') }}</li>
        <li><b>Ménage :</b> {{ $q['household_id'] ?: '—' }}</li>
        @if ($q['tablet'])
            <li><b>Tablette :</b> {{ $q['tablet'] }}</li>
        @endif
        @if ($q['initials'])
            <li><b>Initiales :</b> {{ $q['initials'] }}</li>
        @endif
        <li><b>Événement :</b> {{ $q['event_label'] ?: '—' }}</li>
        <li><b>Formulaire :</b> {{ $q['form_label'] ?: '—' }}</li>
        @if ($q['instance'])
            <li><b>N° Instance :</b> {{ $q['instance'] }}</li>
        @endif
        <li><b>Description :</b> {{ $q['description'] }}</li>
        <li><b>Suggestion :</b> {{ $q['suggestion'] }}</li>
        @if ($q['extra'])
            <li><b>Contexte :</b> {{ $q['extra'] }}</li>
        @endif
        <li><b>Statut :</b> Non résolu</li>
    </ul>
</div>
@endforeach

</body>
</html>
