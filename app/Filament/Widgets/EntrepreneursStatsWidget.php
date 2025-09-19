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
            Stat::make('Emprendedores Inscritos', Entrepreneur::count())
                ->description('Total de registros')
                ->descriptionIcon('heroicon-o-light-bulb')
                ->color('warning')
                ->chart($this->getRegistrationsChart()),

            // Opcional: Agregar más estadísticas relacionadas
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

    private function getNewThisMonth(): int
    {
        return Entrepreneur::whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                          ->count();
    }

    private function getActiveEntrepreneurs(): int
    {
        // Ajusta según tu lógica de negocio para determinar "activo"
        // Por ejemplo, si tienes un campo 'is_active' o 'status'
        return Entrepreneur::where('deleted_at', null)->count();
        // O si no tienes ese campo, puedes usar:
        // return Entrepreneur::whereNotNull('email_verified_at')->count();
    }

    private function getRegistrationsChart(): array
    {
        // Gráfico de los últimos 7 días
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Entrepreneur::whereDate('created_at', $date)->count();
            $data[] = $count;
        }

        return $data;
    }

    // Ordenar este widget primero
    protected static ?int $sort = 1;

    // Actualización automática cada 30 segundos
    protected static ?string $pollingInterval = '30s';
}
