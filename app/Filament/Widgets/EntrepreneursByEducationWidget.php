<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Entrepreneur;

class EntrepreneursByEducationWidget extends ChartWidget
{
    protected static ?string $heading = 'Emprendedores por nivel de educación';

    protected static string $color = 'success';

    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 2,
        'lg' => 1,
        'xl' => 1
    ];

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $educationData = $this->getEducationDistribution();

        // Agregar porcentajes a las etiquetas
        $total = array_sum($educationData['counts']);
        $labelsWithPercentages = [];

        foreach ($educationData['counts'] as $education => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            $labelsWithPercentages[] = $education . ' (' . $percentage . '%)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Emprendedores',
                    'data' => array_values($educationData['counts']),
                    'backgroundColor' => '#10B981', // Verde
                    'borderColor' => '#059669',
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

    private function getEducationDistribution(): array
    {
        // Aplicar filtro por rol
        $query = Entrepreneur::with('educationLevel')
            ->whereHas('educationLevel');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        // Obtener la distribución por nivel de educación
        $distribution = $query->get()
            ->groupBy('educationLevel.name')
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
