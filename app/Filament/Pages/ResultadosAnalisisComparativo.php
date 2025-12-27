<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\BusinessDiagnosis;

class ResultadosAnalisisComparativo extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.resultados-analisis-comparativo';

    protected static ?string $navigationLabel = 'Resultados Análisis Comparativo';

    protected static ?string $title = 'Resultados Análisis Comparativo por Rutas';

    protected static ?int $navigationSort = 4;

    /**
     * Verificar si el usuario puede acceder a esta página
     */
    public static function canAccess(): bool
    {
        return auth()->user()->can('viewComparativeAnalysis');
    }

    /**
     * Obtener datos agrupados por municipio y ruta - ENTRADA
     */
    public function getDataByRouteEntry(): array
    {
        return $this->getDataByRoute('entry');
    }

    /**
     * Obtener datos agrupados por municipio y ruta - SALIDA
     */
    public function getDataByRouteExit(): array
    {
        return $this->getDataByRoute('exit');
    }

    /**
     * Obtener datos agrupados por municipio y ruta (método reutilizable)
     */
    private function getDataByRoute(string $diagnosisType): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city', 'entrepreneur.business'])
            ->where('diagnosis_type', $diagnosisType);

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $diagnoses = $query->get();

        $data = [];

        foreach ($diagnoses as $diagnosis) {
            $cityName = $diagnosis->entrepreneur?->city?->name ?? 'Sin municipio';
            $totalScore = $diagnosis->total_score ?? 0;

            // Determinar la ruta según el puntaje
            $route = $this->getRouteByScore($totalScore);

            if (!isset($data[$cityName])) {
                $data[$cityName] = [
                    'ruta1' => [],
                    'ruta2' => [],
                    'ruta3' => [],
                ];
            }

            // Calcular puntajes por sección
            $adminScore = $this->calculateSectionScore(
                $diagnosis->administrative_section,
                BusinessDiagnosis::getAdministrativeScoring()
            );
            $financialScore = $this->calculateSectionScore(
                $diagnosis->financial_section,
                BusinessDiagnosis::getFinancialScoring()
            );
            $productionScore = $this->calculateSectionScore(
                $diagnosis->production_section,
                BusinessDiagnosis::getProductionScoring()
            );
            $marketScore = $this->calculateSectionScore(
                $diagnosis->market_section,
                BusinessDiagnosis::getMarketScoring()
            );
            $technologyScore = $this->calculateSectionScore(
                $diagnosis->technology_section,
                BusinessDiagnosis::getTechnologyScoring()
            );

            $entrepreneurData = [
                'name' => $diagnosis->entrepreneur->full_name ?? 'N/A',
                'business_name' => $diagnosis->entrepreneur->business->business_name ?? 'N/A',
                'total_score' => $totalScore,
                'maturity_level' => $diagnosis->maturity_level ?? 'Sin nivel',
                'diagnosis_date' => $diagnosis->diagnosis_date?->format('d/m/Y') ?? 'Sin fecha',
                'scores' => [
                    'administrative' => $adminScore,
                    'financial' => $financialScore,
                    'production' => $productionScore,
                    'market' => $marketScore,
                    'technology' => $technologyScore,
                ],
            ];

            // Agregar a la ruta correspondiente
            $data[$cityName][$route][] = $entrepreneurData;
        }

        return $data;
    }

    /**
     * Calcular puntaje de una sección
     */
    private function calculateSectionScore(?array $section, array $scoring): int
    {
        if (empty($section)) {
            return 0;
        }

        $score = 0;
        foreach ($section as $question => $answer) {
            if (isset($scoring[$question][$answer])) {
                $score += $scoring[$question][$answer];
            }
        }

        return $score;
    }

    /**
     * Determinar la ruta según el puntaje
     */
    private function getRouteByScore(int $score): string
    {
        if ($score >= 0 && $score <= 50) {
            return 'ruta1';
        } elseif ($score >= 51 && $score <= 85) {
            return 'ruta2';
        } else {
            return 'ruta3';
        }
    }

    /**
     * Obtener totales por ruta
     */
    public function getTotalsByRoute(string $diagnosisType): array
    {
        $data = $this->getDataByRoute($diagnosisType);

        $totals = [
            'ruta1' => 0,
            'ruta2' => 0,
            'ruta3' => 0,
        ];

        foreach ($data as $cityData) {
            $totals['ruta1'] += count($cityData['ruta1']);
            $totals['ruta2'] += count($cityData['ruta2']);
            $totals['ruta3'] += count($cityData['ruta3']);
        }

        return $totals;
    }
}
