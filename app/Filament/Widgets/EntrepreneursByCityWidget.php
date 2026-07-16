<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Entrepreneur;
use App\Models\City;

class EntrepreneursByCityWidget extends ChartWidget
{
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'Emprendedores por municipio';

    protected static string $color = 'danger';

    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 2,
        'lg' => 1,
        'xl' => 1
    ];

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $cityData = $this->getCityDistribution();

        // Agregar porcentajes a las etiquetas
        $total = array_sum($cityData['counts']);
        $labelsWithPercentages = [];

        foreach ($cityData['counts'] as $city => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            $labelsWithPercentages[] = $city . ' (' . $percentage . '%)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Emprendedores',
                    'data' => array_values($cityData['counts']),
                    'backgroundColor' => '#DC2626', // Rojo
                    'borderColor' => '#B91C1C',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labelsWithPercentages,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Mantener como 'bar'
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Esta línea hace que las barras sean horizontales
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'title' => 'function(context) {
                            return context[0].label.split(" (")[0]; // Solo el nombre sin porcentaje
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

    private function getCityDistribution(): array
    {
        // Aplicar filtro por rol
        $query = Entrepreneur::with('city')
            ->whereHas('city');

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        // Obtener la distribución por ciudad, limitando a los top 8
        $distribution = $query->get()
            ->groupBy('city.name')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(8)
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
