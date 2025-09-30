<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Entrepreneur;
use Carbon\Carbon;

class EntrepreneursStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Emprendedores Inscritos', $this->getTotalEntrepreneurs())
                ->description('Total de registros')
                ->descriptionIcon('heroicon-o-light-bulb')
                ->color('warning')
                ->chart($this->getRegistrationsChart()),

            Stat::make('Nuevos este Mes', $this->getNewThisMonth())
                ->description('Registros en ' . now()->format('M Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Activos', $this->getActiveEntrepreneurs())
                ->description('Emprendedores activos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
        ];
    }

    private function getTotalEntrepreneurs(): int
    {
        $query = Entrepreneur::query();

        if (!auth()->user()->hasRole('admin')) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    private function getNewThisMonth(): int
    {
        $query = Entrepreneur::whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);

        if (!auth()->user()->hasRole('admin')) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    private function getActiveEntrepreneurs(): int
    {
        $query = Entrepreneur::whereNull('deleted_at');

        if (!auth()->user()->hasRole('admin')) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    private function getRegistrationsChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $query = Entrepreneur::whereDate('created_at', $date);

            if (!auth()->user()->hasRole('admin')) {
                $query->where('manager_id', auth()->id());
            }

            $data[] = $query->count();
        }

        return $data;
    }

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';
}
