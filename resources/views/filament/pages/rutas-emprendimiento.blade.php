<x-filament-panels::page>
    <div class="space-y-8">

        @php
            $routesEntry = $this->getRoutesDataEntry();
            $routesExit = $this->getRoutesDataExit();
            $radarDataEntry = $this->getRadarDataByCityEntry();
            $radarDataExit = $this->getRadarDataByCityExit();
            $colors = [
                'ruta1' => ['bg' => '#F59E0B', 'border' => '#D97706'],
                'ruta2' => ['bg' => '#3B82F6', 'border' => '#2563EB'],
                'ruta3' => ['bg' => '#10B981', 'border' => '#059669'],
            ];
        @endphp

        {{-- ========== RESUMEN GENERAL ENTRADA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
            Resumen General - Entrada
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($routesEntry as $routeKey => $route)
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

        <br>

        {{-- ========== RESUMEN GENERAL SALIDA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                Resumen General - Salida
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($routesExit as $routeKey => $route)
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

        <br>

        {{-- ========== RADARES POR MUNICIPIO (COMPARATIVOS LADO A LADO) ========== --}}
        @php
            $allCities = array_unique(array_merge(
                array_keys($radarDataEntry),
                array_keys($radarDataExit)
            ));
            sort($allCities);
        @endphp

        @if(count($allCities) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    Comparativa por Municipio - Entrada vs Salida
                </x-slot>

                <div class="space-y-8">
                    @foreach($allCities as $cityName)
                        @php
                            $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                            $hasEntry = isset($radarDataEntry[$cityName]);
                            $hasExit = isset($radarDataExit[$cityName]);
                        @endphp

                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
                            <h3 class="text-xl font-bold mb-6 text-center text-gray-900 dark:text-white">
                                {{ $cityName }}
                            </h3>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                                {{-- ENTRADA --}}
                                <div class="space-y-4">
                                    <div class="text-center">
                                        <span class="inline-block px-4 py-2 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 rounded-lg font-semibold">
                                             Entrada
                                        </span>
                                    </div>

                                    @if($hasEntry)
                                        <div style="height: 300px; position: relative;">
                                            <canvas id="radar-entry-{{ $citySlug }}"></canvas>
                                        </div>

                                        <div class="space-y-1 text-sm">
                                            @foreach(['ruta1' => 'Ruta 1', 'ruta2' => 'Ruta 2', 'ruta3' => 'Ruta 3'] as $rutaKey => $rutaLabel)
                                                @if($radarDataEntry[$cityName][$rutaKey]['count'] > 0)
                                                    <p style="color: {{ $colors[$rutaKey]['bg'] }}">
                                                        <strong>{{ $rutaLabel }}:</strong> {{ $radarDataEntry[$cityName][$rutaKey]['count'] }} emprendimientos (Promedio: {{ $radarDataEntry[$cityName][$rutaKey]['avg'] }} pts)
                                                    </p>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-64 text-gray-500">
                                            Sin datos de entrada
                                        </div>
                                    @endif
                                </div>

                                {{-- SALIDA --}}
                                <div class="space-y-4">
                                    <div class="text-center">
                                        <span class="inline-block px-4 py-2 bg-success-100 dark:bg-success-900 text-success-800 dark:text-success-200 rounded-lg font-semibold">
                                             Salida
                                        </span>
                                    </div>

                                    @if($hasExit)
                                        <div style="height: 300px; position: relative;">
                                            <canvas id="radar-exit-{{ $citySlug }}"></canvas>
                                        </div>

                                        <div class="space-y-1 text-sm">
                                            @foreach(['ruta1' => 'Ruta 1', 'ruta2' => 'Ruta 2', 'ruta3' => 'Ruta 3'] as $rutaKey => $rutaLabel)
                                                @if($radarDataExit[$cityName][$rutaKey]['count'] > 0)
                                                    <p style="color: {{ $colors[$rutaKey]['bg'] }}">
                                                        <strong>{{ $rutaLabel }}:</strong> {{ $radarDataExit[$cityName][$rutaKey]['count'] }} emprendimientos (Promedio: {{ $radarDataExit[$cityName][$rutaKey]['avg'] }} pts)
                                                    </p>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-64 text-gray-500">
                                            Sin datos de salida
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <br>
                    @endforeach
                </div>
            </x-filament::section>
            <br>
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

        function createSlug(text) {
            return text
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function renderRadarChart(canvasId, cityData, backgroundColor, borderColor) {
            const ctx = document.getElementById(canvasId);

            if (!ctx) return;

            if (radarCharts[canvasId]) {
                radarCharts[canvasId].destroy();
            }

            radarCharts[canvasId] = new Chart(ctx, {
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
                        backgroundColor: backgroundColor,
                        borderColor: borderColor,
                        borderWidth: 2,
                        pointBackgroundColor: borderColor,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: borderColor,
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
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderAllRadarCharts() {
            // Renderizar gráficas de ENTRADA (azul)
            const radarDataEntry = @json($this->getRadarDataByCityEntry());
            Object.keys(radarDataEntry).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const cityData = radarDataEntry[cityName];
                renderRadarChart(
                    `radar-entry-${citySlug}`,
                    cityData,
                    'rgba(59, 130, 246, 0.2)',  // Azul
                    '#3B82F6'
                );
            });

            // Renderizar gráficas de SALIDA (verde)
            const radarDataExit = @json($this->getRadarDataByCityExit());
            Object.keys(radarDataExit).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const cityData = radarDataExit[cityName];
                renderRadarChart(
                    `radar-exit-${citySlug}`,
                    cityData,
                    'rgba(16, 185, 129, 0.2)',  // Verde
                    '#10B981'
                );
            });
        }

        document.addEventListener('DOMContentLoaded', renderAllRadarCharts);
    </script>
    @endpush
</x-filament-panels::page>
