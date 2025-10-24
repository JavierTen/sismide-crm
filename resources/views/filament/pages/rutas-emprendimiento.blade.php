<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Resumen Total -->
        <x-filament::section>
            <x-slot name="heading">
                Resumen General
            </x-slot>

            @php
                $routes = $this->getRoutesData();
                $colors = [
                    'ruta1' => ['bg' => '#F59E0B', 'border' => '#D97706'],
                    'ruta2' => ['bg' => '#3B82F6', 'border' => '#2563EB'],
                    'ruta3' => ['bg' => '#10B981', 'border' => '#059669'],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($routes as $routeKey => $route)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $route['label'] }}</p>
                        <p class="text-3xl font-bold" style="color: {{ $colors[$routeKey]['bg'] }}">
                            {{ $route['total'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Gráficas de Radar por Municipio -->
        @php
            $radarData = $this->getRadarDataByCity();
        @endphp

        @if(count($radarData) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($radarData as $cityName => $cityData)
                    @php
                        $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                    @endphp

                    <x-filament::section>
                        <x-slot name="heading">
                            {{ $cityName }}
                        </x-slot>

                        <div style="height: 400px; position: relative;">
                            <canvas id="radar-{{ $citySlug }}"></canvas>
                        </div>

                        <!-- Detalle de emprendimientos -->
                        <div class="mt-4 space-y-2">
                            @foreach(['ruta1' => 'Ruta 1', 'ruta2' => 'Ruta 2', 'ruta3' => 'Ruta 3'] as $rutaKey => $rutaLabel)
                                @if($cityData[$rutaKey]['count'] > 0)
                                    <div class="text-sm">
                                        <p class="font-semibold" style="color: {{ $colors[$rutaKey]['bg'] }}">
                                            {{ $rutaLabel }}: {{ $cityData[$rutaKey]['count'] }} emprendimientos (Promedio: {{ $cityData[$rutaKey]['avg'] }} pts)
                                        </p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @else
            <x-filament::section>
                <p class="text-center text-gray-500 py-8">No hay datos disponibles</p>
            </x-filament::section>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let radarCharts = {};

        // Función mejorada para crear slug
        function createSlug(text) {
            return text
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function renderRadarCharts() {
            const radarData = @json($this->getRadarDataByCity());

            Object.keys(radarData).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const ctx = document.getElementById(`radar-${citySlug}`);
                const cityData = radarData[cityName];

                if (radarCharts[citySlug]) {
                    radarCharts[citySlug].destroy();
                }

                if (ctx) {
                    radarCharts[citySlug] = new Chart(ctx, {
                        type: 'radar',
                        data: {
                            labels: ['Ruta 1 (0-30)', 'Ruta 2 (31-70)', 'Ruta 3 (71-100)'],
                            datasets: [{
                                label: 'Puntaje Promedio',
                                data: [
                                    cityData.ruta1.avg,
                                    cityData.ruta2.avg,
                                    cityData.ruta3.avg
                                ],
                                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                                borderColor: '#6366F1',
                                borderWidth: 2,
                                pointBackgroundColor: '#6366F1',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: '#6366F1',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += context.parsed.r.toFixed(1) + ' pts';
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        stepSize: 20
                                    },
                                    pointLabels: {
                                        font: {
                                            size: 11
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', renderRadarCharts);
    </script>
    @endpush
</x-filament-panels::page>
