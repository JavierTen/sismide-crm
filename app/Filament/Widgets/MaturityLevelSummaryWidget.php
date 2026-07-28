<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use App\Support\MaturityScale;
use App\Support\YearContext;
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

        $year  = YearContext::effectiveYear() ?? now()->year;
        $scale = MaturityScale::getScale($year);

        $counts    = [];
        $labels    = [];
        $fullNames = [];
        $colors    = [];

        foreach ($scale as $s) {
            $counts[]    = (clone $baseQuery)->whereBetween('total_score', [$s['min'], $s['max']])->count();
            $labels[]    = 'Nivel ' . $s['level'];
            $fullNames[] = $s['name'] . ' (' . $s['min'] . '-' . $s['max'] . ' pts)';
            $colors[]    = $s['color'];
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Emprendedores',
                    'data'            => $counts,
                    'backgroundColor' => $colors,
                    'borderRadius'    => 4,
                    'borderWidth'     => 0,
                    'fullNames'       => $fullNames,
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
                            var ds = context[0].dataset;
                            var i  = context[0].dataIndex;
                            return ds.fullNames ? ds.fullNames[i] : context[0].label;
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
                    'ticks' => [
                        'font'        => ['size' => 11],
                        'maxRotation' => 0,
                        'minRotation' => 0,
                    ],
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
