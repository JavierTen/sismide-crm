{{-- Recorrido del emprendedor: las etapas del programa en orden, hechas o pendientes. --}}
@php
    /** @var array<int, array{title: string, done: bool, detail: string, icon: string}> $steps */
    $done = collect($steps)->where('done', true)->count();
@endphp

<section class="ep-journey" aria-label="Recorrido del emprendedor">
    <div class="ep-journey-head">
        <span class="ep-journey-title">Recorrido</span>
        <div class="ep-meter" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($steps) }}" aria-valuenow="{{ $done }}">
            <div style="width: {{ count($steps) ? round($done / count($steps) * 100) : 0 }}%;"></div>
        </div>
        <span class="ep-muted" style="font-size:.8rem; white-space:nowrap;">{{ $done }} de {{ count($steps) }} etapas</span>
    </div>

    <ol class="ep-steps" style="list-style:none; margin:0; padding:0;">
        @foreach ($steps as $i => $step)
            <li class="ep-step {{ $step['done'] ? 'is-done' : '' }}">
                <span class="ep-step-icon" aria-hidden="true">
                    <x-filament::icon :icon="$step['done'] ? 'heroicon-m-check' : $step['icon']" />
                </span>
                <div style="min-width:0;">
                    <div class="ep-step-title">{{ $i + 1 }}. {{ $step['title'] }}</div>
                    <div class="ep-step-detail ep-muted">
                        <span class="sr-only">{{ $step['done'] ? 'Hecho:' : 'Pendiente:' }}</span>{{ $step['detail'] }}
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
</section>
