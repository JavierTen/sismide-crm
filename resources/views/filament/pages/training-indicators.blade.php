{{-- resources/views/filament/pages/training-indicators.blade.php --}}
<x-filament-panels::page>
    @php
        $generalStats = $this->getGeneralStats();
    @endphp

    {{-- Primera fila: Stats generales --}}
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {{-- Total Capacitaciones --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Total Capacitaciones
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_trainings'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Total Emprendedores Capacitados --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Emprendedores Capacitados
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_unique_entrepreneurs'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Capacitaciones Virtuales --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Virtuales
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_virtual'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Capacitaciones Presenciales --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Presenciales
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_in_person'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Capacitaciones Híbridas --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Híbridas
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_hybrid'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Separador --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-300 "></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-3 text-lg font-medium text-gray-900 bg-white ">
                Capacitaciones por Ruta
            </span>
        </div>
    </div>

    {{-- Segunda fila: Stats por Ruta --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Ruta 1 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 1: Pre-emprendimiento
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_route_1'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Ruta 2 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 2: Consolidación
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_route_2'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Ruta 3 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 3: Escalamiento
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $generalStats['total_route_3'] }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Separador --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-3 text-lg font-medium text-gray-900 bg-white dark:bg-gray-900 dark:text-white">
                Intensidad Horaria por Ruta
            </span>
        </div>
    </div>

    {{-- Cards de Intensidad Horaria --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @php
            $avgIntensity = $this->getAverageIntensityByRoute();
        @endphp

        {{-- Ruta 1 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 1: Pre-emprendimiento
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $avgIntensity['data'][0] }} hrs
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Ruta 2 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 2: Consolidación
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $avgIntensity['data'][1] }} hrs
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Ruta 3 --}}
        <div class="relative p-6 overflow-hidden bg-white rounded-lg shadow dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                            Ruta 3: Escalamiento
                        </dt>
                        <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $avgIntensity['data'][2] }} hrs
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>



    {{-- Separador --}}
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="px-3 text-lg font-medium text-gray-900 bg-white dark:bg-gray-900 dark:text-white">
                Gráficos
            </span>
        </div>
    </div>

    {{-- Gráfico de barras --}}
    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Capacitaciones por Municipio
        </h3>
        <canvas id="chartTrainingsByCity" style="max-height: 400px;"></canvas>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @php
                    $trainingsByCity = $this->getTrainingsByCity();
                @endphp

                new Chart(document.getElementById('chartTrainingsByCity'), {
                    type: 'bar',
                    data: {
                        labels: @json($trainingsByCity['labels']),
                        datasets: [{
                            label: 'Capacitaciones',
                            data: @json($trainingsByCity['data']),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            });
        </script>
        {{-- Agregar en el script dentro del @push('scripts') --}}
        <script>
            @php
                $participationByTraining = $this->getParticipationByTraining();
            @endphp

            new Chart(document.getElementById('chartParticipationByTraining'), {
                type: 'bar',
                data: {
                    labels: @json($participationByTraining['labels']),
                    datasets: [{
                        label: 'Participantes',
                        data: @json($participationByTraining['data']),
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>

        {{-- Agregar en el script dentro del @push('scripts') --}}
        <script>
            @php
                $trainingsByRoute = $this->getTrainingsByRoute();
            @endphp

            new Chart(document.getElementById('chartTrainingsByRoute'), {
                type: 'bar',
                data: {
                    labels: @json($trainingsByRoute['labels']),
                    datasets: [{
                        label: 'Capacitaciones',
                        data: @json($trainingsByRoute['data']),
                        backgroundColor: [
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)'
                        ],
                        borderColor: [
                            'rgb(251, 146, 60)',
                            'rgb(59, 130, 246)',
                            'rgb(34, 197, 94)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>
    @endpush

    {{-- Agregar esto después del gráfico de municipios --}}

    <div class="p-6 mt-6 bg-white rounded-lg shadow dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Participación por Capacitación
        </h3>
        <canvas id="chartParticipationByTraining" style="max-height: 400px;"></canvas>
    </div>

    {{-- Agregar esto después del gráfico de participación --}}

    <div class="p-6 mt-6 bg-white rounded-lg shadow dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Capacitaciones por Ruta
        </h3>
        <canvas id="chartTrainingsByRoute" style="max-height: 400px;"></canvas>
    </div>






</x-filament-panels::page>
