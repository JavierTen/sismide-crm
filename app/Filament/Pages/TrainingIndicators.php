<?php

namespace App\Filament\Pages;

use App\Models\Training;
use App\Models\TrainingParticipation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class TrainingIndicators extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static string $view = 'filament.pages.training-indicators';

    protected static ?string $navigationLabel = 'Indicadores de Capacitaciones';

    protected static ?string $title = 'Indicadores de Capacitaciones';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()->can('listTrainings') || auth()->user()->hasRole(['Admin', 'Viewer']);
    }

    public function getGeneralStats(): array
    {
        return [
            'total_trainings' => Training::count(),
            'total_unique_entrepreneurs' => TrainingParticipation::distinct('entrepreneur_id')->count('entrepreneur_id'),
            'total_virtual' => Training::where('modality', 'virtual')->count(),
            'total_in_person' => Training::where('modality', 'in_person')->count(),
            'total_hybrid' => Training::where('modality', 'hybrid')->count(),
            'total_route_1' => Training::where('route', 'route_1')->count(),
            'total_route_2' => Training::where('route', 'route_2')->count(),
            'total_route_3' => Training::where('route', 'route_3')->count(),
        ];
    }

    public function getTrainingsByCity(): array
    {
        $cities = TrainingParticipation::join('entrepreneurs', 'training_participations.entrepreneur_id', '=', 'entrepreneurs.id')
            ->join('cities', 'entrepreneurs.city_id', '=', 'cities.id')
            ->whereNull('training_participations.deleted_at')
            ->where('training_participations.attended', true)
            ->select('cities.name', DB::raw('count(DISTINCT training_participations.entrepreneur_id) as total'))
            ->groupBy('cities.id', 'cities.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $cities->pluck('name')->toArray(),
            'data' => $cities->pluck('total')->toArray(),
        ];
    }

    public function getParticipationByTraining(): array
    {
        $participations = DB::table('training_participations')
            ->join('trainings', 'training_participations.training_id', '=', 'trainings.id')
            ->whereNull('training_participations.deleted_at')
            ->whereNull('trainings.deleted_at')
            ->where('attended', true)
            ->select('trainings.name', DB::raw('count(*) as total'))
            ->groupBy('training_participations.training_id', 'trainings.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $participations->pluck('name')->toArray(),
            'data' => $participations->pluck('total')->toArray(),
        ];
    }

    public function getUniqueEntrepreneursByRoute(): array
    {
        $route1 = TrainingParticipation::join('trainings', 'training_participations.training_id', '=', 'trainings.id')
            ->where('trainings.route', 'route_1')
            ->distinct('training_participations.entrepreneur_id')
            ->count('training_participations.entrepreneur_id');

        $route2 = TrainingParticipation::join('trainings', 'training_participations.training_id', '=', 'trainings.id')
            ->where('trainings.route', 'route_2')
            ->distinct('training_participations.entrepreneur_id')
            ->count('training_participations.entrepreneur_id');

        $route3 = TrainingParticipation::join('trainings', 'training_participations.training_id', '=', 'trainings.id')
            ->where('trainings.route', 'route_3')
            ->distinct('training_participations.entrepreneur_id')
            ->count('training_participations.entrepreneur_id');

        return [
            'labels' => ['Ruta 1: Pre-emprendimiento', 'Ruta 2: Consolidación', 'Ruta 3: Escalamiento'],
            'data' => [$route1, $route2, $route3],
        ];
    }

    public function getTrainingsByRoute(): array
    {
        return [
            'labels' => ['Ruta 1: Pre-emprendimiento', 'Ruta 2: Consolidación', 'Ruta 3: Escalamiento'],
            'data' => [
                Training::where('route', 'route_1')->count(),
                Training::where('route', 'route_2')->count(),
                Training::where('route', 'route_3')->count(),
            ],
        ];
    }

    public function getAverageIntensityByRoute(): array
    {
        $data = Training::select('route', DB::raw('SUM(intensity_hours) as total_hours'))
            ->whereNotNull('intensity_hours')
            ->groupBy('route')
            ->get()
            ->pluck('total_hours', 'route')
            ->toArray();

        return [
            'labels' => ['Ruta 1: Pre-emprendimiento', 'Ruta 2: Consolidación', 'Ruta 3: Escalamiento'],
            'data' => [
                round($data['route_1'] ?? 0, 1),
                round($data['route_2'] ?? 0, 1),
                round($data['route_3'] ?? 0, 1),
            ],
        ];
    }
}
