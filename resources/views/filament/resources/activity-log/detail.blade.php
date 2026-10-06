@php
    /** @var \App\Models\Activity $activity */
    $format = function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    };

    $properties = $activity->properties ?? collect();
    $changes    = $activity->changes_list;
    $resolver   = new \App\Support\ActivityFieldResolver($activity->subject_type);
    $isUpdate   = $activity->event === 'updated';

    $meta = [
        'Usuario'  => $activity->causer_name,
        'Rol'      => $activity->loginLog?->roles_label ?? '—',
        'Fecha'    => $activity->created_at?->format('d/m/Y h:i:s A'),
        'Panel'    => \App\Models\LoginLog::PANEL_LABELS[$activity->panel] ?? '—',
        'Registro' => $activity->subject_type ? $activity->subject_name : '—',
        'IP'       => $activity->ip_address ?? '—',
    ];

    if ($activity->related_entrepreneur_name) {
        $meta = array_merge(
            array_slice($meta, 0, 5, true),
            ['Emprendedor' => $activity->related_entrepreneur_name],
            array_slice($meta, 5, null, true),
        );
    }
@endphp

<div class="space-y-6">
    <dl class="grid md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        @foreach ($meta as $label => $value)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($activity->event === 'exported')
        <div class="text-sm">
            <p class="mb-2 font-semibold text-gray-950 dark:text-white">{{ $activity->description }}</p>

            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-3">
                @foreach ([
                    'Archivo'    => $properties['file'] ?? null,
                    'Registros'  => $properties['records'] ?? null,
                    'Archivos'   => $properties['files'] ?? null,
                    'Selección'  => $properties['selection'] ?? null,
                    'Búsqueda'   => $properties['search'] ?? null,
                ] as $label => $value)
                    @if (filled($value))
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $format($value) }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if (filled($properties['filters'] ?? null))
                <p class="mt-3 mb-2 text-gray-500 dark:text-gray-400">Filtros aplicados</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs dark:bg-white/5">{{ json_encode($properties['filters'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            @endif
        </div>
    @elseif ($activity->event === 'attendance_updated')
        <div class="space-y-4 text-sm">
            <p class="font-semibold text-gray-950 dark:text-white">{{ $activity->description }}</p>

            @foreach (['marked' => 'Marcados como asistentes', 'unmarked' => 'Desmarcados'] as $key => $label)
                @if (filled($properties[$key] ?? null))
                    <div>
                        <p class="mb-2 text-gray-500 dark:text-gray-400">{{ $label }} ({{ count($properties[$key]) }})</p>
                        <p class="text-gray-950 dark:text-white">{{ implode(', ', $properties[$key]) }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @elseif ($activity->event === 'relation_updated')
        <div class="space-y-4 text-sm">
            <p class="font-semibold text-gray-950 dark:text-white">{{ $activity->description }}</p>

            @foreach (['added' => 'Agregados', 'removed' => 'Quitados'] as $key => $title)
                @if (filled($properties[$key] ?? null))
                    <div>
                        <p class="mb-2 text-gray-500 dark:text-gray-400">{{ $title }} ({{ count($properties[$key]) }})</p>
                        <p class="text-gray-950 dark:text-white">{{ implode(', ', $properties[$key]) }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @elseif ($activity->event === 'evaluation_deleted')
        <div class="space-y-4 text-sm">
            <p class="font-semibold text-gray-950 dark:text-white">{{ $activity->description }}</p>

            @if (filled($properties['ratings'] ?? null))
                <p class="text-gray-500 dark:text-gray-400">Calificaciones que tenía antes de eliminarla</p>

                <div class="max-h-96 overflow-x-auto overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="sticky top-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">Pregunta</th>
                                <th class="sticky top-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($properties['ratings'] as $rating)
                                <tr>
                                    <td class="border-b border-gray-200 px-3 py-2 align-top dark:border-white/10">{{ $rating['question'] ?? '—' }}</td>
                                    <td class="border-b border-gray-200 px-3 py-2 align-top font-medium dark:border-white/10">{{ $rating['score'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif (in_array($activity->event, ['cascade_deleted', 'cascade_restored'], true))
        <div class="space-y-4 text-sm">
            <p class="font-semibold text-gray-950 dark:text-white">{{ $activity->description }}</p>

            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-3">
                @foreach (($properties['affected'] ?? []) as $label => $count)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::ucfirst($label) }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $count }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @elseif (count($changes) > 0)
        <div class="max-h-96 overflow-x-auto overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="sticky top-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">Campo</th>
                        @if ($isUpdate || $activity->event !== 'created')
                            <th class="sticky top-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                {{ $isUpdate ? 'Antes' : 'Valor' }}
                            </th>
                        @endif
                        @if ($isUpdate || $activity->event === 'created')
                            <th class="sticky top-0 z-10 border-b border-gray-200 bg-white px-3 py-2 text-left font-semibold text-gray-950 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                                {{ $isUpdate ? 'Después' : 'Valor' }}
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($changes as $field => $change)
                        <tr>
                            <td class="border-b border-gray-200 px-3 py-2 align-top dark:border-white/10">
                                @if ($resolver->relatedModelFor($field))
                                    <span class="font-medium text-gray-950 dark:text-white">{{ $resolver->label($field) }}</span>
                                    <span class="block font-mono text-xs text-gray-500 dark:text-gray-400">{{ $field }}</span>
                                @else
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $field }}</span>
                                @endif
                            </td>
                            @if ($isUpdate || $activity->event !== 'created')
                                <td class="border-b border-gray-200 px-3 py-2 align-top dark:border-white/10">{{ $format($resolver->value($field, $change['old'])) }}</td>
                            @endif
                            @if ($isUpdate || $activity->event === 'created')
                                <td class="border-b border-gray-200 px-3 py-2 align-top dark:border-white/10">{{ $format($resolver->value($field, $change['new'])) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Esta acción no registró cambios de campos.</p>
    @endif
</div>
