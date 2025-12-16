<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResultResource\Pages;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationResultResource extends Resource
{
    protected static ?string $model = BusinessPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Evaluaciones';

    protected static ?string $modelLabel = 'Resultado de Evaluación';

    protected static ?string $pluralModelLabel = 'Consolidado de Evaluaciones';

    protected static ?int $navigationSort = 3;

    // Métodos de permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('listEvaluationResults');
    }

    public static function canViewAny(): bool
    {
        return static::userCanList();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Solo mostrar planes priorizados que tengan evaluaciones
        return parent::getEloquentQuery()
            ->where('is_prioritized', true)
            ->whereHas('evaluations')
            ->with([
                'entrepreneur.business',
                'entrepreneur.city',
                'evaluations.evaluator',
                'evaluations.question'
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('entrepreneur.full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('Sin emprendimiento'),

                Tables\Columns\TextColumn::make('evaluators_score')
                    ->label('Evaluadores')
                    ->getStateUsing(function (BusinessPlan $record): string {
                        $average = BusinessPlanEvaluation::getAllEvaluatorsAverage($record->id);
                        $count = BusinessPlanEvaluation::getEvaluatorsCount($record->id);

                        if ($count === 0) {
                            return 'Sin evaluar';
                        }

                        return number_format($average, 2) . ' (' . $count . ' evaluador' . ($count > 1 ? 'es' : '') . ')';
                    })
                    ->badge()
                    ->color(fn (string $state) => str_contains($state, 'Sin') ? 'gray' : 'info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('managers_score')
                    ->label('Gestores')
                    ->getStateUsing(function (BusinessPlan $record): string {
                        $average = BusinessPlanEvaluation::getAllManagersAverage($record->id);
                        $count = BusinessPlanEvaluation::getManagersCount($record->id);

                        if ($count === 0) {
                            return 'Sin evaluar';
                        }

                        return number_format($average, 2) . ' (' . $count . ' gestor' . ($count > 1 ? 'es' : '') . ')';
                    })
                    ->badge()
                    ->color(fn (string $state) => str_contains($state, 'Sin') ? 'gray' : 'warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Calificación Final')
                    ->getStateUsing(function (BusinessPlan $record): string {
                        $scores = BusinessPlanEvaluation::getFinalScore($record->id);

                        if ($scores['evaluators_average'] == 0 && $scores['managers_average'] == 0) {
                            return 'Pendiente';
                        }

                        return number_format($scores['final_score'], 2);
                    })
                    ->badge()
                    ->color(function (string $state) {
                        if (str_contains($state, 'Pendiente')) {
                            return 'gray';
                        }

                        $score = (float) str_replace(' / 20', '', $state);

                        if ($score >= 16) return 'success';
                        if ($score >= 12) return 'warning';
                        return 'danger';
                    })
                    ->weight('bold')
                    ->alignCenter()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->relationship('entrepreneur.city', 'name', function ($query) {
                        return $query->where('status', true)
                            ->whereHas('department', function ($q) {
                                $q->where('status', true);
                            })
                            ->orderBy('name', 'asc');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('fully_evaluated')
                    ->label('Completamente evaluados')
                    ->query(function ($query) {
                        return $query->whereHas('evaluatorEvaluations')
                            ->whereHas('managerEvaluations');
                    }),

                Tables\Filters\Filter::make('pending_managers')
                    ->label('Pendientes de gestores')
                    ->query(function ($query) {
                        return $query->whereHas('evaluatorEvaluations')
                            ->whereDoesntHave('managerEvaluations');
                    }),
            ])
            ->actions([
                // Sin acciones
            ])
            ->bulkActions([
                // Sin acciones bulk
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluationResults::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Contar planes completamente evaluados
        $count = BusinessPlan::where('is_prioritized', true)
            ->whereHas('evaluatorEvaluations')
            ->whereHas('managerEvaluations')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
