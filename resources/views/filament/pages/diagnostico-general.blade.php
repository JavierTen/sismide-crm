<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $citiesData = $this->getEntrepreneursDataByCities();

            // Calcular totales por ruta
            $ruta1Total = 0;
            $ruta2Total = 0;
            $ruta3Total = 0;

            foreach($citiesData as $cityName => $entrepreneursData) {
                foreach($entrepreneursData as $entrepreneur) {
                    if ($entrepreneur['total_score'] >= 0 && $entrepreneur['total_score'] <= 50) {
                        $ruta1Total++;
                    } elseif ($entrepreneur['total_score'] >= 51 && $entrepreneur['total_score'] <= 85) {
                        $ruta2Total++;
                    } elseif ($entrepreneur['total_score'] >= 86 && $entrepreneur['total_score'] <= 100) {
                        $ruta3Total++;
                    }
                }
            }
        @endphp

        <!-- Resumen General -->
        <x-filament::section>
            <x-slot name="heading">
                Resumen General
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 1: Pre-emprendimiento y validación temprana</p>
                    <p class="text-3xl font-bold text-orange-500">{{ $ruta1Total }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 2: Consolidación</p>
                    <p class="text-3xl font-bold text-blue-500">{{ $ruta2Total }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Ruta 3: Escalamiento</p>
                    <p class="text-3xl font-bold text-green-500">{{ $ruta3Total }}</p>
                    <p class="text-xs text-gray-500 mt-1">emprendimientos</p>
                </div>
            </div>
        </x-filament::section>

        @if(count($citiesData) > 0)
            <!-- Gráficos por Municipio -->
            <div class="grid grid-cols-1 gap-6">
                @foreach($citiesData as $cityName => $entrepreneursData)
                    @php
                        $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                    @endphp

                    <x-filament::section>
                        <x-slot name="heading">
                            {{ $cityName }} - {{ count($entrepreneursData) }} Emprendimientos
                        </x-slot>

                        <div style="height: 600px; position: relative;">
                            <canvas id="radar-{{ $citySlug }}"></canvas>
                        </div>

                        <!-- Leyenda de emprendimientos -->
                        <div class="mt-6 space-y-2">
                            <h3 class="text-base font-semibold mb-3">Emprendimientos Graficados</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                @foreach($entrepreneursData as $entrepreneur)
                                    <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
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
                    </x-filament::section>
                @endforeach
            </div>
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

        // Función para crear slug
        function createSlug(text) {
            return text
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function renderRadarCharts() {
            const citiesData = @json($this->getEntrepreneursDataByCities());

            Object.keys(citiesData).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const entrepreneursData = citiesData[cityName];
                const ctx = document.getElementById(`radar-${citySlug}`);

                if (radarCharts[citySlug]) {
                    radarCharts[citySlug].destroy();
                }

                // Preparar datasets (uno por emprendimiento)
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

                if (ctx) {
                    radarCharts[citySlug] = new Chart(ctx, {
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
            });
        }

        document.addEventListener('DOMContentLoaded', renderRadarCharts);
    </script>
    @endpush
</x-filament-panels::page>
