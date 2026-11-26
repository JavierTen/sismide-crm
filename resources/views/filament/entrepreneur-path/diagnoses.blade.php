{{-- Diagnóstico de entrada y de salida lado a lado, con el cambio de puntaje. --}}
@php
    /** @var array{entry: ?array, exit: ?array} $s */
    $s = $getState();
    $change = ($s['entry']['score'] ?? null) !== null && ($s['exit']['score'] ?? null) !== null ? $s['exit']['score'] - $s['entry']['score'] : null;
@endphp

<div class="ep-tab">
    @if ($change !== null)
        <p style="margin-bottom:1rem;">
            <x-filament::badge :color="$change > 0 ? 'success' : ($change < 0 ? 'danger' : 'gray')" :icon="$change > 0 ? 'heroicon-m-arrow-trending-up' : ($change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus')">
                {{ $change > 0 ? 'Mejoró' : ($change < 0 ? 'Bajó' : 'Sin cambio') }}: {{ sprintf('%+d', $change) }} puntos entre la entrada y la salida
            </x-filament::badge>
        </p>
    @endif

    <div class="ep-dx">
        @foreach (['entry' => 'Diagnóstico de entrada', 'exit' => 'Diagnóstico de salida'] as $key => $title)
            @php $d = $s[$key]; @endphp
            <div class="ep-dx-card">
                <div class="ep-label" style="font-size:.8rem;">{{ $title }}</div>

                @if (! $d)
                    <p class="ep-empty">Aún no se ha hecho.</p>
                @else
                    <div style="display:flex; align-items:baseline; gap:.4rem;">
                        <span class="ep-dx-score">{{ $d['score'] ?? '—' }}</span>
                        <span class="ep-muted">/ 100 puntos</span>
                    </div>

                    @if ($d['level'])
                        <span class="ep-level-pill" style="color: {{ $d['level']['color'] }}; background: {{ $d['level']['color'] }}1f; align-self:flex-start;">
                            <span aria-hidden="true" style="width:.5rem; height:.5rem; border-radius:9999px; background: {{ $d['level']['color'] }};"></span>
                            {{ $d['level']['label'] }}
                        </span>
                    @endif

                    <div class="ep-muted" style="font-size:.8rem;">{{ $d['date'] }}{{ $d['manager'] ? ' · ' . $d['manager'] : '' }}</div>

                    @if ($d['news'])
                        <div>
                            <div class="ep-label">Novedad del emprendimiento</div>
                            <div class="ep-value">{{ $d['news'] }}</div>
                        </div>
                    @endif

                    @if ($d['observations'])
                        <div>
                            <div class="ep-label">Observaciones</div>
                            <p class="ep-value" style="white-space:pre-line;">{{ $d['observations'] }}</p>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
