{{--
    Recorrido del emprendedor: las etapas del programa en orden, unidas por una
    línea (continua entre etapas hechas, punteada si alguna está pendiente). En
    pantallas angostas pasa a vertical.
--}}
@php
    /** @var array<int, array{title: string, done: bool, detail: string, icon: string, optional?: bool}> $steps */
    $done = collect($steps)->where('done', true)->count();
@endphp

<style>
    .ep-journey { border: 1px solid var(--ep-line); border-radius: 1rem; padding: 1.25rem 1.5rem 1.5rem; }
    .ep-journey-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
    .ep-journey-title { font-size: 1.05rem; font-weight: 700; color: rgb(var(--gray-950)); }
    .dark .ep-journey-title { color: #fff; }
    .ep-journey-count { font-size: .8rem; font-weight: 600; padding: .2rem .75rem; border-radius: 9999px; white-space: nowrap;
        background: rgba(var(--success-500), .14); color: rgb(var(--success-700)); }
    .dark .ep-journey-count { color: rgb(var(--success-400)); }

    .ep-track { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(var(--ep-steps), minmax(0, 1fr)); }
    /* La línea hacia la siguiente etapa: punteada y gris, o continua y verde si las dos están hechas. */
    .ep-stop { position: relative; padding-right: .75rem; --ep-line-style: dashed; --ep-line-color: rgb(var(--gray-300)); }
    .dark .ep-stop { --ep-line-color: rgb(var(--gray-600)); }
    .ep-stop.is-linked { --ep-line-style: solid; --ep-line-color: rgb(var(--success-700)); }
    .dark .ep-stop.is-linked { --ep-line-color: rgb(var(--success-500)); }
    .ep-stop:not(:last-child)::after { content: ''; position: absolute; top: 1.125rem; left: 2.25rem; right: 0; border-top: 2px var(--ep-line-style) var(--ep-line-color); }

    .ep-dot { position: relative; z-index: 1; width: 2.25rem; height: 2.25rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: .9rem; background: #fff; border: 2px dashed rgb(var(--gray-400)); color: rgb(var(--gray-700)); }
    .dark .ep-dot { background: rgb(var(--gray-900)); border-color: rgb(var(--gray-500)); color: rgb(var(--gray-200)); }
    .ep-stop.is-done .ep-dot { border: 0; background: rgb(var(--success-700)); color: #fff; }
    .dark .ep-stop.is-done .ep-dot { background: rgb(var(--success-600)); }
    .ep-dot svg { width: 1.1rem; height: 1.1rem; }

    .ep-stop-body { margin-top: .75rem; }
    .ep-stop-title { font-size: .875rem; font-weight: 600; line-height: 1.2rem; color: rgb(var(--gray-950)); }
    .dark .ep-stop-title { color: #fff; }
    .ep-stop-detail { margin-top: .2rem; font-size: .8rem; line-height: 1.15rem; color: rgb(var(--gray-500)); }
    .dark .ep-stop-detail { color: rgb(var(--gray-400)); }
    .ep-pending { display: inline-block; margin-top: .4rem; padding: .1rem .45rem; border-radius: .375rem; font-size: .68rem; font-weight: 600; letter-spacing: .05em;
        text-transform: uppercase; background: rgba(var(--warning-400), .25); color: rgb(var(--warning-800)); }
    .dark .ep-pending { background: rgba(var(--warning-400), .15); color: rgb(var(--warning-300)); }

    /* Angosto: vertical, con la línea bajando entre los círculos. */
    @media (max-width: 47.99rem) {
        .ep-track { grid-template-columns: 1fr; }
        .ep-stop { display: flex; gap: .85rem; padding: 0 0 1.1rem; }
        .ep-stop:not(:last-child)::after { top: 2.25rem; bottom: 0; left: 1.125rem; right: auto; border-top: 0; border-left: 2px var(--ep-line-style) var(--ep-line-color); }
        .ep-dot { flex: none; }
        .ep-stop-body { margin-top: .4rem; }
    }
</style>

<section class="ep-journey" aria-label="Recorrido del emprendedor">
    <div class="ep-journey-head">
        <span class="ep-journey-title">Recorrido</span>
        <span class="ep-journey-count">{{ $done }} de {{ count($steps) }} etapas</span>
    </div>

    <ol class="ep-track" style="--ep-steps: {{ count($steps) }};">
        @foreach ($steps as $i => $step)
            @php $linked = $step['done'] && ($steps[$i + 1]['done'] ?? false); @endphp
            <li class="ep-stop {{ $step['done'] ? 'is-done' : '' }} {{ $linked ? 'is-linked' : '' }}">
                <span class="ep-dot" aria-hidden="true">
                    @if ($step['done'])
                        <x-filament::icon icon="heroicon-m-check" />
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <div class="ep-stop-body">
                    <div class="ep-stop-title">{{ $step['title'] }}</div>
                    {{-- "Pendiente" ya lo dice la etiqueta; no se repite. --}}
                    @if ($step['done'] || $step['detail'] !== 'Pendiente')
                        <div class="ep-stop-detail">{{ $step['detail'] }}</div>
                    @endif
                    {{-- Las etapas opcionales (ferias) no se marcan como pendientes. --}}
                    @if (! $step['done'] && empty($step['optional']))
                        <span class="ep-pending">Pendiente</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>
