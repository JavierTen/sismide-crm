<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use App\Models\Characterization;
use App\Models\Entrepreneur;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardKpiWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $goal = Project::where('status', true)->value('participant_goal') ?? 300;

        $registeredQuery = Entrepreneur::query();
        $this->applyRoleFilter($registeredQuery);
        $registered = $registeredQuery->count();

        $characterizedQuery = Entrepreneur::whereHas('characterizations');
        $this->applyRoleFilter($characterizedQuery);
        $characterized = $characterizedQuery->count();

        $diagnosedQuery = Entrepreneur::whereHas('businessDiagnoses');
        $this->applyRoleFilter($diagnosedQuery);
        $diagnosed = $diagnosedQuery->count();

        $pendingQuery = Entrepreneur::whereDoesntHave('characterizations');
        $this->applyRoleFilter($pendingQuery);
        $pending = $pendingQuery->count();

        $pctMeta   = $goal > 0 ? round(($registered / $goal) * 100, 1) : 0;
        $pctChar   = $registered > 0 ? round(($characterized / $registered) * 100, 1) : 0;
        $pctDiag   = $characterized > 0 ? round(($diagnosed / $characterized) * 100, 1) : 0;

        return [
            Stat::make('Meta General', number_format($goal))
                ->description('Participantes meta ' . now()->year)
                ->descriptionIcon('heroicon-o-flag')
                ->color('primary'),

            Stat::make('Registrados', number_format($registered))
                ->description("{$pctMeta}% de la meta")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Caracterizados', number_format($characterized))
                ->description("{$pctChar}% de registrados")
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Diagnosticados', number_format($diagnosed))
                ->description("{$pctDiag}% de caracterizados")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Pendientes', number_format($pending))
                ->description('Sin caracterización')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }

    private function applyRoleFilter(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }
    }
}
