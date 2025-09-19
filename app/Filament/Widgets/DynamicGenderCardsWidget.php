<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Entrepreneur;

class DynamicGenderCardsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $genderStats = $this->getGenderBreakdown();
        $total = array_sum($genderStats);
        $stats = [];

        // Definir colores por género
        $genderColors = [
            'Masculino' => 'primary',
            'Femenino' => 'danger',
            'LGTBIQ+' => 'warning',
            'Otro' => 'gray',
        ];

        // Solo crear cards para géneros que tienen registros
        foreach ($genderStats as $gender => $count) {
            if ($count > 0) { // Solo si hay emprendedores de este género
                $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
                $color = $genderColors[$gender] ?? 'gray';

                $stats[] = Stat::make($gender, $count)
                    ->description($percentage . '% del total')
                    ->descriptionIcon('heroicon-m-user')
                    ->color($color);
            }
        }

        // Si no hay datos, mostrar mensaje
        if (empty($stats)) {
            $stats[] = Stat::make('Sin datos', '0')
                ->description('No hay registros por género')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray');
        }

        return $stats;
    }

    private function getGenderBreakdown(): array
    {
        // Obtener solo los géneros que realmente existen en los datos
        $distribution = Entrepreneur::with('gender')
            ->whereHas('gender') // Solo emprendedores con género asignado
            ->get()
            ->groupBy('gender.name')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc() // Ordenar de mayor a menor
            ->toArray();

        return $distribution;
    }

    protected static ?int $sort = 4;

    // Hacer que se ajuste dinámicamente al número de cards
    protected int | string | array $columnSpan = [
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
    ];

    protected static ?string $pollingInterval = '30s';
}
