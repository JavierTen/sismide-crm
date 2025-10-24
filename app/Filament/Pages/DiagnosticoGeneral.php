<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\BusinessDiagnosis;

class DiagnosticoGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.diagnostico-general';

    protected static ?string $navigationLabel = 'Diagnóstico General';

    protected static ?string $title = 'Diagnóstico General por Emprendimiento';

    protected static ?int $navigationSort = 4;

    /**
     * Obtener datos de emprendimientos agrupados por municipio
     */
    public function getEntrepreneursDataByCities(): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city']);

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $diagnoses = $query->get();

        $citiesData = [];

        foreach ($diagnoses as $diagnosis) {
            $cityName = $diagnosis->entrepreneur?->city?->name ?? 'Sin municipio';

            if (!isset($citiesData[$cityName])) {
                $citiesData[$cityName] = [];
            }

            $totalScore = $diagnosis->total_score ?? 0;

            // Determinar ruta/fase
            $route = '';
            $color = '';
            $borderColor = '';

            if ($totalScore >= 0 && $totalScore <= 50) {
                $route = 'Fase 1: Nivel 0,1,2 - Pre-emprendimiento';
                $color = 'rgba(245, 158, 11, 0.5)';
                $borderColor = '#F59E0B';
            } elseif ($totalScore >= 51 && $totalScore <= 85) {
                $route = 'Fase 2: Nivel 3,4 - Consolidación';
                $color = 'rgba(59, 130, 246, 0.5)';
                $borderColor = '#3B82F6';
            } elseif ($totalScore >= 86 && $totalScore <= 100) {
                $route = 'Fase 3: Nivel 5 - Escalamiento';
                $color = 'rgba(16, 185, 129, 0.5)';
                $borderColor = '#10B981';
            }

            // Calcular puntaje de cada sección
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

            $citiesData[$cityName][] = [
                'name' => $diagnosis->entrepreneur->full_name ?? 'N/A',
                'business_name' => $diagnosis->entrepreneur->business->business_name ?? 'N/A',
                'route' => $route,
                'total_score' => $totalScore,
                'color' => $color,
                'borderColor' => $borderColor,
                'scores' => [
                    'administrative' => $adminScore,
                    'financial' => $financialScore,
                    'production' => $productionScore,
                    'market' => $marketScore,
                    'technology' => $technologyScore,
                ],
            ];
        }

        return $citiesData;
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
}
