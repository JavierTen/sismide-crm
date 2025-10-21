<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Entrepreneur;

class EntrepreneursByPopulationWidget extends ChartWidget
{
    protected static ?string $heading = 'Emprendedores por población vulnerable';

    protected static string $color = 'warning';

    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 2,
        'lg' => 1,
        'xl' => 1
    ];

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $populationData = $this->getPopulationDistribution();

        // Agregar porcentajes a las etiquetas
        $total = array_sum($populationData['counts']);
        $labelsWithPercentages = [];

        foreach ($populationData['counts'] as $population => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            $labelsWithPercentages[] = $population . ' (' . $percentage . '%)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Emprendedores',
                    'data' => array_values($populationData['counts']),
                    'backgroundColor' => '#8B5CF6', // Naranja/Amarillo
                    'borderColor' => '#7C3AED',
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
            'indexAxis' => 'y', // Barras horizontales
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'title' => 'function(context) {
                            return context[0].label.split(" (")[0];
                        }',
                        'label' => 'function(context) {
                            return context.parsed.x + " emprendedores";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => '#F3F4F6',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    private function getPopulationDistribution(): array
    {
        // Aplicar filtro por rol
        $query = Entrepreneur::with('population')
            ->whereHas('population');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        // Obtener la distribución por población vulnerable
        $distribution = $query->get()
            ->groupBy('population.name')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->toArray();

        if (empty($distribution)) {
            return [
                'counts' => [
                    'Sin datos' => 0,
                ],
            ];
        }

        return [
            'counts' => $distribution,
        ];
    }

    protected static ?string $pollingInterval = '30s';
}
