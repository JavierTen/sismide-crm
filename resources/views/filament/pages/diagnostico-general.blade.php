<x-filament-panels::page>
    <div class="space-y-8">
        @php
            $citiesDataEntry = $this->getEntrepreneursDataByCitiesEntry();
            $citiesDataExit = $this->getEntrepreneursDataByCitiesExit();

            // Calcular totales ENTRADA
            $ruta1TotalEntry = 0;
            $ruta2TotalEntry = 0;
            $ruta3TotalEntry = 0;

            foreach($citiesDataEntry as $cityName => $entrepreneursData) {
                foreach($entrepreneursData as $entrepreneur) {
                    if ($entrepreneur['total_score'] >= 0 && $entrepreneur['total_score'] <= 50) {
                        $ruta1TotalEntry++;
                    } elseif ($entrepreneur['total_score'] >= 51 && $entrepreneur['total_score'] <= 85) {
                        $ruta2TotalEntry++;
                    } elseif ($entrepreneur['total_score'] >= 86 && $entrepreneur['total_score'] <= 100) {
                        $ruta3TotalEntry++;
                    }
                }
            }

            // Calcular totales SALIDA
            $ruta1TotalExit = 0;
            $ruta2TotalExit = 0;
            $ruta3TotalExit = 0;

            foreach($citiesDataExit as $cityName => $entrepreneursData) {
                foreach($entrepreneursData as $entrepreneur) {
                    if ($entrepreneur['total_score'] >= 0 && $entrepreneur['total_score'] <= 50) {
                        $ruta1TotalExit++;
                    } elseif ($entrepreneur['total_score'] >= 51 && $entrepreneur['total_score'] <= 85) {
                        $ruta2TotalExit++;
                    } elseif ($entrepreneur['total_score'] >= 86 && $entrepreneur['total_score'] <= 100) {
                        $ruta3TotalExit++;
                    }
                }
            }
        @endphp

        {{-- ========== RESUMEN GENERAL ENTRADA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                Resumen General - Entrada
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 1: Pre-emprendimiento y validación temprana</p>
                    <p class="text-3xl font-bold text-orange-500">{{ $ruta1TotalEntry }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 2: Consolidación</p>
                    <p class="text-3xl font-bold text-blue-500">{{ $ruta2TotalEntry }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 3: Escalamiento</p>
                    <p class="text-3xl font-bold text-green-500">{{ $ruta3TotalEntry }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>
            </div>
        </x-filament::section>

        {{-- ========== RESUMEN GENERAL SALIDA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                Resumen General - Salida
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 1: Pre-emprendimiento y validación temprana</p>
                    <p class="text-3xl font-bold text-orange-500">{{ $ruta1TotalExit }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 2: Consolidación</p>
                    <p class="text-3xl font-bold text-blue-500">{{ $ruta2TotalExit }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 3: Escalamiento</p>
                    <p class="text-3xl font-bold text-green-500">{{ $ruta3TotalExit }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>
            </div>
        </x-filament::section>

        {{-- ========== GRÁFICOS POR MUNICIPIO (ENTRADA Y SALIDA UNO DEBAJO DEL OTRO) ========== --}}
        @php
            $allCities = array_unique(array_merge(
                array_keys($citiesDataEntry),
                array_keys($citiesDataExit)
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
                            $hasEntry = isset($citiesDataEntry[$cityName]);
                            $hasExit = isset($citiesDataExit[$cityName]);
                        @endphp

                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
                            <h3 class="text-xl font-bold mb-6 text-center text-gray-900 dark:text-white">
                                {{ $cityName }}
                            </h3>

                            {{-- ENTRADA --}}
                            <div class="space-y-4 mb-8">
                                <div class="text-center">
                                    <span class="inline-block px-4 py-2 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 rounded-lg font-semibold">
                                        Entrada
                                    </span>
                                </div>

                                @if($hasEntry)
                                    <div style="height: 600px; position: relative;">
                                        <canvas id="radar-entry-{{ $citySlug }}"></canvas>
                                    </div>

                                    <!-- Leyenda de emprendimientos ENTRADA -->
                                    <div class="mt-6 space-y-2">
                                        <h4 class="text-sm font-semibold mb-3">Emprendimientos Graficados (Entrada)</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                            @foreach($citiesDataEntry[$cityName] as $entrepreneur)
                                                <div class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-900 rounded">
                                                    <div class="w-4 h-4 rounded flex-shrink-0" style="background-color: {{ $entrepreneur['borderColor'] }}"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium truncate">{{ $entrepreneur['name'] }}</p>
                                                        <p class="text-xs dark:text-primary-400 truncate font-medium">{{ $entrepreneur['business_name'] }}</p>
                                                        <p class="text-xs text-gray-500">{{ explode(' - ', $entrepreneur['route'])[0] }}</p>
                                                        <p class="text-xs text-gray-600 font-semibold">Puntaje: {{ $entrepreneur['total_score'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
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
                                    <div style="height: 600px; position: relative;">
                                        <canvas id="radar-exit-{{ $citySlug }}"></canvas>
                                    </div>

                                    <!-- Leyenda de emprendimientos SALIDA -->
                                    <div class="mt-6 space-y-2">
                                        <h4 class="text-sm font-semibold mb-3">Emprendimientos Graficados (Salida)</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                            @foreach($citiesDataExit[$cityName] as $entrepreneur)
                                                <div class="flex items-center space-x-2 p-2 bg-white dark:bg-gray-900 rounded">
                                                    <div class="w-4 h-4 rounded flex-shrink-0" style="background-color: {{ $entrepreneur['borderColor'] }}"></div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium truncate">{{ $entrepreneur['name'] }}</p>
                                                        <p class="text-xs dark:text-primary-400 truncate font-medium">{{ $entrepreneur['business_name'] }}</p>
                                                        <p class="text-xs text-gray-500">{{ explode(' - ', $entrepreneur['route'])[0] }}</p>
                                                        <p class="text-xs text-gray-600 font-semibold">Puntaje: {{ $entrepreneur['total_score'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center h-64 text-gray-500">
                                        Sin datos de salida
                                    </div>
                                @endif
                            </div>
                        </div>
                        <br>
                    @endforeach
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <p class="text-center text-gray-500 py-8">No hay diagnósticos disponibles</p>
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

        function renderRadarChart(canvasId, entrepreneursData) {
            const ctx = document.getElementById(canvasId);

            if (!ctx) return;

            if (radarCharts[canvasId]) {
                radarCharts[canvasId].destroy();
            }

            const datasets = entrepreneursData.map((entrepreneur) => {
                return {
                    label: entrepreneur.name + ' (' + entrepreneur.total_score + ' pts)',
                    data: [
                        entrepreneur.scores.administrative,
                        entrepreneur.scores.financial,
                        entrepreneur.scores.production,
                        entrepreneur.scores.market,
                        entrepreneur.scores.technology
                    ],
                    backgroundColor: entrepreneur.color,
                    borderColor: entrepreneur.borderColor,
                    borderWidth: 2,
                    pointBackgroundColor: entrepreneur.borderColor,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: entrepreneur.borderColor,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                };
            });

            radarCharts[canvasId] = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [
                        'Administrativa (max 15)',
                        'Financiera (max 25)',
                        'Producción (max 20)',
                        'Mercado (max 20)',
                        'Tecnología (max 20)'
                    ],
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right',
                            labels: {
                                font: {
                                    size: 10
                                },
                                padding: 8,
                                usePointStyle: true,
                                boxWidth: 6,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.r + ' pts';
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 25,
                            min: 0,
                            ticks: {
                                stepSize: 5,
                                font: {
                                    size: 10
                                }
                            },
                            pointLabels: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        intersect: false
                    }
                }
            });
        }

        function renderAllRadarCharts() {
            // Renderizar gráficas de ENTRADA
            const citiesDataEntry = @json($this->getEntrepreneursDataByCitiesEntry());
            Object.keys(citiesDataEntry).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const entrepreneursData = citiesDataEntry[cityName];
                renderRadarChart(`radar-entry-${citySlug}`, entrepreneursData);
            });

            // Renderizar gráficas de SALIDA
            const citiesDataExit = @json($this->getEntrepreneursDataByCitiesExit());
            Object.keys(citiesDataExit).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const entrepreneursData = citiesDataExit[cityName];
                renderRadarChart(`radar-exit-${citySlug}`, entrepreneursData);
            });
        }

        document.addEventListener('DOMContentLoaded', renderAllRadarCharts);
    </script>
    @endpush
</x-filament-panels::page>
