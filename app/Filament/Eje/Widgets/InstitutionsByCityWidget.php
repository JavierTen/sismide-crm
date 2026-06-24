<?php

namespace App\Filament\Eje\Widgets;

use App\Models\EducationalInstitution;
use Filament\Widgets\ChartWidget;

class InstitutionsByCityWidget extends ChartWidget
{
    protected static ?string $heading = 'Instituciones por municipio';

    protected static string $color = 'primary';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 2,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $distribution = $this->getDistribution();
        $total = array_sum($distribution);

        $labels = [];

        foreach ($distribution as $city => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            $labels[] = "{$city} ({$percentage}%)";
        }

        return [
            'datasets' => [
                [
                    'label' => 'Instituciones',
                    'data' => array_values($distribution),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
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
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    private function getDistribution(): array
    {
        $query = EducationalInstitution::with('city')->whereHas('city');

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $distribution = $query->get()
            ->groupBy('city.name')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        return empty($distribution) ? ['Sin datos' => 0] : $distribution;
    }
}
