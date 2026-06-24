<?php

namespace App\Filament\Eje\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;

class CharacterizationCoverageWidget extends ChartWidget
{
    protected static ?string $heading = 'Cobertura de caracterización';

    protected static string $color = 'info';

    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        [$characterized, $pending] = $this->getCounts();

        return [
            'datasets' => [
                [
                    'data' => [$characterized, $pending],
                    'backgroundColor' => ['#10B981', '#F59E0B'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Caracterizados', 'Pendientes'],
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

    private function getCounts(): array
    {
        $query = Student::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        $characterized = (clone $query)->whereHas('characterizations')->count();
        $pending = (clone $query)->whereDoesntHave('characterizations')->count();

        return [$characterized, $pending];
    }
}
