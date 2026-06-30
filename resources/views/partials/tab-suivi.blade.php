    <div id="tab-suivi" class="tab-panel">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden suivi-bloc">

            <div class="flex items-center overflow-x-auto border-b border-gray-100 px-4 gap-0">
                @foreach ([
                    'b-suivi6'  => '6 mois',
                    'b-suivi12' => '12 mois',
                    'b-suivi18' => '18 mois',
                    'b-suivi24' => '24 mois',
                    'b-suivi36' => '36 mois',
                ] as $tpKey => $tpLabel)
                    <button class="suivi-tp-btn {{ $loop->first ? 'active' : '' }}"
                            onclick="showSuiviTab('{{ $tpKey }}', this)">
                        {{ $tpLabel }}
                    </button>
                @endforeach
            </div>

            @foreach ([
                'b-suivi6'  => ['fk' => 'suivi6',  'label' => 'Suivi 6 mois'],
                'b-suivi12' => ['fk' => 'suivi12', 'label' => 'Suivi 12 mois'],
                'b-suivi18' => ['fk' => 'suivi18', 'label' => 'Suivi 18 mois'],
                'b-suivi24' => ['fk' => 'suivi24', 'label' => 'Suivi 24 mois'],
                'b-suivi36' => ['fk' => 'suivi36', 'label' => 'Suivi 36 mois'],
            ] as $tpId => $tp)
                <div id="suivitp-{{ $tpId }}"
                     class="suivi-tp-panel {{ $loop->first ? 'active' : '' }} p-6 space-y-6">
                    @include('partials.tab-followup', [
                        'data'   => $followUp[$tp['fk']] ?? [],
                        'label'  => $tp['label'],
                        'tabKey' => str_replace('-', '_', $tpId),
                        'cohort' => 'B',
                    ])
                </div>
            @endforeach

        </div>

<script>
if (typeof showSuiviTab === 'undefined') {
    window.showSuiviTab = function(key, btn) {
        const bloc = btn.closest('.suivi-bloc');
        if (!bloc) return;
        bloc.querySelectorAll('.suivi-tp-panel').forEach(p => p.classList.remove('active'));
        bloc.querySelectorAll('.suivi-tp-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('suivitp-' + key)?.classList.add('active');
        btn.classList.add('active');
    };
}
if (typeof filterFwTable === 'undefined') {
    window.filterFwTable = function(input, key) {
        const q   = input.value.toLowerCase();
        const tid = 'fwTable-' + key;
        document.querySelectorAll('#' + tid + ' tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    };
}
</script>

    </div>{{-- /tab-suivi --}}
