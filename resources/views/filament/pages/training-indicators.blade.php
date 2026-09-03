<x-filament-panels::page>
@php
    $kpis       = $this->getKpis();
    $municipios = $this->getAvancePorMunicipio();
    $asistRuta  = $this->getAsistenciaPorRuta();
    $intensidad = $this->getIntensidadPorRuta();
    $chart      = $this->getChartData();
@endphp

<div class="space-y-6">

{{-- ── FILTROS ──────────────────────────────────────────────────────────────── --}}
<x-filament::section heading="Filtros" icon="heroicon-o-funnel">
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">Municipio</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterCityId">
                    <option value="">Todos</option>
                    @foreach($this->getCities() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">Ruta</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterRuta">
                    <option value="">Todas</option>
                    <option value="route_1">Ruta 1 — Pre-emprendimiento</option>
                    <option value="route_2">Ruta 2 — Consolidación</option>
                    <option value="route_3">Ruta 3 — Escalamiento</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-500 dark:text-gray-400">Modalidad</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterModalidad">
                    <option value="">Todas</option>
                    <option value="in_person">Presencial</option>
                    <option value="virtual">Virtual</option>
                    <option value="hybrid">Híbrida</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>
</x-filament::section>

{{-- ── KPIs (mismo estilo que StatsOverviewWidget) ─────────────────────────── --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.5rem;">
    @foreach([
        ['label' => 'Capacitaciones realizadas',  'value' => $kpis['capacitaciones_realizadas'], 'desc' => 'con ejecución registrada',                          'icon' => 'heroicon-m-book-open',    'color' => 'primary'],
        ['label' => 'Habilitados únicos',          'value' => $kpis['habilitados_unicos'],         'desc' => 'personas distintas habilitadas en al menos una cap.','icon' => 'heroicon-m-user-group',   'color' => 'info'],
        ['label' => 'Emprendedores capacitados',   'value' => $kpis['emprendedores_unicos'],       'desc' => 'personas únicas con asistencia registrada',           'icon' => 'heroicon-m-users',         'color' => 'warning'],
        ['label' => 'Cobertura de capacitación',   'value' => $kpis['cobertura'].'%',              'desc' => 'capacitados ÷ habilitados únicos',                   'icon' => 'heroicon-m-trophy',        'color' => 'success'],
        ['label' => 'Horas de formación',          'value' => $kpis['horas_formacion'].' h',       'desc' => 'intensidad horaria ejecutada',                       'icon' => 'heroicon-m-clock',         'color' => 'info'],
        ['label' => '% de asistencia',             'value' => $kpis['pct_asistencia'].'%',         'desc' => $kpis['total_asistentes'].'/'.$kpis['total_participaciones'].' asistencias registradas', 'icon' => 'heroicon-m-check-circle', 'color' => 'success'],
    ] as $stat)
    @php $c = $stat['color']; @endphp
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <x-filament::icon :icon="$stat['icon']" class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500"/>
                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $stat['label'] }}
                </span>
            </div>
            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $stat['value'] }}
            </div>
            <div class="flex items-center gap-x-1">
                <span
                    class="fi-wi-stats-overview-stat-description text-sm fi-color-custom fi-color-{{ $c }} text-custom-600 dark:text-custom-400"
                    style="--c-400:var(--{{ $c }}-400);--c-600:var(--{{ $c }}-600)">
                    {{ $stat['desc'] }}
                </span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── AVANCE POR MUNICIPIO (mismo estilo que CityProgressWidget) ──────────── --}}
