{{--
    Tabla simple de la ficha. Cada celda es un texto o ['badge' => texto, 'color' => color de Filament];
    las columnas marcadas como numéricas se alinean a la derecha.
--}}
@php
    /** @var array{columns: array<int, array{label: string, numeric?: bool}>, rows: array<int, array<int, mixed>>, empty: string} $s */
    $s = $getState();
@endphp

<div class="ep-tab">
    @if ($s['rows'] === [])
        <p class="ep-empty">{{ $s['empty'] }}</p>
    @else
        <div class="ep-table-wrap">
            <table class="ep-table">
                <thead>
                    <tr>
                        @foreach ($s['columns'] as $column)
                            <th scope="col" style="{{ ! empty($column['numeric']) ? 'text-align:right;' : '' }}">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($s['rows'] as $row)
                        <tr>
                            @foreach ($row as $i => $cell)
                                <td class="{{ ! empty($s['columns'][$i]['numeric']) ? 'ep-num' : '' }}">
                                    @if (is_array($cell))
                                        <x-filament::badge :color="$cell['color'] ?? 'gray'">{{ $cell['badge'] }}</x-filament::badge>
                                    @elseif (blank($cell))
                                        <span class="ep-empty">—</span>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
