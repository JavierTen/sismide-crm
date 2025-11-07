<?php

namespace App\Filament\Emprendedor\Pages;

use Filament\Pages\Page;
use App\Models\BusinessDiagnosis;

class MiDiagnostico extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.emprendedor.pages.mi-diagnostico';

    protected static ?string $navigationLabel = 'Mi Diagnóstico';

    protected static ?string $title = 'Mi Diagnóstico Empresarial';

    protected static ?int $navigationSort = 2;

    public function getDiagnosisData(): ?array
    {
        $entrepreneur = auth()->user();

        $diagnosis = BusinessDiagnosis::where('entrepreneur_id', $entrepreneur->id)
            ->with(['entrepreneur.business', 'manager'])
            ->latest()
            ->first();

        if (!$diagnosis) {
            return null;
        }

        $totalScore = $diagnosis->total_score ?? 0;

        // Determinar ruta/fase
        $route = '';
        $color = '';
        $borderColor = '';

        if ($totalScore >= 0 && $totalScore <= 50) {
            $route = 'Fase 1: Pre-emprendimiento y validación temprana';
            $color = 'rgba(245, 158, 11, 0.5)';
            $borderColor = '#F59E0B';
        } elseif ($totalScore >= 51 && $totalScore <= 85) {
            $route = 'Fase 2: Consolidación';
            $color = 'rgba(59, 130, 246, 0.5)';
            $borderColor = '#3B82F6';
        } elseif ($totalScore >= 86 && $totalScore <= 100) {
            $route = 'Fase 3: Escalamiento';
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

        return [
            'diagnosis' => $diagnosis,
            'business_name' => $entrepreneur->business->business_name ?? 'Mi Emprendimiento',
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
            'observations' => $diagnosis->general_observations ?? '',
            'diagnosis_date' => $diagnosis->diagnosis_date,
            'manager' => $diagnosis->manager,
        ];
    }

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
