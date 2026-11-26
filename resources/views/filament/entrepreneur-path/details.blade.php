{{-- Datos en pares etiqueta / valor, listas como etiquetas y un texto largo opcional. --}}
@php
    /** @var array{intro?: ?string, details?: array, lists?: array, text?: ?array{label: string, value: ?string}, empty?: string}|null $s */
    $s = $getState();
@endphp

<div class="ep-tab">
    @if (! $s)
        <p class="ep-empty">{{ $empty ?? 'Sin información.' }}</p>
    @else
        @if (! empty($s['intro']))
            <p class="ep-muted" style="margin-bottom:1rem;">{{ $s['intro'] }}</p>
        @endif

        <dl class="ep-grid" style="margin:0;">
            @foreach ($s['details'] ?? [] as $item)
                <div>
                    <dt class="ep-label">{{ $item['label'] }}</dt>
                    <dd class="ep-value {{ blank($item['value']) ? 'ep-empty' : '' }}" style="margin:0;">{{ filled($item['value']) ? $item['value'] : '—' }}</dd>
                </div>
            @endforeach
        </dl>

        @foreach ($s['lists'] ?? [] as $list)
            <div class="ep-block">
                <div class="ep-label">{{ $list['label'] }}</div>
                @if ($list['items'])
                    <div class="ep-chips">
                        @foreach ($list['items'] as $chip)
                            <x-filament::badge color="gray">{{ $chip }}</x-filament::badge>
                        @endforeach
                    </div>
                @else
                    <div class="ep-value ep-empty">—</div>
                @endif
            </div>
        @endforeach

        @if (! empty($s['text']))
            <div class="ep-block">
                <div class="ep-label">{{ $s['text']['label'] }}</div>
                <p class="ep-value {{ blank($s['text']['value']) ? 'ep-empty' : '' }}" style="white-space:pre-line; max-width:70ch;">{{ filled($s['text']['value']) ? $s['text']['value'] : 'Sin descripción.' }}</p>
            </div>
        @endif
    @endif
</div>
