@php
    /**
     * Cada alerta devuelve un modelo distinto, así que aquí se decide qué
     * columnas tienen sentido para cada una.
     */
    $columns = match ($key) {
        'students_without_characterization',
        'students_without_canvas' => [
            'Estudiante'  => fn ($record) => $record->name,
            'Documento'   => fn ($record) => $record->document_number,
            'Institución' => fn ($record) => $record->educationalInstitution?->display_name ?? '—',
            'Grado'       => fn ($record) => $record->grade ?? '—',
        ],
        'students_low_attendance' => [
            'Estudiante'   => fn ($record) => $record->name,
            'Institución'  => fn ($record) => $record->educationalInstitution?->display_name ?? '—',
            'Asistió a'    => fn ($record) => $record->sessions_attended.' de '.$record->sessions_total,
            '% Asistencia' => fn ($record) => $record->sessions_total > 0
                ? round($record->sessions_attended * 100 / $record->sessions_total, 1).'%'
                : '—',
        ],
        'institutions_without_evaluation' => [
            'Institución' => fn ($record) => $record->display_name,
            'Municipio'   => fn ($record) => $record->city?->name ?? '—',
            'Rector'      => fn ($record) => $record->principal_name ?? '—',
        ],
        'trainings_without_sessions' => [
            'Módulo y Tema' => fn ($record) => $record->name,
            'Registrada'    => fn ($record) => $record->created_at?->format('d/m/Y') ?? '—',
            'Registró'      => fn ($record) => $record->manager?->name ?? '—',
        ],
        default => ['Registro' => fn ($record) => (string) $record->getKey()],
    };
@endphp

<div>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        {{ $records->count() }} {{ $records->count() === 1 ? 'caso' : 'casos' }}
        @if ($records->count() >= 500)
            (se muestran los primeros 500)
        @endif
    </p>

    @if ($records->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay registros que mostrar.</p>
    @else
        <div class="max-h-96 overflow-x-auto overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        @foreach (array_keys($columns) as $heading)
                            {{-- El encabezado necesita fondo propio y z-index: al ser
                                 sticky, sin ellos las filas se le transparentan debajo. --}}
                            <th
                                class="sticky top-0 z-10 whitespace-nowrap border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                            >
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            @foreach ($columns as $resolve)
                                <td class="border-b border-gray-200 px-3 py-2 align-top dark:border-white/10">
                                    {{ $resolve($record) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
