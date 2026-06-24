<?php

namespace App\Filament\Eje\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentsByGenderWidget extends ChartWidget
{
    protected static ?string $heading = 'Estudiantes por género';

    protected static string $color = 'info';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $distribution = $this->getDistribution();

        return [
            'datasets' => [
                [
                    'data' => array_values($distribution),
                    'backgroundColor' => ['#3B82F6', '#DC2626', '#F59E0B', '#6B7280'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($distribution),
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
                ],
            ],
            'cutout' => '60%',
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    private function getDistribution(): array
    {
        $query = Student::with('gender');

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $distribution = $query->get()
            ->groupBy(fn (Student $student) => $student->gender?->name ?? 'Sin especificar')
            ->map(fn ($group) => $group->count())
            ->toArray();

        return empty($distribution) ? ['Sin datos' => 0] : $distribution;
    }
}
