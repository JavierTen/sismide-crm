<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\BusinessDiagnosis;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
    /**
     * Obtiene los datos agrupados por ruta - VERSION OPTIMIZADA
     */
    public function getRoutesData(): array
    {
        return cache()->remember('routes_data', now()->addMinutes(10), function () {
            // Query directa a BD
            $ruta1 = BusinessDiagnosis::whereNotNull('total_score')
                ->whereIn('maturity_level', [
                    'Nivel 0: Pre-emprendimiento y validación temprana',
                    'Nivel 1: Pre-emprendimiento y validación temprana',
                    'Nivel 2: Pre-emprendimiento y validación temprana',
                ])
                ->count();

            $ruta2 = BusinessDiagnosis::whereNotNull('total_score')
                ->whereIn('maturity_level', [
                    'Nivel 3: Consolidación',
                    'Nivel 4: Consolidación',
                ])
                ->count();

            $ruta3 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('maturity_level', 'Nivel 5: Escalamiento')
                ->count();

            return [
                'ruta1' => [
                    'label' => 'Ruta 1: Pre-emprendimiento',
                    'total' => $ruta1,
                    'levels' => 'Niveles 0, 1, 2',
                ],
                'ruta2' => [
                    'label' => 'Ruta 2: Consolidación',
                    'total' => $ruta2,
                    'levels' => 'Niveles 3, 4',
                ],
                'ruta3' => [
                    'label' => 'Ruta 3: Escalamiento',
                    'total' => $ruta3,
                    'levels' => 'Nivel 5',
                ],
            ];
        });
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
    /**
     * Obtiene los datos de radar agrupados por ciudad
     * Basado en NIVELES, no en puntajes
     */
    public function getRadarDataByCity(): array
    {
        return cache()->remember('radar_data_by_city', now()->addMinutes(10), function () {
            $results = DB::table('business_diagnoses as bd')
                ->join('entrepreneurs as e', 'bd.entrepreneur_id', '=', 'e.id')
                ->join('businesses as b', 'e.id', '=', 'b.entrepreneur_id')
                ->join('cities as c', 'b.city_id', '=', 'c.id')
                ->whereNull('bd.deleted_at')
                ->whereNull('e.deleted_at')
                ->whereNull('b.deleted_at')
                ->whereNotNull('bd.total_score')
                ->whereNotNull('bd.maturity_level')
                ->select(
                    'c.name as city_name',
                    'bd.total_score',
                    'bd.maturity_level'
                )
                ->get();

            $cityData = [];

            foreach ($results as $row) {
                $cityName = $row->city_name;
                $score = $row->total_score;
                $level = $row->maturity_level;

                if (!isset($cityData[$cityName])) {
                    $cityData[$cityName] = [
                        'ruta1' => ['scores' => [], 'count' => 0, 'avg' => 0],
                        'ruta2' => ['scores' => [], 'count' => 0, 'avg' => 0],
                        'ruta3' => ['scores' => [], 'count' => 0, 'avg' => 0],
                    ];
                }

                // Clasificar por NIVEL, no por puntaje
                if (
                    str_contains($level, 'Nivel 0:') ||
                    str_contains($level, 'Nivel 1:') ||
                    str_contains($level, 'Nivel 2:')
                ) {
                    $ruta = 'ruta1';
                } elseif (
                    str_contains($level, 'Nivel 3:') ||
                    str_contains($level, 'Nivel 4:')
                ) {
                    $ruta = 'ruta2';
                } elseif (str_contains($level, 'Nivel 5:')) {
                    $ruta = 'ruta3';
                } else {
                    continue; // Si no coincide con ningún nivel, saltar
                }

                $cityData[$cityName][$ruta]['scores'][] = $score;
                $cityData[$cityName][$ruta]['count']++;
            }

            // Calcular promedios de puntajes
            foreach ($cityData as $cityName => &$data) {
                foreach (['ruta1', 'ruta2', 'ruta3'] as $ruta) {
                    if ($data[$ruta]['count'] > 0) {
                        $data[$ruta]['avg'] = round(
                            array_sum($data[$ruta]['scores']) / $data[$ruta]['count'],
                            1
                        );
                    }
                    unset($data[$ruta]['scores']);
                }
            }

            ksort($cityData);
            return $cityData;
        });
    }
}
