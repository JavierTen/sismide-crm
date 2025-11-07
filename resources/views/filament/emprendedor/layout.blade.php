<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'livewire',
    ])

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{ $slot }}
</x-filament-panels::layout.base>
