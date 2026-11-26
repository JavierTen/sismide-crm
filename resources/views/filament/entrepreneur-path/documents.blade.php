{{-- Archivos y enlaces del emprendedor, agrupados por etapa. Se abren en otra pestaña. --}}
@php
    /** @var array<string, array<int, array{label: string, url: string, icon: string}>> $groups */
    $groups = $getState() ?? []; // Filament entrega una lista vacía como null
@endphp

<div class="ep-tab">
    @if ($groups === [])
        <p class="ep-empty">El emprendedor aún no tiene documentos ni evidencias cargadas.</p>
    @else
        <div class="ep-docs">
            @foreach ($groups as $group => $docs)
                <section>
                    <div class="ep-label">{{ $group }} <span class="ep-muted">({{ count($docs) }})</span></div>
                    <div class="ep-doc-list">
                        @foreach ($docs as $doc)
                            <a class="ep-doc" href="{{ $doc['url'] }}" target="_blank" rel="noopener">
                                <x-filament::icon :icon="$doc['icon']" />
                                {{ $doc['label'] }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
