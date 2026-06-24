<?php

namespace App\Filament\Eje\Widgets;

use App\Models\StudentCharacterization;
use Filament\Widgets\ChartWidget;

class StudentsByMainInterestWidget extends ChartWidget
{
    protected static ?string $heading = 'Estudiantes por interés principal';

    protected static string $color = 'success';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 2,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $distribution = $this->getDistribution();

        return [
            'datasets' => [
                [
                    'label' => 'Estudiantes',
                    'data' => array_values($distribution),
                    'backgroundColor' => '#8B5CF6',
                    'borderColor' => '#7C3AED',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_keys($distribution),
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
        $query = StudentCharacterization::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $options = StudentCharacterization::mainInterestOptions();

        $distribution = $query->whereNotNull('main_interest')
            ->get()
            ->groupBy('main_interest')
            ->map(fn ($group) => $group->count())
            ->mapWithKeys(fn ($count, $interest) => [$options[$interest] ?? $interest => $count])
            ->toArray();

        return empty($distribution) ? ['Sin datos' => 0] : $distribution;
    }
}
