<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Entrepreneur;
use App\Models\Gender;

class GenderDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Género';

    protected static string $color = 'info';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2
    ];

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $genderData = $this->getGenderDistribution();
        $total = array_sum($genderData['counts']);

        // Crear etiquetas con nombres y porcentajes
        $labelsWithPercentages = [];

        foreach ($genderData['counts'] as $label => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            $labelsWithPercentages[] = $label . ' (' . $percentage . '%)';
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($genderData['counts']),
                    'backgroundColor' => [
                        '#3B82F6', // Azul
                        '#DC2626', // Rojo
                        '#F59E0B', // Naranja
                        '#6B7280', // Gris
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labelsWithPercentages,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const data = context.dataset.data;
                            const total = data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.parsed / total) * 100);
                            const originalLabels = ["Femenino", "Masculino", "LGTBIQ+", "Otro"];
                            const genderName = originalLabels[context.dataIndex] || context.label.split(" (")[0];
                            return genderName + ": " + context.parsed + " emprendedores (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
            'cutout' => '60%',
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    private function getGenderDistribution(): array
    {
        // Aplicar filtro por rol
        $query = Entrepreneur::with('gender');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        // Obtener la distribución por género con nombres
        $distribution = $query->get()
            ->groupBy('gender.name')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        // Si no hay datos, mostrar estructura vacía
        if (empty($distribution)) {
            return [
                'counts' => [
                    'Femenino' => 0,
                    'Masculino' => 0,
                    'LGTBIQ+' => 0,
                    'Otro' => 0,
                ],
            ];
        }

        return [
            'counts' => $distribution,
        ];
    }

    // Actualización automática cada 30 segundos
    protected static ?string $pollingInterval = '30s';
}
