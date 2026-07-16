<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class RutaDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución por Ruta';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';

    protected static string $color = 'danger';

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $goal = Project::where('status', true)->value('participant_goal') ?? 300;

        $baseQuery = BusinessDiagnosis::query();
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $baseQuery->where('manager_id', auth()->id());
        }

        $ruta1 = (clone $baseQuery)->where('total_score', '<=', 50)->count();
        $ruta2 = (clone $baseQuery)->whereBetween('total_score', [51, 85])->count();
        $ruta3 = (clone $baseQuery)->where('total_score', '>=', 86)->count();

        $total = $ruta1 + $ruta2 + $ruta3;
        $pct = fn($n) => $total > 0 ? round(($n / $total) * 100) : 0;

        return [
            'datasets' => [
                [
                    'data'            => [$ruta1, $ruta2, $ruta3],
                    'backgroundColor' => ['#DC2626', '#1D4ED8', '#16A34A'],
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => [
                "Ruta 1 — {$ruta1} ({$pct($ruta1)}%)\nNiveles 0, 1 y 2",
                "Ruta 2 — {$ruta2} ({$pct($ruta2)}%)\nNiveles 3 y 4",
                "Ruta 3 — {$ruta3} ({$pct($ruta3)}%)\nNivel 5",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout'  => '60%',
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'right',
                    'labels'   => [
                        'boxWidth' => 14,
                        'padding'  => 12,
                        'font'     => ['size' => 12],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label.split("\n")[0];
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'maintainAspectRatio' => false,
            'responsive'          => true,
        ];
    }
}
