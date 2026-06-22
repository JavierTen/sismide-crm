<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Indicadores extends BaseDashboard
{
    protected static string $routePath = '/indicadores';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores';

    protected static ?int $navigationSort = -1;
}
