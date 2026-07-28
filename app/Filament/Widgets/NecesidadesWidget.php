<?php

namespace App\Filament\Widgets;

use App\Models\Characterization;
use Filament\Widgets\ChartWidget;

class NecesidadesWidget extends ChartWidget
{
    protected static ?string $heading = 'Necesidades Más Frecuentes';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = '60s';

    protected static string $color = 'warning';

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    private static array $needLabels = [
        'marketing'           => 'Marketing',
        'financiero'          => 'Financiero',
        'formalizacion'       => 'Formalización',
        'produccion'          => 'Producción',
        'acceso_financiacion' => 'Financiación',
        'tecnologia'          => 'Tecnología',
        'innovacion'          => 'Innovación',
        'comercial'           => 'Comercial',
        'administrativo'      => 'Administrativo',
        'contable'            => 'Contable',
        'talento_humano'      => 'Talento humano',
        'otro'                => 'Otro',
    ];

    protected function getData(): array
    {
        $query = Characterization::query();
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->whereHas('entrepreneur', fn($q) => $q->where('manager_id', auth()->id()));
        }

        $counts = array_fill_keys(array_keys(self::$needLabels), 0);

        $query->whereNotNull('strengthening_needs')
            ->pluck('strengthening_needs')
            ->each(function ($needs) use (&$counts) {
                if (!is_array($needs)) return;
                foreach ($needs as $need) {
                    if (isset($counts[$need])) {
                        $counts[$need]++;
                    }
                }
            });

        $counts = array_filter($counts, fn($v) => $v > 0);
        arsort($counts);
        $counts = array_slice($counts, 0, 8, true);

        $labels = array_map(fn($k) => self::$needLabels[$k] ?? $k, array_keys($counts));
        $data   = array_values($counts);

        return [
            'datasets' => [
                [
                    'label'           => 'Emprendedores',
                    'data'            => $data,
                    'backgroundColor' => '#F59E0B',
                    'borderRadius'    => 4,
                    'borderWidth'     => 0,
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
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.parsed.y + " emprendedores";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => [
                        'font'        => ['size' => 10],
                        'autoSkip'    => false,
                        'maxRotation' => 35,
                        'minRotation' => 35,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid'        => ['color' => '#F3F4F6'],
                    'ticks'       => ['stepSize' => 1],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive'          => true,
        ];
    }
}
