@php
    $stats = $this->getAlertStats();
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Filtros</x-slot>
        <x-slot name="description">
            Acotan las alertas por municipio e institución. Las capacitaciones sin sesiones no se ven
            afectadas, porque todavía no están asociadas a ninguna institución.
        </x-slot>

        {{ $this->form }}

        <div class="mt-3">
            {{ $this->clearFiltersAction }}
        </div>
    </x-filament::section>

    @if (count($stats) === 0)
        <x-filament::section>
            <div class="flex flex-col items-center gap-2 py-10 text-center">
                <x-filament::icon
                    icon="heroicon-o-check-circle"
                    class="h-10 w-10 text-success-500"
                />

                <p class="text-base font-semibold text-gray-950 dark:text-white">
                    Sin alertas activas
                </p>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay casos pendientes con los filtros seleccionados.
                </p>
            </div>
        </x-filament::section>
    @else
        {{-- Mismas clases de grilla que usa el widget de indicadores, para que
             las tarjetas se vean idénticas a las de esa pantalla. --}}
        <div class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                {{ $stat }}
            @endforeach
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