<x-filament::section heading="Avance por Municipio" icon="heroicon-o-map-pin" description="Porcentaje de asistencia sobre emprendedores habilitados">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="py-2 px-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Municipio</th>
                    <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Capacitaciones</th>
                    <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Habilitados únicos</th>
                    <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Participaciones esperadas</th>
                    <th class="py-2 px-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Asistencias registradas</th>
                    <th class="py-2 px-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">% Asistencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($municipios as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="py-2.5 px-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['ciudad'] }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['cap_realizadas'] }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['habilitados_unicos'] }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['participaciones_esperadas'] }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-600 dark:text-gray-300">{{ $row['asistencias_registradas'] }}</td>
                    <td class="py-2.5 px-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[60px]">
                                <div class="h-2 rounded-full bg-primary-600"
                                     style="width: {{ min($row['pct'], 100) }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-12 text-right">
                                {{ $row['pct'] }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        Sin datos para los filtros seleccionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($municipios) > 1)
            @php
                $totCaps      = array_sum(array_column($municipios, 'cap_realizadas'));
                $totHabUnicos = array_sum(array_column($municipios, 'habilitados_unicos'));
                $totPart      = array_sum(array_column($municipios, 'participaciones_esperadas'));
                $totAsist     = array_sum(array_column($municipios, 'asistencias_registradas'));
                $totPct       = $totPart > 0 ? round($totAsist / $totPart * 100, 1) : 0;
            @endphp
            <tfoot>
                <tr class="border-t-2 border-gray-300 dark:border-gray-600 font-bold">
                    <td class="py-2.5 px-3 text-gray-900 dark:text-gray-100">TOTAL</td>
                    <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totCaps }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totHabUnicos }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totPart }}</td>
                    <td class="py-2.5 px-3 text-center text-gray-900 dark:text-gray-100">{{ $totAsist }}</td>
                    <td class="py-2.5 px-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 min-w-[60px]">
                                <div class="h-2 rounded-full bg-primary-600"
                                     style="width: {{ min($totPct, 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 w-12 text-right">
                                {{ $totPct }}%
                            </span>
                        </div>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</x-filament::section>

{{-- ── FILA: Distribución por Ruta | Modalidad ─────────────────────────────── --}}
<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:1.5rem;">

    <x-filament::section heading="Distribución por Ruta" icon="heroicon-o-map" description="Capacitaciones ejecutadas por ruta de emprendimiento">
        @php
            $totalRuta = array_sum($chart['ruta_data']);
            $rutaDefs  = [
                ['Ruta 1', 'Pre-emprendimiento (Niveles 0, 1 y 2)', '#DC2626'],
                ['Ruta 2', 'Consolidación (Niveles 3 y 4)',          '#1D4ED8'],
                ['Ruta 3', 'Escalamiento (Nivel 5)',                 '#16A34A'],
            ];
        @endphp
        <div class="flex items-center gap-6">
            <div class="flex-shrink-0" style="width:180px; height:180px;">
                <canvas id="chartRuta"></canvas>
            </div>
            <div class="flex-1 space-y-3">
                @foreach($rutaDefs as $i => $r)
                @php $v = $chart['ruta_data'][$i]; $pct = $totalRuta > 0 ? round($v/$totalRuta*100) : 0; @endphp
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-block h-3.5 w-3.5 flex-shrink-0 rounded-sm" style="background:{{ $r[2] }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $r[0] }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $v }} ({{ $pct }}%)</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $r[1] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Modalidad de las Capacitaciones" icon="heroicon-o-computer-desktop" description="Capacitaciones por tipo de modalidad de entrega">
        @php
            $totalMod = array_sum($chart['modalidad_data']);
            $modDefs  = [['Presencial','#1D4ED8'],['Virtual','#16A34A'],['Híbrida','#DC2626']];
        @endphp
        <div class="flex items-center gap-6">
            <div class="flex-shrink-0" style="width:180px; height:180px;">
                <canvas id="chartModalidad"></canvas>
            </div>
            <div class="flex-1 space-y-3">
                @foreach($modDefs as $i => $m)
                @php $v = $chart['modalidad_data'][$i]; $pct = $totalMod > 0 ? round($v/$totalMod*100) : 0; @endphp
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-block h-3.5 w-3.5 flex-shrink-0 rounded-sm" style="background:{{ $m[1] }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $m[0] }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $v }} ({{ $pct }}%)</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</div>

