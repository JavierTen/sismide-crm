<x-filament-panels::page>
    @php
        $diagnosisData = $this->getDiagnosisData();
    @endphp

    @if ($diagnosisData)
        <div class="space-y-6">
            {{-- Header con información general --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['business_name'] }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Puntaje Total: <span class="font-bold text-gray-900 dark:text-white">{{ $diagnosisData['total_score'] }}</span> / 100
                            @if ($diagnosisData['diagnosis_date'])
                                <span class="ml-4">
                                    • Fecha: {{ \Carbon\Carbon::parse($diagnosisData['diagnosis_date'])->format('d/m/Y') }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="rounded-full px-4 py-2 text-sm font-semibold"
                            style="background-color: {{ $diagnosisData['color'] }}; color: {{ $diagnosisData['borderColor'] }};">
                            {{ $diagnosisData['route'] }}
                        </span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Gráfico Radar --}}
            <x-filament::section>
                <x-slot name="heading">
                    Evaluación por Áreas
                </x-slot>

                <div style="height: 500px; position: relative;">
                    <canvas id="radar-diagnostico-emprendedor"></canvas>
                </div>
            </x-filament::section>

            {{-- Desglose de Puntajes --}}
            {{-- <x-filament::section>
                <x-slot name="heading">
                    Desglose de Puntajes
                </x-slot>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Administrativa</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['scores']['administrative'] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">de 15 puntos</div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-blue-500" style="width: {{ ($diagnosisData['scores']['administrative'] / 15) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Financiera</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['scores']['financial'] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">de 25 puntos</div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-green-500" style="width: {{ ($diagnosisData['scores']['financial'] / 25) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Producción</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['scores']['production'] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">de 20 puntos</div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-purple-500" style="width: {{ ($diagnosisData['scores']['production'] / 20) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Mercado</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['scores']['market'] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">de 20 puntos</div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-yellow-500" style="width: {{ ($diagnosisData['scores']['market'] / 20) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Tecnología</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $diagnosisData['scores']['technology'] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">de 20 puntos</div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full bg-pink-500" style="width: {{ ($diagnosisData['scores']['technology'] / 20) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </x-filament::section> --}}

            {{-- Observaciones Generales --}}
            @if ($diagnosisData['observations'])
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-document-text class="h-5 w-5" />
                            <span>Observaciones Generales</span>
                        </div>
                    </x-slot>


                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        <p class="text-sm text-gray-700 dark:text-gray-300" style="white-space: pre-line;">{{ $diagnosisData['observations'] }}</p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    @else
        {{-- Estado vacío --}}
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-12">
                <div class="rounded-full bg-gray-100 p-6 dark:bg-gray-800">
                    <x-heroicon-o-chart-bar class="h-16 w-16 text-gray-400" />
                </div>
                <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-white">
                    No hay diagnóstico disponible
                </h3>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    Aún no se ha realizado un diagnóstico empresarial.<br>
                    Tu gestor se pondrá en contacto contigo para realizarlo.
                </p>
            </div>
        </x-filament::section>
    @endif

    @if ($diagnosisData)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const diagnosisData = {!! json_encode($diagnosisData) !!};
                    const ctx = document.getElementById('radar-diagnostico-emprendedor');

                    if (ctx && typeof Chart !== 'undefined') {
                        new Chart(ctx, {
                            type: 'radar',
                            data: {
                                labels: [
                                    'Administrativa (máx 15)',
                                    'Financiera (máx 25)',
                                    'Producción (máx 20)',
                                    'Mercado (máx 20)',
                                    'Tecnología (máx 20)'
                                ],
                                datasets: [{
                                    label: diagnosisData.business_name + ' - ' + diagnosisData.total_score + ' pts',
                                    data: [
                                        diagnosisData.scores.administrative,
                                        diagnosisData.scores.financial,
                                        diagnosisData.scores.production,
                                        diagnosisData.scores.market,
                                        diagnosisData.scores.technology
                                    ],
                                    backgroundColor: diagnosisData.color,
                                    borderColor: diagnosisData.borderColor,
                                    borderWidth: 3,
                                    pointBackgroundColor: diagnosisData.borderColor,
                                    pointBorderColor: '#fff',
                                    pointHoverBackgroundColor: '#fff',
                                    pointHoverBorderColor: diagnosisData.borderColor,
                                    pointRadius: 6,
                                    pointHoverRadius: 8,
                                    pointBorderWidth: 2,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        labels: {
                                            font: {
                                                size: 14,
                                                weight: 'bold'
                                            },
                                            padding: 20,
                                            usePointStyle: true,
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        titleFont: { size: 14 },
                                        bodyFont: { size: 13 },
                                        callbacks: {
                                            label: function(context) {
                                                const maxValues = [15, 25, 20, 20, 20];
                                                const max = maxValues[context.dataIndex];
                                                return context.dataset.label + ': ' + context.parsed.r + ' / ' + max + ' pts';
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
                                            font: { size: 12 },
                                            backdropColor: 'transparent'
                                        },
                                        pointLabels: {
                                            font: {
                                                size: 13,
                                                weight: 'bold'
                                            },
                                            padding: 15
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.1)'
                                        }
                                    }
                                }
                            }
                        });
                    }
                }, 500);
            });
        </script>
    @endif
</x-filament-panels::page>
