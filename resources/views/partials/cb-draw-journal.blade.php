{{-- Journal des tirages Cohorte B (graines) — $draws : CohortBNetSelectionService::draws() --}}
@php
    $activeDraws   = collect($draws)->whereNull('resetAt');
    $archivedCount = count($draws) - $activeDraws->count();
@endphp
@if (count($draws))
<details class="rounded-lg border border-gray-200 px-4 py-2 text-xs" @if ($activeDraws->isEmpty()) open @endif>
    <summary class="cursor-pointer font-semibold text-gray-700">
        Journal des tirages ({{ count($draws) }}{{ $archivedCount ? ', dont ' . $archivedCount . ' annulé(s) par réinitialisation' : '' }})
        @if ($activeDraws->isNotEmpty())
            — graines actives :
            @foreach ($activeDraws as $d)<span class="font-mono">{{ $d['seed'] }}</span>@if (!$loop->last), @endif @endforeach
        @endif
        @if (collect($draws)->every('verified')) <span class="text-green-700">· ✓ tous reproductibles</span>
        @else <span class="text-red-600">· ⚠ écart au rejeu</span> @endif
    </summary>
    <table class="tbl mt-2">
        <thead><tr><th class="text-left">Lot</th><th class="text-left">Date</th><th class="text-left">Par</th><th>Graine</th>
            <th>Moustiq. à couvrir</th><th>Réserve</th><th>Grappes</th><th>Éligibles</th><th>Retenus</th><th>Rejeu</th><th class="text-left">État</th><th></th></tr></thead>
        <tbody>
        @foreach ($draws as $d)
            <tr class="{{ $d['resetAt'] ? 'row-released' : '' }}">
                <td>{{ $d['batch'] === 1 ? 'Tirage initial' : 'Complément ' . ($d['batch'] - 1) }}</td>
                <td>{{ $d['date']?->format('d/m/Y H:i') }}</td>
                <td>{{ $d['user'] }}</td>
                <td class="text-center font-mono font-bold">{{ $d['seed'] }}</td>
                <td class="text-center">{{ $d['needNets'] }}</td>
                <td class="text-center">{{ $d['reserve'] }}</td>
                <td class="text-center">{{ $d['spread'] ? 'réparties' : 'simple' }}</td>
                <td class="text-center">{{ $d['poolSize'] }} mén. / {{ $d['poolNets'] }} moustiq.</td>
                <td class="text-center">{{ count($d['picked']) }}</td>
                <td class="text-center">{!! $d['verified'] ? '<span class="text-green-700 font-bold">✓ identique</span>' : '<span class="text-red-600 font-bold">✗ différent</span>' !!}</td>
                <td>
                    @if ($d['resetAt'])
                        <span class="st st-released">Annulé</span>
                        <span class="text-[10px]">le {{ $d['resetAt']->format('d/m/Y H:i') }} par {{ $d['resetBy'] }}</span>
                    @else
                        <span class="st st-visited">Actif</span>
                    @endif
                </td>
                <td class="text-right"><a href="{{ route('cb.selection.draw-csv', $d['id']) }}" class="btn btn-secondary btn-xs" title="Ménages éligibles, clés SHA-256 et ménages retenus — pour vérification indépendante">CSV</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="text-[10px] text-gray-400 mt-1">« Rejeu » : le tirage est recalculé à partir de sa graine et de la liste des ménages éligibles enregistrée au moment du tirage.
        Les tirages annulés par une réinitialisation restent consultables pour l'audit.</p>
</details>
@endif
