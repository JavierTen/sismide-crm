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
     * Verificar si el usuario puede acceder a esta página
     */
    public static function canAccess(): bool
    {
        return auth()->user()->can('viewEntrepreneurshipRadar');
    }

    /**
     * Obtener datos para las rutas - ENTRADA
     */
    public function getRoutesDataEntry(): array
    {
        return cache()->remember('routes_data_entry', now()->addMinutes(10), function () {
            $ruta1 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'entry') // ← FILTRO ENTRADA
                ->whereIn('maturity_level', [
                    'Nivel 0: Pre-emprendimiento y validación temprana',
                    'Nivel 1: Pre-emprendimiento y validación temprana',
                    'Nivel 2: Pre-emprendimiento y validación temprana',
                ])
                ->count();

            $ruta2 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'entry') // ← FILTRO ENTRADA
                ->whereIn('maturity_level', [
                    'Nivel 3: Consolidación',
                    'Nivel 4: Consolidación',
                ])
                ->count();

            $ruta3 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'entry') // ← FILTRO ENTRADA
                ->where('maturity_level', 'Nivel 5: Escalamiento e Innovación')
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
     * Obtener datos para las rutas - SALIDA
     */
    public function getRoutesDataExit(): array
    {
        return cache()->remember('routes_data_exit', now()->addMinutes(10), function () {
            $ruta1 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'exit') // ← FILTRO SALIDA
                ->whereIn('maturity_level', [
                    'Nivel 0: Pre-emprendimiento y validación temprana',
                    'Nivel 1: Pre-emprendimiento y validación temprana',
                    'Nivel 2: Pre-emprendimiento y validación temprana',
                ])
                ->count();

            $ruta2 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'exit') // ← FILTRO SALIDA
                ->whereIn('maturity_level', [
                    'Nivel 3: Consolidación',
                    'Nivel 4: Consolidación',
                ])
                ->count();

            $ruta3 = BusinessDiagnosis::whereNotNull('total_score')
                ->where('diagnosis_type', 'exit') // ← FILTRO SALIDA
                ->where('maturity_level', 'Nivel 5: Escalamiento e Innovación')
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
     * Obtener datos de radar por ciudad - ENTRADA
     */
    public function getRadarDataByCityEntry(): array
    {
        return cache()->remember('radar_data_by_city_entry', now()->addMinutes(10), function () {
            $results = DB::table('business_diagnoses as bd')
                ->join('entrepreneurs as e', 'bd.entrepreneur_id', '=', 'e.id')
                ->join('businesses as b', 'e.id', '=', 'b.entrepreneur_id')
                ->join('cities as c', 'b.city_id', '=', 'c.id')
                ->whereNull('bd.deleted_at')
                ->whereNull('e.deleted_at')
                ->whereNull('b.deleted_at')
                ->where('bd.diagnosis_type', 'entry') // ← FILTRO ENTRADA
                ->whereNotNull('bd.total_score')
                ->whereNotNull('bd.maturity_level')
                ->select(
                    'c.name as city_name',
                    'bd.total_score',
                    'bd.maturity_level'
                )
                ->get();

            return $this->processRadarData($results);
        });
    }

    /**
     * Obtener datos de radar por ciudad - SALIDA
     */
    public function getRadarDataByCityExit(): array
    {
        return cache()->remember('radar_data_by_city_exit', now()->addMinutes(10), function () {
            $results = DB::table('business_diagnoses as bd')
                ->join('entrepreneurs as e', 'bd.entrepreneur_id', '=', 'e.id')
                ->join('businesses as b', 'e.id', '=', 'b.entrepreneur_id')
                ->join('cities as c', 'b.city_id', '=', 'c.id')
                ->whereNull('bd.deleted_at')
                ->whereNull('e.deleted_at')
                ->whereNull('b.deleted_at')
                ->where('bd.diagnosis_type', 'exit') // ← FILTRO SALIDA
                ->whereNotNull('bd.total_score')
                ->whereNotNull('bd.maturity_level')
                ->select(
                    'c.name as city_name',
                    'bd.total_score',
                    'bd.maturity_level'
                )
                ->get();

            return $this->processRadarData($results);
        });
    }

    /**
     * Procesar datos de radar (método reutilizable)
     */
    private function processRadarData($results): array
    {
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

            // Clasificar por NIVEL
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
                continue;
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
    }
}
