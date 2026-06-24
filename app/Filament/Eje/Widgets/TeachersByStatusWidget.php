<?php

namespace App\Filament\Eje\Widgets;

use App\Models\Teacher;
use Filament\Widgets\ChartWidget;

class TeachersByStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Docentes por estado';

    protected static string $color = 'warning';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $distribution = $this->getDistribution();

        return [
            'datasets' => [
                [
                    'label' => 'Docentes',
                    'data' => array_values($distribution),
                    'backgroundColor' => ['#10B981', '#6B7280', '#DC2626'],
                    'borderWidth' => 0,
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
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
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
        $query = Teacher::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $statusOptions = Teacher::statusOptions();

        $distribution = $query->get()
            ->groupBy('status')
            ->map(fn ($group) => $group->count())
            ->mapWithKeys(fn ($count, $status) => [$statusOptions[$status] ?? $status => $count])
            ->toArray();

        return empty($distribution) ? ['Sin datos' => 0] : $distribution;
    }
}
