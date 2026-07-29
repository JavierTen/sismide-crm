<?php

namespace App\Filament\Pages;

use App\Support\MaturityScale;
use App\Support\YearContext;
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
     * Verificar si el usuario puede acceder a esta página
     */
    public static function canAccess(): bool
    {
        return auth()->user()->can('viewGeneralDiagnosis');
    }

    /**
     * Obtener datos de emprendimientos agrupados por municipio - ENTRADA
     */
    public function getEntrepreneursDataByCitiesEntry(): array
    {
        return $this->getEntrepreneursDataByCities('entry');
    }

    /**
     * Obtener datos de emprendimientos agrupados por municipio - SALIDA
     */
    public function getEntrepreneursDataByCitiesExit(): array
    {
        return $this->getEntrepreneursDataByCities('exit');
    }

    /**
     * Obtener datos de emprendimientos agrupados por municipio (método reutilizable)
     */
    private function getEntrepreneursDataByCities(string $diagnosisType): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city', 'entrepreneur.business'])
            ->where('diagnosis_type', $diagnosisType);

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

            $year   = $diagnosis->created_at?->year ?? now()->year;
            $phases = MaturityScale::getPhaseRanges($year);
            if ($totalScore >= $phases[1]['min'] && $totalScore <= $phases[1]['max']) {
                $route = $phases[1]['label'];
                $color = 'rgba(245, 158, 11, 0.5)';
                $borderColor = '#F59E0B';
            } elseif ($totalScore >= $phases[2]['min'] && $totalScore <= $phases[2]['max']) {
                $route = $phases[2]['label'];
                $color = 'rgba(59, 130, 246, 0.5)';
                $borderColor = '#3B82F6';
            } elseif ($totalScore >= $phases[3]['min'] && $totalScore <= $phases[3]['max']) {
                $route = $phases[3]['label'];
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
