<?php

namespace App\Filament\Eje\Widgets;

use App\Models\EducationalInstitution;
use App\Models\InstitutionEvaluation;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class EjeStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Instituciones Educativas', $this->scoped(EducationalInstitution::query())->count())
                ->description('Instituciones registradas')
                ->descriptionIcon('heroicon-o-building-library')
                ->color('primary'),

            Stat::make('Docentes', $this->scoped(Teacher::query())->count())
                ->description('Docentes vinculados')
                ->descriptionIcon('heroicon-o-user-circle')
                ->color('warning'),

            Stat::make('Estudiantes', $this->scoped(Student::query())->count())
                ->description('Estudiantes vinculados')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Evaluaciones', $this->scoped(InstitutionEvaluation::query())->count())
                ->description('Evaluaciones registradas')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('info'),
        ];
    }

    private function scoped(Builder $query): Builder
    {
        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query;
    }
}
