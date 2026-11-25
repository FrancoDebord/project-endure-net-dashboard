{{-- Membres du ménage à la baseline — $list : CohortHouseholds::baselineMembers()[household_id] --}}
@forelse ($list ?? [] as $m)
    <span>{{ $m['num'] }}. {{ $m['name'] }}</span> <span style="color:#64748B">({{ $m['sex'] }}, {{ $m['age'] }})</span>@if (!$loop->last)<br>@endif
@empty
    <span style="color:#94A3B8">—</span>
@endforelse
