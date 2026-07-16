<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use App\Models\BusinessPlan;
use App\Models\Characterization;
use App\Models\Entrepreneur;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlertasPendientesWidget extends BaseWidget
{
    protected ?string $heading = 'Alertas y Pendientes';
    protected static ?int $sort = 7;
    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $isManager = !auth()->user()->hasRole(['Admin', 'Viewer']);
        $managerId = auth()->id();

        // Sin caracterización
        $q1 = Entrepreneur::whereDoesntHave('characterizations');
        if ($isManager) $q1->where('manager_id', $managerId);
        $sinCaracterizacion = $q1->count();

        // Caracterizados sin diagnóstico
        $q2 = Entrepreneur::whereHas('characterizations')->whereDoesntHave('businessDiagnoses');
        if ($isManager) $q2->where('manager_id', $managerId);
        $sinDiagnostico = $q2->count();

        // Diagnosticados sin plan de negocio
        $q3 = Entrepreneur::whereHas('businessDiagnoses')->whereDoesntHave('businessPlans');
        if ($isManager) $q3->where('manager_id', $managerId);
        $sinPlan = $q3->count();

        // Sin gestor asignado
        $q4 = Entrepreneur::whereNull('manager_id');
        $sinGestor = $q4->count();

        // Visitas sin resultado
        $q5 = Visit::whereNull('visit_result');
        if ($isManager) $q5->where('manager_id', $managerId);
        $visitasPendientes = $q5->count();

        // Sin evidencia fotográfica en caracterización
        $q6 = Characterization::whereNull('photo_evidence_path');
        if ($isManager) $q6->whereHas('entrepreneur', fn($q) => $q->where('manager_id', $managerId));
        $sinEvidencia = $q6->count();

        // Sin habeas data
        $q7 = Characterization::where(fn($q) => $q->where('habeas_data_accepted', false)->orWhereNull('habeas_data_accepted'));
        if ($isManager) $q7->whereHas('entrepreneur', fn($q) => $q->where('manager_id', $managerId));
        $sinHabeasData = $q7->count();

        return [
            Stat::make('Sin caracterización', $sinCaracterizacion)
                ->description('Registrados sin caracterizar')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($sinCaracterizacion > 0 ? 'danger' : 'success'),

            Stat::make('Sin diagnóstico', $sinDiagnostico)
                ->description('Caracterizados sin diagnóstico')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($sinDiagnostico > 0 ? 'warning' : 'success'),

            Stat::make('Sin plan de negocio', $sinPlan)
                ->description('Diagnosticados sin plan')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($sinPlan > 0 ? 'warning' : 'success'),

            Stat::make('Sin gestor asignado', $sinGestor)
                ->description('Emprendedores sin gestor')
                ->descriptionIcon('heroicon-o-user-minus')
                ->color($sinGestor > 0 ? 'danger' : 'success'),

            Stat::make('Visitas pendientes', $visitasPendientes)
                ->description('Sin resultado registrado')
                ->descriptionIcon('heroicon-o-clock')
                ->color($visitasPendientes > 0 ? 'warning' : 'success'),

            Stat::make('Sin evidencia fotográfica', $sinEvidencia)
                ->description('Caracterizaciones sin foto')
                ->descriptionIcon('heroicon-o-camera')
                ->color($sinEvidencia > 0 ? 'warning' : 'success'),

            Stat::make('Sin habeas data', $sinHabeasData)
                ->description('Sin autorización de datos')
                ->descriptionIcon('heroicon-o-shield-exclamation')
                ->color($sinHabeasData > 0 ? 'danger' : 'success'),
        ];
    }
}
