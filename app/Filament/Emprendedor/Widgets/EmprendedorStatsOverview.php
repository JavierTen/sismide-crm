<?php

namespace App\Filament\Emprendedor\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmprendedorStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $entrepreneur = auth()->user();

        return [
            Stat::make('Mi Emprendimiento', $entrepreneur->business?->business_name ?? 'Sin negocio')
                ->description('Nombre del negocio')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success'),

            Stat::make('Diagnósticos', $entrepreneur->businessDiagnoses()->count())
                ->description('Diagnósticos realizados')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Visitas', $entrepreneur->visits()->count())
                ->description('Visitas registradas')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),
        ];
    }
}
