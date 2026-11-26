<x-filament-panels::page>
    @php
        // Datos y gráficos de siempre (métodos de DiagnosticoGeneral y radares de Chart.js); aquí solo cambia la presentación.
        $citiesDataEntry = $this->getEntrepreneursDataByCitiesEntry();
        $citiesDataExit = $this->getEntrepreneursDataByCitiesExit();

        // Calcular totales ENTRADA (usa 'route' ya asignado por año en PHP)
        $ruta1TotalEntry = 0;
        $ruta2TotalEntry = 0;
        $ruta3TotalEntry = 0;

        foreach ($citiesDataEntry as $entrepreneursData) {
            foreach ($entrepreneursData as $e) {
                if (str_contains($e['route'], 'Fase 1'))      $ruta1TotalEntry++;
                elseif (str_contains($e['route'], 'Fase 2'))  $ruta2TotalEntry++;
                elseif (str_contains($e['route'], 'Fase 3'))  $ruta3TotalEntry++;
            }
        }

        // Calcular totales SALIDA
        $ruta1TotalExit = 0;
        $ruta2TotalExit = 0;
        $ruta3TotalExit = 0;

        foreach ($citiesDataExit as $entrepreneursData) {
            foreach ($entrepreneursData as $e) {
                if (str_contains($e['route'], 'Fase 1'))      $ruta1TotalExit++;
                elseif (str_contains($e['route'], 'Fase 2'))  $ruta2TotalExit++;
                elseif (str_contains($e['route'], 'Fase 3'))  $ruta3TotalExit++;
            }
        }

        $allCities = array_unique(array_merge(array_keys($citiesDataEntry), array_keys($citiesDataExit)));
        sort($allCities);

        $routes = [
            ['number' => 1, 'name' => 'Pre-emprendimiento y validación temprana', 'levels' => 'Niveles 0 a 2', 'color' => '#F59E0B', 'entry' => $ruta1TotalEntry, 'exit' => $ruta1TotalExit],
            ['number' => 2, 'name' => 'Consolidación', 'levels' => 'Niveles 3 y 4', 'color' => '#3B82F6', 'entry' => $ruta2TotalEntry, 'exit' => $ruta2TotalExit],
            ['number' => 3, 'name' => 'Escalamiento', 'levels' => 'Nivel 5', 'color' => '#10B981', 'entry' => $ruta3TotalEntry, 'exit' => $ruta3TotalExit],
        ];

        $year     = \App\Support\YearContext::effectiveYear();
        $yearText = $year ? "en {$year}" : 'en todos los años';
        $num      = fn ($n) => number_format((float) $n, 0, ',', '.');
        // "Fase 1: Nivel 0,1,2 - Pre-emprendimiento" → "Ruta 1" (la fase N es la ruta N).
        $routeName = fn (string $route) => preg_match('/Fase (\d)/', $route, $m) ? 'Ruta ' . $m[1] : 'Sin ruta';
    @endphp

    <style>
        .dg { --dg-line: rgba(var(--gray-950), .08); --dg-soft: rgba(var(--gray-400), .07); --dg-muted: rgb(var(--gray-500)); --dg-text: rgb(var(--gray-950));
            display: flex; flex-direction: column; gap: 1.5rem; font-size: .875rem; }
        .dark .dg { --dg-line: rgba(255, 255, 255, .1); --dg-muted: rgb(var(--gray-400)); --dg-text: #fff; }
        .dg-dot { flex: none; width: .65rem; height: .65rem; border-radius: 9999px; }
        .dg-muted { color: var(--dg-muted); }

        /* Resumen: una tarjeta por ruta con entrada y salida */
        .dg-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem; }
        .dg-card { border: 1px solid var(--dg-line); border-radius: .9rem; padding: 1rem 1.1rem; }
        .dg-card-title { display: flex; align-items: flex-start; gap: .5rem; font-weight: 600; color: var(--dg-text); }
        .dg-card-title .dg-dot { margin-top: .3rem; }
        .dg-card-sub { margin-top: .15rem; padding-left: 1.15rem; font-size: .75rem; color: var(--dg-muted); }
        .dg-figures { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .9rem; }
        .dg-figure { border-radius: .6rem; padding: .55rem .7rem; background: var(--dg-soft); }
        .dg-figure small { display: block; font-size: .72rem; font-weight: 500; color: var(--dg-muted); }
        .dg-figure b { display: block; margin-top: .1rem; font-size: 1.6rem; line-height: 2rem; font-weight: 700; color: var(--dg-text); font-variant-numeric: tabular-nums; }

        /* Municipios */
        .dg-cities { display: flex; flex-direction: column; gap: 1rem; }
        .dg-city { border: 1px solid var(--dg-line); border-radius: .9rem; padding: 1.1rem 1.25rem 1.25rem; }
        .dg-city-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: .25rem .75rem; padding-bottom: .85rem; border-bottom: 1px solid var(--dg-line); }
        .dg-city-name { font-size: 1.1rem; font-weight: 700; color: var(--dg-text); }
        .dg-city-meta { font-size: .78rem; color: var(--dg-muted); }
        .dg-side { padding-top: 1.1rem; }
        .dg-side + .dg-side { margin-top: 1.1rem; border-top: 1px dashed var(--dg-line); }
        .dg-side-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; margin-bottom: .75rem; }
        .dg-tag { display: inline-flex; padding: .2rem .7rem; border-radius: 9999px; font-size: .78rem; font-weight: 600; }
        .dg-tag.is-entry { background: rgba(var(--primary-500), .12); color: rgb(var(--primary-700)); }
        .dg-tag.is-exit { background: rgba(var(--success-500), .14); color: rgb(var(--success-700)); }
        .dark .dg-tag.is-entry { color: rgb(var(--primary-300)); }
        .dark .dg-tag.is-exit { color: rgb(var(--success-300)); }
        .dg-chart { position: relative; height: 600px; }
        .dg-none { display: flex; align-items: center; justify-content: center; gap: .5rem; min-height: 7rem; border-radius: .75rem; background: var(--dg-soft); color: var(--dg-muted); }
        .dg-none svg { width: 1.4rem; height: 1.4rem; }

        /* Emprendimientos graficados */
        .dg-list { margin-top: 1rem; }
        .dg-list summary { display: inline-flex; align-items: center; gap: .4rem; cursor: pointer; font-size: .85rem; font-weight: 600; color: var(--dg-text); list-style: none; }
        .dg-list summary::-webkit-details-marker { display: none; }
        .dg-list summary::before { content: '▸'; color: var(--dg-muted); transition: transform .15s; }
        .dg-list[open] summary::before { transform: rotate(90deg); }
        .dg-list summary:focus-visible { outline: 2px solid rgb(var(--primary-500)); outline-offset: 2px; border-radius: .25rem; }
        .dg-items { list-style: none; margin: .75rem 0 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr)); gap: .5rem; }
        .dg-item { display: flex; align-items: flex-start; gap: .55rem; min-width: 0; padding: .55rem .65rem; border-radius: .6rem; background: var(--dg-soft); }
        .dg-item .dg-dot { margin-top: .3rem; }
        .dg-item-text { flex: 1; min-width: 0; }
        .dg-item-name { font-weight: 600; color: var(--dg-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dg-item-business { font-size: .78rem; color: var(--dg-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dg-item-score { flex: none; text-align: right; font-size: .75rem; color: var(--dg-muted); white-space: nowrap; }
        .dg-item-score b { display: block; font-size: .95rem; color: var(--dg-text); font-variant-numeric: tabular-nums; }

        @media (prefers-reduced-motion: reduce) { .dg-list summary::before { transition: none; } }
    </style>

    <div class="dg">
        {{-- ===== Resumen general: entrada y salida por ruta ===== --}}
        <x-filament::section>
            <x-slot name="heading">Resumen general</x-slot>
            <x-slot name="description">Emprendimientos en cada ruta según su diagnóstico de entrada y de salida, {{ $yearText }}.</x-slot>

            <div class="dg-summary">
                @foreach ($routes as $route)
                    <div class="dg-card">
                        <div class="dg-card-title">
                            <span class="dg-dot" style="background: {{ $route['color'] }};" aria-hidden="true"></span>
                            <span>Ruta {{ $route['number'] }} · {{ $route['name'] }}</span>
                        </div>
                        <div class="dg-card-sub">{{ $route['levels'] }}</div>
                        <div class="dg-figures">
                            <div class="dg-figure"><small>Entrada</small><b>{{ $num($route['entry']) }}</b></div>
                            <div class="dg-figure"><small>Salida</small><b>{{ $num($route['exit']) }}</b></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- ===== Comparativa por municipio: radar de cada emprendimiento por área ===== --}}
        <x-filament::section>
            <x-slot name="heading">Comparativa por municipio</x-slot>
            <x-slot name="description">Puntaje de cada emprendimiento por área (administrativa, financiera, producción, mercado y tecnología), en la entrada y en la salida.</x-slot>

            @if (count($allCities) > 0)
                <div class="dg-cities">
                    @foreach ($allCities as $cityName)
                        @php
                            $citySlug = Str::lower(Str::slug(Str::ascii($cityName)));
                            $sides = [
                                'entry' => ['label' => 'Entrada', 'data' => $citiesDataEntry[$cityName] ?? null],
                                'exit'  => ['label' => 'Salida', 'data' => $citiesDataExit[$cityName] ?? null],
                            ];
                        @endphp

                        <article class="dg-city">
                            <div class="dg-city-head">
                                <span class="dg-city-name">{{ $cityName }}</span>
                                <span class="dg-city-meta">{{ $num(count($sides['entry']['data'] ?? [])) }} con diagnóstico de entrada · {{ $num(count($sides['exit']['data'] ?? [])) }} de salida</span>
                            </div>

                            @foreach ($sides as $side => $info)
                                <div class="dg-side">
                                    <div class="dg-side-head">
                                        <span class="dg-tag is-{{ $side }}">{{ $info['label'] }}</span>
                                        @if ($info['data'])
                                            <span class="dg-muted" style="font-size:.8rem;">{{ $num(count($info['data'])) }} {{ count($info['data']) === 1 ? 'emprendimiento graficado' : 'emprendimientos graficados' }}</span>
                                        @endif
                                    </div>

                                    @if ($info['data'])
                                        <div class="dg-chart">
                                            <canvas id="radar-{{ $side }}-{{ $citySlug }}" role="img" aria-label="Radar de {{ mb_strtolower($info['label']) }} de {{ $cityName }}"></canvas>
                                        </div>

                                        <details class="dg-list" open>
                                            <summary>Emprendimientos graficados ({{ $num(count($info['data'])) }})</summary>
                                            <ul class="dg-items">
                                                @foreach ($info['data'] as $entrepreneur)
                                                    <li class="dg-item">
                                                        <span class="dg-dot" style="background: {{ $entrepreneur['borderColor'] }};" aria-hidden="true"></span>
                                                        <div class="dg-item-text">
                                                            <div class="dg-item-name" title="{{ $entrepreneur['name'] }}">{{ $entrepreneur['name'] }}</div>
                                                            <div class="dg-item-business" title="{{ $entrepreneur['business_name'] }}">{{ $entrepreneur['business_name'] }}</div>
                                                        </div>
                                                        <div class="dg-item-score" title="{{ $entrepreneur['route'] }}">
                                                            <b>{{ $entrepreneur['total_score'] }} pts</b>
                                                            {{ $routeName($entrepreneur['route']) }}
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @else
                                        <div class="dg-none">
                                            <x-filament::icon icon="heroicon-o-chart-bar" />
                                            Sin diagnósticos de {{ mb_strtolower($info['label']) }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>
            @else
                <p class="dg-muted" style="text-align:center; padding:2rem 0;">No hay diagnósticos {{ $yearText }}.</p>
            @endif
        </x-filament::section>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        let radarCharts = {};

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

        function renderRadarChart(canvasId, entrepreneursData) {
            const ctx = document.getElementById(canvasId);

            if (!ctx) return;

            if (radarCharts[canvasId]) {
                radarCharts[canvasId].destroy();
            }

            const theme = chartTheme();

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
                                color: theme.text,
                                font: {
                                    size: 10,
                                    family: theme.font,
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
                                color: theme.text,
                                backdropColor: 'transparent',
                                font: {
                                    size: 10
                                }
                            },
                            grid: { color: theme.grid },
                            angleLines: { color: theme.grid },
                            pointLabels: {
                                color: theme.text,
                                font: {
                                    size: 11,
                                    family: theme.font,
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
            const citiesDataEntry = @json($citiesDataEntry);
            Object.keys(citiesDataEntry).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const entrepreneursData = citiesDataEntry[cityName];
                renderRadarChart(`radar-entry-${citySlug}`, entrepreneursData);
            });

            // Renderizar gráficas de SALIDA
            const citiesDataExit = @json($citiesDataExit);
            Object.keys(citiesDataExit).forEach(cityName => {
                const citySlug = createSlug(cityName);
                const entrepreneursData = citiesDataExit[cityName];
                renderRadarChart(`radar-exit-${citySlug}`, entrepreneursData);
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
