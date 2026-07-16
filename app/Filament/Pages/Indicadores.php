<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AlertasPendientesWidget;
use App\Filament\Widgets\CityProgressWidget;
use App\Filament\Widgets\DashboardKpiWidget;
use App\Filament\Widgets\GenderDistributionWidget;
use App\Filament\Widgets\MaturityLevelSummaryWidget;
use App\Filament\Widgets\NecesidadesWidget;
use App\Filament\Widgets\RutaDistributionWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Indicadores extends BaseDashboard
{
    protected static string $routePath = '/indicadores';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores';

    protected static ?int $navigationSort = -1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('viewDashboardIndicators') ?? false;
    }

    public function getWidgets(): array
    {
        if (!auth()->user()?->can('viewDashboardIndicators')) {
            return [];
        }

        return [
            DashboardKpiWidget::class,
            CityProgressWidget::class,
            RutaDistributionWidget::class,
            MaturityLevelSummaryWidget::class,
            NecesidadesWidget::class,
            GenderDistributionWidget::class,
            AlertasPendientesWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
