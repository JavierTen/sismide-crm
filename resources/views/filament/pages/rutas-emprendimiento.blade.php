<x-filament-panels::page>
    @php
        // Datos y gráficos de siempre (métodos de RutasEmprendimiento y radares de Chart.js); aquí solo cambia la presentación.
        $routesEntry    = $this->getRoutesDataEntry();
        $routesExit     = $this->getRoutesDataExit();
        $radarDataEntry = $this->getRadarDataByCityEntry();
        $radarDataExit  = $this->getRadarDataByCityExit();
        $year           = \App\Support\YearContext::effectiveYear();
        $phases         = \App\Support\MaturityScale::getPhaseRanges($year ?? now()->year);
        $colors = [
            'ruta1' => ['bg' => '#F59E0B', 'border' => '#D97706'],
            'ruta2' => ['bg' => '#3B82F6', 'border' => '#2563EB'],
            'ruta3' => ['bg' => '#10B981', 'border' => '#059669'],
        ];
        $routes = [
            'ruta1' => ['number' => 1, 'name' => 'Pre-emprendimiento', 'levels' => 'Niveles 0 a 2', 'range' => $phases[1] ?? null],
            'ruta2' => ['number' => 2, 'name' => 'Consolidación', 'levels' => 'Niveles 3 y 4', 'range' => $phases[2] ?? null],
            'ruta3' => ['number' => 3, 'name' => 'Escalamiento', 'levels' => 'Nivel 5', 'range' => $phases[3] ?? null],
        ];

        $allCities = array_unique(array_merge(array_keys($radarDataEntry), array_keys($radarDataExit)));
        sort($allCities);

        $yearText  = $year ? "en {$year}" : 'en todos los años';
        $num       = fn ($n) => number_format((float) $n, 0, ',', '.');
        $pts       = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', '.'), '0'), ',');
        $cityTotal = fn (?array $d) => $d ? array_sum(array_map(fn ($r) => $d[$r]['count'], array_keys($routes))) : 0;
    @endphp

    <style>
        .rt { --rt-line: rgba(var(--gray-950), .08); --rt-soft: rgba(var(--gray-400), .07); --rt-muted: rgb(var(--gray-500)); --rt-text: rgb(var(--gray-950));
            display: flex; flex-direction: column; gap: 1.5rem; font-size: .875rem; }
        .dark .rt { --rt-line: rgba(255, 255, 255, .1); --rt-muted: rgb(var(--gray-400)); --rt-text: #fff; }
        .rt-dot { flex: none; width: .65rem; height: .65rem; border-radius: 9999px; }
        .rt-muted { color: var(--rt-muted); }

        /* Resumen: una tarjeta por ruta con entrada y salida */
        .rt-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem; }
        .rt-card { border: 1px solid var(--rt-line); border-radius: .9rem; padding: 1rem 1.1rem; }
        .rt-card-title { display: flex; align-items: center; gap: .5rem; font-weight: 600; color: var(--rt-text); }
        .rt-card-sub { margin-top: .15rem; font-size: .75rem; color: var(--rt-muted); }
        .rt-card-figures { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .9rem; }
        .rt-figure { border-radius: .6rem; padding: .55rem .7rem; background: var(--rt-soft); }
        .rt-figure small { display: block; font-size: .72rem; font-weight: 500; color: var(--rt-muted); }
        .rt-figure b { display: block; margin-top: .1rem; font-size: 1.6rem; line-height: 2rem; font-weight: 700; color: var(--rt-text); font-variant-numeric: tabular-nums; }

        /* Comparativa por municipio */
        .rt-cities { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 38rem), 1fr)); gap: 1rem; }
        .rt-city { border: 1px solid var(--rt-line); border-radius: .9rem; padding: 1.1rem 1.2rem 1.2rem; }
        .rt-city-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: .25rem .75rem; padding-bottom: .85rem; margin-bottom: 1rem; border-bottom: 1px solid var(--rt-line); }
        .rt-city-name { font-size: 1.05rem; font-weight: 700; color: var(--rt-text); }
        .rt-city-meta { font-size: .78rem; color: var(--rt-muted); }
        .rt-sides { display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: 1.25rem; }
        .rt-side-head { display: flex; justify-content: center; margin-bottom: .5rem; }
        .rt-tag { display: inline-flex; align-items: center; gap: .4rem; padding: .2rem .7rem; border-radius: 9999px; font-size: .78rem; font-weight: 600; }
        .rt-tag.is-entry { background: rgba(59, 130, 246, .12); color: #1d4ed8; }
        .rt-tag.is-exit { background: rgba(16, 185, 129, .14); color: #047857; }
        .dark .rt-tag.is-entry { color: #93c5fd; }
        .dark .rt-tag.is-exit { color: #6ee7b7; }
        .rt-chart { position: relative; height: 280px; }
        .rt-none { height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; border-radius: .75rem;
            background: var(--rt-soft); color: var(--rt-muted); text-align: center; padding: 1rem; }
        .rt-none svg { width: 1.75rem; height: 1.75rem; }
        .rt-list { list-style: none; margin: .75rem 0 0; padding: 0; }
        .rt-list li { display: flex; align-items: center; gap: .5rem; padding: .35rem 0; border-top: 1px solid var(--rt-line); font-size: .82rem; color: var(--rt-text); }
        .rt-list li .rt-value { margin-left: auto; font-variant-numeric: tabular-nums; text-align: right; }
    </style>

    <div class="rt">
        {{-- ===== Resumen general: entrada y salida por ruta ===== --}}
        <x-filament::section>
            <x-slot name="heading">Resumen general</x-slot>
            <x-slot name="description">Emprendimientos en cada ruta según su diagnóstico de entrada y de salida, {{ $yearText }}.</x-slot>

            <div class="rt-summary">
                @foreach ($routes as $key => $route)
                    <div class="rt-card">
                        <div class="rt-card-title">
                            <span class="rt-dot" style="background: {{ $colors[$key]['bg'] }};" aria-hidden="true"></span>
                            Ruta {{ $route['number'] }} · {{ $route['name'] }}
                        </div>
                        <div class="rt-card-sub">
                            {{ $route['levels'] }}@if ($route['range']) · {{ $route['range']['min'] }} a {{ $route['range']['max'] }} puntos @endif
                        </div>
                        <div class="rt-card-figures">
                            <div class="rt-figure">
                                <small>Entrada</small>
                                <b>{{ $num($routesEntry[$key]['total']) }}</b>
                            </div>
                            <div class="rt-figure">
                                <small>Salida</small>
                                <b>{{ $num($routesExit[$key]['total']) }}</b>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- ===== Comparativa por municipio: radares de entrada y salida ===== --}}
        <x-filament::section>
            <x-slot name="heading">Comparativa por municipio</x-slot>
            <x-slot name="description">Puntaje promedio de cada ruta en la entrada y en la salida.</x-slot>

            @if (count($allCities) > 0)
                <div class="rt-cities">
                    @foreach ($allCities as $cityName)
                        @php
                            $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                            $sides = [
                                'entry' => ['label' => 'Entrada', 'data' => $radarDataEntry[$cityName] ?? null],
                                'exit'  => ['label' => 'Salida', 'data' => $radarDataExit[$cityName] ?? null],
                            ];
                        @endphp

                        <article class="rt-city">
                            <div class="rt-city-head">
                                <span class="rt-city-name">{{ $cityName }}</span>
                                <span class="rt-city-meta">{{ $num($cityTotal($sides['entry']['data'])) }} con diagnóstico de entrada · {{ $num($cityTotal($sides['exit']['data'])) }} de salida</span>
                            </div>

                            <div class="rt-sides">
                                @foreach ($sides as $side => $info)
                                    <div>
                                        <div class="rt-side-head">
                                            <span class="rt-tag is-{{ $side }}">{{ $info['label'] }}</span>
                                        </div>

                                        @if ($info['data'])
                                            <div class="rt-chart">
                                                <canvas id="radar-{{ $side }}-{{ $citySlug }}" aria-label="Radar de {{ mb_strtolower($info['label']) }} de {{ $cityName }}" role="img"></canvas>
                                            </div>

                                            <ul class="rt-list">
                                                @foreach ($routes as $key => $route)
                                                    @if ($info['data'][$key]['count'] > 0)
                                                        <li>
                                                            <span class="rt-dot" style="background: {{ $colors[$key]['bg'] }};" aria-hidden="true"></span>
                                                            Ruta {{ $route['number'] }}
                                                            <span class="rt-value">
                                                                {{ $num($info['data'][$key]['count']) }} {{ $info['data'][$key]['count'] === 1 ? 'emprendimiento' : 'emprendimientos' }}
                                                                <span class="rt-muted">· prom. {{ $pts($info['data'][$key]['avg']) }} pts</span>
                                                            </span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="rt-none">
                                                <x-filament::icon icon="heroicon-o-chart-bar" />
                                                <span>Sin diagnósticos de {{ mb_strtolower($info['label']) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="rt-muted" style="text-align:center; padding:2rem 0;">No hay diagnósticos {{ $yearText }}.</p>
            @endif
        </x-filament::section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let radarCharts = {};

        const phaseRanges = @json($phases);

        function createSlug(text) {
            return text
                .toLowerCase()
                .normalize("NFD")
                .replace(/[̀-ͯ]/g, "")
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        // Colores de textos y líneas guía según el tema del panel (el radar en sí no cambia).
        function chartTheme() {
            const dark = document.documentElement.classList.contains('dark');
            return {
                text: dark ? '#a1a1aa' : '#52525b',
                grid: dark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.08)',
                font: getComputedStyle(document.body).fontFamily,
            };
        }

        function renderRadarChart(canvasId, cityData, backgroundColor, borderColor) {
            const ctx = document.getElementById(canvasId);

            if (!ctx) return;

            if (radarCharts[canvasId]) {
                radarCharts[canvasId].destroy();
            }

            const theme = chartTheme();

            radarCharts[canvasId] = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [
                        `Ruta 1 (${phaseRanges[1].min}-${phaseRanges[1].max})`,
                        `Ruta 2 (${phaseRanges[2].min}-${phaseRanges[2].max})`,
                        `Ruta 3 (${phaseRanges[3].min}-${phaseRanges[3].max})`,
                    ],
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
                            labels: { color: theme.text, font: { family: theme.font } },
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
                                stepSize: 20,
                                color: theme.text,
                                backdropColor: 'transparent',
                            },
                            grid: { color: theme.grid },
                            angleLines: { color: theme.grid },
                            pointLabels: {
                                color: theme.text,
                                font: {
                                    size: 10,
                                    family: theme.font,
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderAllRadarCharts() {
            // Renderizar gráficas de ENTRADA (azul)
            const radarDataEntry = @json($radarDataEntry);
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
            const radarDataExit = @json($radarDataExit);
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

        // Si se cambia entre modo claro y oscuro, los radares se vuelven a dibujar con los colores del tema.
        let wasDark = document.documentElement.classList.contains('dark');
        new MutationObserver(() => {
            const dark = document.documentElement.classList.contains('dark');
            if (dark !== wasDark) {
                wasDark = dark;
                renderAllRadarCharts();
            }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    </script>
    @endpush
</x-filament-panels::page>
