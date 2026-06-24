<?php

namespace App\Filament\Eje\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentsByGradeWidget extends ChartWidget
{
    protected static ?string $heading = 'Estudiantes por grado';

    protected static string $color = 'success';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $distribution = $this->getDistribution();

        return [
            'datasets' => [
                [
                    'data' => array_values($distribution),
                    'backgroundColor' => ['#3B82F6', '#F59E0B'],
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
        $query = Student::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $gradeOptions = Student::gradeOptions();

        $distribution = $query->get()
            ->groupBy('grade')
            ->map(fn ($group) => $group->count())
            ->mapWithKeys(fn ($count, $grade) => [$gradeOptions[$grade] ?? $grade => $count])
            ->toArray();

        return empty($distribution) ? ['Sin datos' => 0] : $distribution;
    }
}
