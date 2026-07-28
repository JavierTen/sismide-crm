<?php

namespace App\Filament\Widgets;

use App\Support\MaturityScale;
use App\Support\YearContext;
use Filament\Widgets\ChartWidget;
use App\Models\BusinessDiagnosis;

class MaturityLevel3Widget extends ChartWidget
{
    protected static bool $isDiscoverable = false;

    protected static string $color = 'info';

    public function getHeading(): string
    {
        $s = MaturityScale::getScale(YearContext::effectiveYear() ?? now()->year)[3];
        return "Nivel 3: {$s['name']} ({$s['min']}-{$s['max']} pts)";
    }

    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 2,
        'lg' => 1,
        'xl' => 1
    ];

    protected static ?int $sort = 13;

    protected function getData(): array
    {
        $s    = MaturityScale::getScale(YearContext::effectiveYear() ?? now()->year)[3];
        $data = $this->getMunicipalityData($s['min'], $s['max']);

        // Obtener el total GENERAL de emprendimientos con diagnóstico
        $totalGeneral = $this->getTotalEntrepreneursWithDiagnosis();

        $labelsWithPercentages = [];

        foreach ($data['counts'] as $city => $count) {
            $percentage = $totalGeneral > 0 ? round(($count / $totalGeneral) * 100, 1) : 0;
            $labelsWithPercentages[] = $city . ' (' . $percentage . '%)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Emprendimientos',
                    'data' => array_values($data['counts']),
                    'backgroundColor' => $s['color'],
                    'borderColor' => $s['color'],
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labelsWithPercentages,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'title' => 'function(context) { return context[0].label.split(" (")[0]; }',
                        'label' => 'function(context) { return context.parsed.x + " emprendimientos"; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => '#F3F4F6'],
                    'ticks' => ['stepSize' => 1],
                ],
                'y' => [
                    'grid' => ['display' => false],
                    'ticks' => ['font' => ['size' => 11]],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    private function getMunicipalityData(int $minScore, int $maxScore): array
    {
        $query = BusinessDiagnosis::with(['entrepreneur.city'])
            ->whereBetween('total_score', [$minScore, $maxScore])
            ->whereHas('entrepreneur.city');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $distribution = $query->get()
            ->groupBy('entrepreneur.city.name')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(8)
            ->toArray();

        if (empty($distribution)) {
            return ['counts' => ['Sin datos' => 0]];
        }

        return ['counts' => $distribution];
    }

    private function getTotalEntrepreneursWithDiagnosis(): int
    {
        $query = BusinessDiagnosis::query();

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    protected static ?string $pollingInterval = '30s';
}
