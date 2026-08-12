<x-filament-panels::page>

    {{-- ── Sección de selección ─────────────────────────────────────────── --}}
    <div class="p-6 bg-white rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-calendar-days class="w-5 h-5 text-primary-500" />
            Seleccionar sesión de capacitación
        </h2>

        <form wire:submit.prevent="loadEntrepreneurs">
            {{ $this->form }}

            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                {{-- Badge de ruta --}}
                @if ($this->data['training_id'] ?? false)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                        <x-heroicon-m-map class="w-3.5 h-3.5" />
                        {{ $this->getRouteLabel() }}
                    </span>
                @else
                    <span></span>
                @endif

                {{-- Botón cargar --}}
                @if ($loaded)
                    <x-filament::button
                        wire:click="resetSearch"
                        type="button"
                        color="gray"
                        icon="heroicon-m-arrow-path"
                    >
                        Nueva búsqueda
                    </x-filament::button>
                @elseif (($this->data['training_id'] ?? false) && ($this->data['city_id'] ?? false) && ($this->data['session_date'] ?? false))
                    <x-filament::button type="submit" icon="heroicon-m-arrow-right" icon-position="after">
                        Cargar emprendedores
                    </x-filament::button>
                @endif
            </div>
        </form>
    </div>

    {{-- ── Checklist de asistencia ─────────────────────────────────────── --}}
    @if ($loaded)
        <div class="bg-white rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">

            {{-- Cabecera con estadísticas --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-primary-500" />
                        Lista de asistencia
                    </h2>

                    {{-- Contadores --}}
                    <div class="flex flex-wrap gap-3 text-sm">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
                            Habilitados: <strong>{{ count($entrepreneurs) }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-success-50 dark:bg-success-900/30 text-success-700 dark:text-success-300 font-medium">
                            Asistentes: <strong>{{ $this->getAttendedCount() }}</strong>
                        </span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-danger-50 dark:bg-danger-900/30 text-danger-700 dark:text-danger-300 font-medium">
                            No asistentes: <strong>{{ count($entrepreneurs) - $this->getAttendedCount() }}</strong>
                        </span>
                        @if (count($entrepreneurs) > 0)
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-info-50 dark:bg-info-900/30 text-info-700 dark:text-info-300 font-medium">
                                Asistencia: <strong>{{ round($this->getAttendedCount() / count($entrepreneurs) * 100) }}%</strong>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Barra de herramientas --}}
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-3">
                {{-- Buscar --}}
                <div class="flex-1 min-w-48">
                    <div class="relative">
                        <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Buscar emprendedor o emprendimiento..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                        />
                    </div>
                </div>

                {{-- Seleccionar / Desmarcar todos --}}
                <button
                    wire:click="toggleAll"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                >
                    @if ($selectAll)
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        Desmarcar todos
                    @else
                        <x-heroicon-o-check class="w-4 h-4" />
                        Seleccionar todos
                    @endif
                </button>
            </div>

            {{-- Tabla de emprendedores --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-900/30">
                        <tr>
                            <th class="px-4 py-3 w-14 text-center">Asistió</th>
                            <th class="px-4 py-3">Emprendedor</th>
                            <th class="px-4 py-3">Emprendimiento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($this->getFilteredEntrepreneurs() as $entrepreneur)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        wire:model.live="attendees.{{ $entrepreneur['id'] }}"
                                        class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $entrepreneur['name'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $entrepreneur['business'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    @if ($search)
                                        No se encontraron resultados para "<strong>{{ $search }}</strong>".
                                    @else
                                        No hay emprendedores en la lista.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pie con botón guardar --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4 bg-gray-50 dark:bg-gray-900/50">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Los emprendedores <strong>sin marcar</strong> quedarán registrados como no asistentes.
                </p>
                <x-filament::button
                    wire:click="saveAttendance"
                    wire:loading.attr="disabled"
                    icon="heroicon-m-check-circle"
                    color="success"
                >
                    <span wire:loading.remove wire:target="saveAttendance">Guardar asistencia</span>
                    <span wire:loading wire:target="saveAttendance">Guardando...</span>
                </x-filament::button>
            </div>
        </div>
    @endif

</x-filament-panels::page>
