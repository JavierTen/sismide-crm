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

        $year   = YearContext::effectiveYear() ?? now()->year;
        $scale  = MaturityScale::getScale($year);
        $levels = array_map(fn($s) => [
            'label' => ['Nivel ' . $s['level'], $s['name']],
            'min'   => $s['min'],
            'max'   => $s['max'],
            'color' => $s['color'],
        ], $scale);

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
                            var lbl = context[0].label;
                            return Array.isArray(lbl) ? lbl.join(" ") : lbl;
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
                        'font'     => ['size' => 10],
                        'autoSkip' => false,
                        'maxRotation' => 30,
                        'minRotation' => 30,
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
