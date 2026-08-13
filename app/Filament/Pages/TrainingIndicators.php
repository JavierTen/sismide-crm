<?php

namespace App\Filament\Pages;

use App\Models\City;
use App\Models\Training;
use App\Models\TrainingParticipation;
use App\Models\TrainingSession;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class TrainingIndicators extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $navigationLabel = 'Indicadores';
    protected static ?string $title           = 'Indicadores de Capacitaciones';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.pages.training-indicators';

    public ?int    $filterCityId   = null;
    public ?string $filterRuta     = null;
    public ?string $filterModalidad = null;
    public ?string $filterEstado   = null;

    public static function canAccess(): bool
    {
        return auth()->user()->can('viewTrainingIndicators');
    }

    public function updated(string $name): void
    {
        if (!in_array($name, ['filterCityId', 'filterRuta', 'filterModalidad'])) {
            return;
        }

        $chart = $this->getChartData();
        $this->dispatch('training-charts-update',
            rutaData:  $chart['ruta_data'],
            modalData: $chart['modalidad_data'],
            empLabels: $chart['emp_mun_labels'],
            empValues: $chart['emp_mun_data'],
        );
    }

    // ── Helpers de query ────────────────────────────────────────────────────

    private function sessionsQuery()
    {
        $q = DB::table('training_sessions as ts')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at');

        if ($this->filterCityId)    $q->where('ts.city_id', $this->filterCityId);
        if ($this->filterRuta)      $q->where('t.route', $this->filterRuta);
        if ($this->filterModalidad) $q->where('t.modality', $this->filterModalidad);
        if ($this->filterEstado)    $q->where('t.status', $this->filterEstado);

        return $q;
    }

    private function participationsQuery()
    {
        $q = DB::table('training_participations as tp')
            ->join('training_sessions as ts', 'tp.training_session_id', '=', 'ts.id')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->whereNull('tp.deleted_at')
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at');

        if ($this->filterCityId)    $q->where('ts.city_id', $this->filterCityId);
        if ($this->filterRuta)      $q->where('t.route', $this->filterRuta);
        if ($this->filterModalidad) $q->where('t.modality', $this->filterModalidad);
        if ($this->filterEstado)    $q->where('t.status', $this->filterEstado);

        return $q;
    }

    // ── Datos para filtros ───────────────────────────────────────────────────

    public function getCities(): array
    {
        return DB::table('cities as c')
            ->join('training_sessions as ts', 'ts.city_id', '=', 'c.id')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at')
            ->orderBy('c.name')
            ->distinct()
            ->pluck('c.name', 'c.id')
            ->toArray();
    }

    // ── KPIs ─────────────────────────────────────────────────────────────────

    public function getKpis(): array
    {
        $sesiones = $this->sessionsQuery();

        $capacitacionesRealizadas = (clone $sesiones)
            ->distinct()
            ->count('ts.training_id');

        $emprendedoresUnicos = $this->participationsQuery()
            ->where('tp.attended', true)
            ->distinct()
            ->count('tp.entrepreneur_id');

        // Horas de formación: sum de intensity_hours de trainings con sesiones (multiplicada por nro de sesiones)
        $horasFormacion = (clone $sesiones)
            ->sum('t.intensity_hours') ?? 0;

        $totalParticipaciones = $this->participationsQuery()->count();
        $totalAsistentes      = $this->participationsQuery()->where('tp.attended', true)->count();

        $pctAsistencia = $totalParticipaciones > 0
            ? round(($totalAsistentes / $totalParticipaciones) * 100, 1)
            : 0;

        return [
            'capacitaciones_realizadas' => $capacitacionesRealizadas,
            'emprendedores_unicos'      => $emprendedoresUnicos,
            'horas_formacion'           => round($horasFormacion, 1),
            'pct_asistencia'            => $pctAsistencia,
            'total_asistentes'          => $totalAsistentes,
            'total_participaciones'     => $totalParticipaciones,
        ];
    }

    // ── Avance por municipio ─────────────────────────────────────────────────

    public function getAvancePorMunicipio(): array
    {
        $rows = DB::table('training_sessions as ts')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->join('cities as c', 'ts.city_id', '=', 'c.id')
            ->leftJoin('training_participations as tp', function ($j) {
                $j->on('tp.training_session_id', '=', 'ts.id')
                  ->whereNull('tp.deleted_at');
            })
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at')
            ->when($this->filterRuta, fn($q) => $q->where('t.route', $this->filterRuta))
            ->when($this->filterModalidad, fn($q) => $q->where('t.modality', $this->filterModalidad))
            ->when($this->filterEstado, fn($q) => $q->where('t.status', $this->filterEstado))
            ->when($this->filterCityId, fn($q) => $q->where('ts.city_id', $this->filterCityId))
            ->select(
                'c.name',
                DB::raw('COUNT(DISTINCT ts.training_id) as cap_realizadas'),
                DB::raw('COUNT(tp.id) as habilitados'),
                DB::raw('SUM(CASE WHEN tp.attended = 1 THEN 1 ELSE 0 END) as asistentes')
            )
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();

        return $rows->map(fn($r) => [
            'ciudad'           => $r->name,
            'cap_realizadas'   => $r->cap_realizadas,
            'habilitados'      => $r->habilitados,
            'asistentes'       => $r->asistentes,
            'pct'              => $r->habilitados > 0
                ? round(($r->asistentes / $r->habilitados) * 100, 1)
                : 0,
        ])->toArray();
    }

    // ── Distribución por Ruta (donut) ────────────────────────────────────────

    public function getDistribucionRuta(): array
    {
        $rows = (clone $this->sessionsQuery())
            ->select('t.route', DB::raw('COUNT(DISTINCT ts.training_id) as total'))
            ->groupBy('t.route')
            ->get();

        $map = $rows->pluck('total', 'route');

        return [
            'labels' => ['Ruta 1', 'Ruta 2', 'Ruta 3'],
            'data'   => [
                $map['route_1'] ?? 0,
                $map['route_2'] ?? 0,
                $map['route_3'] ?? 0,
            ],
        ];
    }

    // ── Asistencia por Ruta (barras de progreso) ─────────────────────────────

    public function getAsistenciaPorRuta(): array
    {
        $rows = $this->participationsQuery()
            ->select(
                DB::raw('COALESCE(tp.route_snapshot, t.route) as route'),
                DB::raw('COUNT(tp.id) as habilitados'),
                DB::raw('SUM(CASE WHEN tp.attended = 1 THEN 1 ELSE 0 END) as asistentes')
            )
            ->groupBy(DB::raw('COALESCE(tp.route_snapshot, t.route)'))
            ->get();

        $map = $rows->keyBy('route');

        $rutas = [
            'route_1' => 'Ruta 1: Pre-emprendimiento',
            'route_2' => 'Ruta 2: Consolidación',
            'route_3' => 'Ruta 3: Escalamiento',
        ];

        return collect($rutas)->map(fn($label, $key) => [
            'label'      => $label,
            'habilitados' => $map[$key]->habilitados ?? 0,
            'asistentes'  => $map[$key]->asistentes  ?? 0,
            'pct'         => isset($map[$key]) && $map[$key]->habilitados > 0
                ? round(($map[$key]->asistentes / $map[$key]->habilitados) * 100, 1)
                : 0,
        ])->values()->toArray();
    }

    // ── Intensidad horaria por Ruta ──────────────────────────────────────────

    public function getIntensidadPorRuta(): array
    {
        $rows = (clone $this->sessionsQuery())
            ->select('t.route', DB::raw('SUM(t.intensity_hours) as horas'))
            ->groupBy('t.route')
            ->get();

        $map = $rows->pluck('horas', 'route');

        return [
            'route_1' => round($map['route_1'] ?? 0, 1),
            'route_2' => round($map['route_2'] ?? 0, 1),
            'route_3' => round($map['route_3'] ?? 0, 1),
            'total'   => round(($map['route_1'] ?? 0) + ($map['route_2'] ?? 0) + ($map['route_3'] ?? 0), 1),
        ];
    }

    // ── Distribución por Modalidad (donut) ───────────────────────────────────

    public function getDistribucionModalidad(): array
    {
        $rows = (clone $this->sessionsQuery())
            ->select('t.modality', DB::raw('COUNT(DISTINCT ts.training_id) as total'))
            ->groupBy('t.modality')
            ->get();

        $map = $rows->pluck('total', 'modality');
        $total = $map->sum();

        return [
            'labels'   => ['Presencial', 'Virtual', 'Híbrida'],
            'data'     => [
                $map['in_person'] ?? 0,
                $map['virtual']   ?? 0,
                $map['hybrid']    ?? 0,
            ],
            'total'    => $total,
        ];
    }

    // ── Top capacitaciones ───────────────────────────────────────────────────

    public function getTopCapacitaciones(): array
    {
        return DB::table('training_sessions as ts')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->leftJoin('training_participations as tp', function ($j) {
                $j->on('tp.training_session_id', '=', 'ts.id')
                  ->whereNull('tp.deleted_at');
            })
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at')
            ->when($this->filterCityId, fn($q) => $q->where('ts.city_id', $this->filterCityId))
            ->when($this->filterRuta, fn($q) => $q->where('t.route', $this->filterRuta))
            ->when($this->filterModalidad, fn($q) => $q->where('t.modality', $this->filterModalidad))
            ->when($this->filterEstado, fn($q) => $q->where('t.status', $this->filterEstado))
            ->select(
                't.id',
                't.name',
                't.route',
                't.modality',
                DB::raw('COUNT(DISTINCT ts.id) as sesiones'),
                DB::raw('COUNT(tp.id) as habilitados'),
                DB::raw('SUM(CASE WHEN tp.attended = 1 THEN 1 ELSE 0 END) as asistentes')
            )
            ->groupBy('t.id', 't.name', 't.route', 't.modality')
            ->orderByDesc('asistentes')
            ->limit(8)
            ->get()
            ->toArray();
    }

    // ── Estado documental ────────────────────────────────────────────────────

    public function getEstadoDocumental(): array
    {
        $base = Training::withoutGlobalScopes()
            ->when($this->filterRuta, fn($q) => $q->where('route', $this->filterRuta))
            ->when($this->filterModalidad, fn($q) => $q->where('modality', $this->filterModalidad))
            ->when($this->filterCityId, fn($q) => $q->whereHas(
                'sessions', fn($s) => $s->where('city_id', $this->filterCityId)
            ));

        return [
            'completas'    => (clone $base)->where('status', 'complete')->count(),
            'pendientes'   => (clone $base)->where('status', 'support_pending')->count(),
            'sin_soportes' => (clone $base)->where('status', 'attendance_pending')->count(),
            'programadas'  => (clone $base)->where('status', 'scheduled')->count(),
        ];
    }

    // ── Datos de gráficos para la vista ─────────────────────────────────────

    public function getChartData(): array
    {
        $municipio  = $this->getAvancePorMunicipio();
        $ruta       = $this->getDistribucionRuta();
        $modalidad  = $this->getDistribucionModalidad();
        $empMun     = $this->getEmprendedoresPorMunicipio();

        return [
            'municipio_labels'     => array_column($municipio, 'ciudad'),
            'municipio_asistencia' => array_column($municipio, 'pct'),
            'ruta_labels'          => $ruta['labels'],
            'ruta_data'            => $ruta['data'],
            'modalidad_labels'     => $modalidad['labels'],
            'modalidad_data'       => $modalidad['data'],
            'emp_mun_labels'       => array_column($empMun, 'ciudad'),
            'emp_mun_data'         => array_column($empMun, 'emprendedores'),
        ];
    }

    public function getEmprendedoresPorMunicipio(): array
    {
        return DB::table('training_participations as tp')
            ->join('training_sessions as ts', 'tp.training_session_id', '=', 'ts.id')
            ->join('trainings as t', 'ts.training_id', '=', 't.id')
            ->join('cities as c', 'ts.city_id', '=', 'c.id')
            ->whereNull('tp.deleted_at')
            ->whereNull('ts.deleted_at')
            ->whereNull('t.deleted_at')
            ->where('tp.attended', true)
            ->when($this->filterCityId,    fn($q) => $q->where('ts.city_id', $this->filterCityId))
            ->when($this->filterRuta,      fn($q) => $q->where('t.route', $this->filterRuta))
            ->when($this->filterModalidad, fn($q) => $q->where('t.modality', $this->filterModalidad))
            ->when($this->filterEstado,    fn($q) => $q->where('t.status', $this->filterEstado))
            ->select('c.name as ciudad', DB::raw('COUNT(DISTINCT tp.entrepreneur_id) as emprendedores'))
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get()
            ->map(fn($r) => ['ciudad' => $r->ciudad, 'emprendedores' => (int) $r->emprendedores])
            ->toArray();
    }
}