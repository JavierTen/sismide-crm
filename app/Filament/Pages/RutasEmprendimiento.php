<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\BusinessDiagnosis;
use App\Models\City;

class RutasEmprendimiento extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.rutas-emprendimiento';

    protected static ?string $navigationLabel = 'Radar de Emprendimiento';

    protected static ?string $title = 'Radar de Emprendimiento';

    protected static ?int $navigationSort = 3;

    /**
     * Obtener datos para las rutas
     */
    public function getRoutesData(): array
    {
        return [
            'ruta1' => $this->getRouteData(0, 50, 'Ruta 1: Pre-emprendimiento y validación temprana (Niveles 0, 1, 2)'),
            'ruta2' => $this->getRouteData(51, 85, 'Ruta 2: Consolidación (Niveles 3, 4)'),
            'ruta3' => $this->getRouteData(86, 100, 'Ruta 3: Escalamiento (Nivel 5)'),
        ];
    }

    /**
     * Obtener datos de una ruta específica
     */
    private function getRouteData(int $minScore, int $maxScore, string $label): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city'])
            ->whereBetween('total_score', [$minScore, $maxScore])
            ->whereHas('entrepreneur.city');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $total = $query->count();

        $distribution = $query->get()
            ->groupBy('entrepreneur.city.name')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->toArray();

        return [
            'label' => $label,
            'total' => $total,
            'distribution' => $distribution,
        ];
    }

    /**
     * Obtener datos para gráfico de radar por municipio
     */
    public function getRadarDataByCity(): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city'])
            ->whereHas('entrepreneur.city');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $diagnoses = $query->get();

        // Agrupar por municipio
        $citiesData = [];

        foreach ($diagnoses as $diagnosis) {
            $cityName = $diagnosis->entrepreneur->city->name ?? 'Sin municipio';

            if (!isset($citiesData[$cityName])) {
                $citiesData[$cityName] = [
                    'ruta1' => ['total' => 0, 'count' => 0, 'avg' => 0, 'emprendimientos' => []],
                    'ruta2' => ['total' => 0, 'count' => 0, 'avg' => 0, 'emprendimientos' => []],
                    'ruta3' => ['total' => 0, 'count' => 0, 'avg' => 0, 'emprendimientos' => []],
                ];
            }

            $score = $diagnosis->total_score ?? 0;

            // Clasificar en rutas y guardar info del emprendimiento
            $emprendimientoInfo = [
                'nombre' => $diagnosis->entrepreneur->full_name ?? 'N/A',
                'puntaje' => $score
            ];

            if ($score >= 0 && $score <= 30) {
                $citiesData[$cityName]['ruta1']['total'] += $score;
                $citiesData[$cityName]['ruta1']['count']++;
                $citiesData[$cityName]['ruta1']['emprendimientos'][] = $emprendimientoInfo;
            } elseif ($score >= 31 && $score <= 70) {
                $citiesData[$cityName]['ruta2']['total'] += $score;
                $citiesData[$cityName]['ruta2']['count']++;
                $citiesData[$cityName]['ruta2']['emprendimientos'][] = $emprendimientoInfo;
            } elseif ($score >= 71 && $score <= 100) {
                $citiesData[$cityName]['ruta3']['total'] += $score;
                $citiesData[$cityName]['ruta3']['count']++;
                $citiesData[$cityName]['ruta3']['emprendimientos'][] = $emprendimientoInfo;
            }
        }

        // Calcular promedios
        foreach ($citiesData as $cityName => &$data) {
            foreach (['ruta1', 'ruta2', 'ruta3'] as $ruta) {
                if ($data[$ruta]['count'] > 0) {
                    $data[$ruta]['avg'] = round($data[$ruta]['total'] / $data[$ruta]['count'], 1);
                }
            }
        }

        return $citiesData;
    }
}