{{-- ── FILA: Emprendedores por Municipio (ancho completo) ─────────────────── --}}
<x-filament::section heading="Emprendedores capacitados por Municipio" icon="heroicon-o-map-pin" description="Personas únicas que han asistido al menos a una capacitación">
    <div style="height:240px;">
        <canvas id="chartEmpMunicipio"></canvas>
    </div>
</x-filament::section>

{{-- ── FILA: Asistencia por Ruta | Intensidad Horaria ─────────────────────── --}}
<div style="display:grid; grid-template-columns:repeat(2,1fr); gap:1.5rem;">

    <x-filament::section heading="Asistencia por Ruta" icon="heroicon-o-chart-bar" description="Relación entre participantes habilitados y asistentes">
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @php
                $rutaLabels = ['Ruta 1','Ruta 2','Ruta 3'];
                $rutaSubs   = ['Pre-emprendimiento','Consolidación','Escalamiento'];
            @endphp
            @foreach($asistRuta as $i => $r)
            <div class="py-3 first:pt-0 last:pb-0">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $rutaLabels[$i] }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rutaSubs[$i] }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $r['pct'] }}%</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $r['asistentes'] }} / {{ $r['habilitados'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ min($r['pct'], 100) }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="Intensidad Horaria por Ruta" icon="heroicon-o-clock" description="Horas de formación ejecutadas — Total {{ $intensidad['total'] }} h">
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach([
                ['Ruta 1', 'Pre-emprendimiento', 'route_1'],
                ['Ruta 2', 'Consolidación',       'route_2'],
                ['Ruta 3', 'Escalamiento',        'route_3'],
            ] as $r)
            <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $r[0] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $r[1] }}</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $intensidad[$r[2]] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-0.5">h</span>
                </div>
            </div>
            @endforeach
        </div>
    </x-filament::section>
</div>


</div>{{-- /space-y-6 --}}

{{-- ── SCRIPTS ──────────────────────────────────────────────────────────────── --}}
@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endonce

<script>
(function () {
    function kill(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        const ex = Chart.getChart(el);
        if (ex) ex.destroy();
        return el;
    }

    function donut(id, data, colors) {
        const el = kill(id);
        if (!el) return;
        new Chart(el, {
            type: 'doughnut',
            data: { datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 4 }] },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => ' ' + ctx.parsed + ' capacitaciones' } }
                },
                animation: { duration: 400 },
            }
        });
    }

    function bar(id, labels, data) {
        const el = kill(id);
        if (!el) return;
        new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: '#1D4ED8', borderRadius: 4, borderWidth: 0 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#F3F4F6' } },
                },
                animation: { duration: 400 },
            }
        });
    }

    window.__tiBuild = function (d) {
        if (typeof Chart === 'undefined') return;
        donut('chartRuta',       d.rutaData,  ['#DC2626','#1D4ED8','#16A34A']);
        donut('chartModalidad',  d.modalData, ['#1D4ED8','#16A34A','#DC2626']);
        bar('chartEmpMunicipio', d.empLabels, d.empValues);
    };

    // Carga inicial
    const initialData = {
        rutaData:  @json($chart['ruta_data']),
        modalData: @json($chart['modalidad_data']),
        empLabels: @json($chart['emp_mun_labels']),
        empValues: @json($chart['emp_mun_data']),
    };

    function waitAndBuild() {
        if (typeof Chart !== 'undefined') { window.__tiBuild(initialData); return; }
        const t = setInterval(() => {
            if (typeof Chart !== 'undefined') { clearInterval(t); window.__tiBuild(initialData); }
        }, 100);
    }

    document.addEventListener('DOMContentLoaded', waitAndBuild);

    // Actualizaciones por filtro: datos frescos vía dispatch de Livewire
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('training-charts-update', (data) => {
            window.__tiBuild(data);
        });
    });
})();
</script>

</x-filament-panels::page>