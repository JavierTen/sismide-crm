<x-filament-panels::page>
    <div class="space-y-8">
        @php
            $dataEntry = $this->getDataByRouteEntry();
            $dataExit = $this->getDataByRouteExit();
            $totalsEntry = $this->getTotalsByRoute('entry');
            $totalsExit = $this->getTotalsByRoute('exit');
        @endphp

        {{-- ========== RESUMEN GENERAL ENTRADA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                Resumen General - Entrada
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 1: Pre-emprendimiento y validación temprana</p>
                    <p class="text-3xl font-bold text-orange-500">{{ $totalsEntry['ruta1'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 2: Consolidación</p>
                    <p class="text-3xl font-bold text-blue-500">{{ $totalsEntry['ruta2'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 3: Escalamiento</p>
                    <p class="text-3xl font-bold text-green-500">{{ $totalsEntry['ruta3'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>
            </div>
        </x-filament::section>

        <br>

        {{-- ========== RESUMEN GENERAL SALIDA ========== --}}
        <x-filament::section>
            <x-slot name="heading">
                Resumen General - Salida
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 1: Pre-emprendimiento y validación temprana</p>
                    <p class="text-3xl font-bold text-orange-500">{{ $totalsExit['ruta1'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 2: Consolidación</p>
                    <p class="text-3xl font-bold text-blue-500">{{ $totalsExit['ruta2'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 3: Escalamiento</p>
                    <p class="text-3xl font-bold text-green-500">{{ $totalsExit['ruta3'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>
            </div>
        </x-filament::section>

        <br>
        {{-- ========== GRÁFICOS POR MUNICIPIO Y RUTA ========== --}}
        @php
            $allCities = array_unique(array_merge(
                array_keys($dataEntry),
                array_keys($dataExit)
            ));
            sort($allCities);
        @endphp

        @if(count($allCities) > 0)
            @foreach($allCities as $cityName)
                @php
                    $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                @endphp

                <x-filament::section>
                    <x-slot name="heading">
                        {{ $cityName }}
                    </x-slot>

                    <div class="space-y-8">
                        {{-- RUTA 1 --}}
                        @if((isset($dataEntry[$cityName]['ruta1']) && count($dataEntry[$cityName]['ruta1']) > 0) ||
                            (isset($dataExit[$cityName]['ruta1']) && count($dataExit[$cityName]['ruta1']) > 0))
                            <div class="border-2 border-orange-300 dark:border-orange-700 rounded-lg p-6 bg-orange-50 dark:bg-orange-900/10">
                                <h4 class="font-bold text-orange-700 dark:text-orange-400 mb-6 text-xl flex items-center justify-center">
                                    <span class="w-10 h-10 bg-orange-500 text-white rounded-full flex items-center justify-center mr-3">1</span>
                                    Ruta 1: Pre-emprendimiento y validación temprana
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- ENTRADA --}}
                                    <div>
                                        <div class="text-center mb-4 font-semibold">
                                            ENTRADA
                                        </div>
                                        @if(isset($dataEntry[$cityName]['ruta1']) && count($dataEntry[$cityName]['ruta1']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta1-entry"></canvas>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de entrada
                                            </div>
                                        @endif
                                    </div>

                                    {{-- SALIDA --}}
                                    <div>
                                        <div class="text-center mb-4">
                                            <div class="text-center mb-4 font-semibold">
                                                SALIDA
                                            </div>
                                        </div>
                                        @if(isset($dataExit[$cityName]['ruta1']) && count($dataExit[$cityName]['ruta1']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta1-exit"></canvas>
                                            </div>

                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de salida
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <br>

                        {{-- RUTA 2 --}}
                        @if((isset($dataEntry[$cityName]['ruta2']) && count($dataEntry[$cityName]['ruta2']) > 0) ||
                            (isset($dataExit[$cityName]['ruta2']) && count($dataExit[$cityName]['ruta2']) > 0))
                            <div class="border-2 border-blue-300 dark:border-blue-700 rounded-lg p-6 bg-blue-50 dark:bg-blue-900/10">
                                <h4 class="font-bold text-blue-700 dark:text-blue-400 mb-6 text-xl flex items-center justify-center">
                                    <span class="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">2</span>
                                    Ruta 2: Consolidación
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- ENTRADA --}}
                                    <div>
                                        <div class="text-center mb-4 font-semibold">
                                            ENTRADA
                                        </div>
                                        @if(isset($dataEntry[$cityName]['ruta2']) && count($dataEntry[$cityName]['ruta2']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta2-entry"></canvas>
                                            </div>

                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de entrada
                                            </div>
                                        @endif
                                    </div>

                                    {{-- SALIDA --}}
                                    <div>
                                        <div class="text-center mb-4">
                                            <div class="text-center mb-4 font-semibold">
                                                SALIDA
                                            </div>
                                        </div>
                                        @if(isset($dataExit[$cityName]['ruta2']) && count($dataExit[$cityName]['ruta2']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta2-exit"></canvas>
                                            </div>

                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de salida
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        <br>
                        {{-- RUTA 3 --}}
                        @if((isset($dataEntry[$cityName]['ruta3']) && count($dataEntry[$cityName]['ruta3']) > 0) ||
                            (isset($dataExit[$cityName]['ruta3']) && count($dataExit[$cityName]['ruta3']) > 0))
                            <div class="border-2 border-green-300 dark:border-green-700 rounded-lg p-6 bg-green-50 dark:bg-green-900/10">
                                <h4 class="font-bold text-green-700 dark:text-green-400 mb-6 text-xl flex items-center justify-center">
                                    <span class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center mr-3">3</span>
                                    Ruta 3: Escalamiento
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- ENTRADA --}}
                                    <div>
                                        <div class="text-center mb-4 font-semibold">
                                            ENTRADA
                                        </div>
                                        @if(isset($dataEntry[$cityName]['ruta3']) && count($dataEntry[$cityName]['ruta3']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta3-entry"></canvas>
                                            </div>

                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de entrada
                                            </div>
                                        @endif
                                    </div>

                                    {{-- SALIDA --}}
                                    <div>
                                        <div class="text-center mb-4">
                                            <div class="text-center mb-4 font-semibold">
                                                SALIDA
                                            </div>
                                        </div>
                                        @if(isset($dataExit[$cityName]['ruta3']) && count($dataExit[$cityName]['ruta3']) > 0)
                                            <div style="height: 400px; position: relative;">
                                                <canvas id="radar-{{ $citySlug }}-ruta3-exit"></canvas>
                                            </div>

                                        @else
                                            <div class="flex items-center justify-center h-64 text-gray-500">
                                                Sin datos de salida
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
                <br>
            @endforeach
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

        function getColorByScore(score) {
            if (score >= 0 && score <= 50) {
                return {
                    bg: 'rgba(245, 158, 11, 0.2)',
                    border: '#F59E0B'
                };
            } else if (score >= 51 && score <= 85) {
                return {
                    bg: 'rgba(59, 130, 246, 0.2)',
                    border: '#3B82F6'
                };
            } else {
                return {
                    bg: 'rgba(16, 185, 129, 0.2)',
                    border: '#10B981'
                };
            }
        }

        function renderRadarChart(canvasId, entrepreneursData) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            if (radarCharts[canvasId]) {
                radarCharts[canvasId].destroy();
            }

            const datasets = entrepreneursData.map((entrepreneur) => {
                const colors = getColorByScore(entrepreneur.total_score);
                return {
                    label: entrepreneur.name + ' (' + entrepreneur.total_score + ' pts)',
                    data: [
                        entrepreneur.scores.administrative,
                        entrepreneur.scores.financial,
                        entrepreneur.scores.production,
                        entrepreneur.scores.market,
                        entrepreneur.scores.technology
                    ],
                    backgroundColor: colors.bg,
                    borderColor: colors.border,
                    borderWidth: 2,
                    pointBackgroundColor: colors.border,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: colors.border,
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
                            position: 'bottom',
                            labels: {
                                font: { size: 9 },
                                padding: 6,
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
                            ticks: {
                                stepSize: 5,
                                font: { size: 9 }
                            },
                            pointLabels: {
                                font: { size: 10 }
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

        function renderAllCharts() {
            const dataEntry = @json($this->getDataByRouteEntry());
            const dataExit = @json($this->getDataByRouteExit());

            Object.keys(dataEntry).forEach(cityName => {
                const citySlug = createSlug(cityName);

                ['ruta1', 'ruta2', 'ruta3'].forEach(ruta => {
                    if (dataEntry[cityName][ruta] && dataEntry[cityName][ruta].length > 0) {
                        renderRadarChart(`radar-${citySlug}-${ruta}-entry`, dataEntry[cityName][ruta]);
                    }
                });
            });

            Object.keys(dataExit).forEach(cityName => {
                const citySlug = createSlug(cityName);

                ['ruta1', 'ruta2', 'ruta3'].forEach(ruta => {
                    if (dataExit[cityName][ruta] && dataExit[cityName][ruta].length > 0) {
                        renderRadarChart(`radar-${citySlug}-${ruta}-exit`, dataExit[cityName][ruta]);
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', renderAllCharts);
    </script>
    @endpush
</x-filament-panels::page>
