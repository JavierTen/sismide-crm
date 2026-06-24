<?php

namespace App\Filament\Eje\Pages;

use App\Filament\Eje\Widgets\CharacterizationCoverageWidget;
use App\Filament\Eje\Widgets\EjeStatsWidget;
use App\Filament\Eje\Widgets\InstitutionsByCityWidget;
use App\Filament\Eje\Widgets\StudentsByGenderWidget;
use App\Filament\Eje\Widgets\StudentsByGradeWidget;
use App\Filament\Eje\Widgets\StudentsByMainInterestWidget;
use App\Filament\Eje\Widgets\TeachersByStatusWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Indicadores';

    protected static ?string $title = 'Indicadores';

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            EjeStatsWidget::class,
            InstitutionsByCityWidget::class,
            StudentsByGradeWidget::class,
            StudentsByGenderWidget::class,
            TeachersByStatusWidget::class,
            CharacterizationCoverageWidget::class,
            StudentsByMainInterestWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
