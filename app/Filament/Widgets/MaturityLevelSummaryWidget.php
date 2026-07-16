<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use Filament\Widgets\ChartWidget;

class MaturityLevelSummaryWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución por Nivel de Madurez';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = '60s';

    protected static string $color = 'primary';

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $baseQuery = BusinessDiagnosis::query();
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $baseQuery->where('manager_id', auth()->id());
        }

        $levels = [
            ['label' => 'Nivel 0\nPotencial\nEmprendedor', 'min' => 0,  'max' => 15,  'color' => '#6B7280'],
            ['label' => 'Nivel 1\nIdeación',                'min' => 16, 'max' => 30,  'color' => '#EC4899'],
            ['label' => 'Nivel 2\nValidación',              'min' => 31, 'max' => 50,  'color' => '#F59E0B'],
            ['label' => 'Nivel 3\nPuesta en\nmarcha',       'min' => 51, 'max' => 70,  'color' => '#3B82F6'],
            ['label' => 'Nivel 4\nConsolidación',           'min' => 71, 'max' => 85,  'color' => '#F97316'],
            ['label' => 'Nivel 5\nEscalamiento',            'min' => 86, 'max' => 100, 'color' => '#8B5CF6'],
        ];

        $counts = [];
        $labels = [];
        $colors = [];

        foreach ($levels as $level) {
            $counts[]   = (clone $baseQuery)->whereBetween('total_score', [$level['min'], $level['max']])->count();
            $labels[]   = $level['label'];
            $colors[]   = $level['color'];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Emprendedores',
                    'data'            => $counts,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 4,
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'title' => 'function(context) {
                            return context[0].label.replace(/\\n/g, " ");
                        }',
                        'label' => 'function(context) {
                            return context.parsed.y + " emprendedores";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['font' => ['size' => 10]],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid'        => ['color' => '#F3F4F6'],
                    'ticks'       => ['stepSize' => 1],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive'          => true,
        ];
    }
}
