<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManagerBusinessPlanEvaluationResource\Pages;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use App\Models\BusinessPlanEvaluationQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ManagerBusinessPlanEvaluationResource extends Resource
{
    protected static ?string $model = BusinessPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Evaluaciones';

    protected static ?string $modelLabel = 'Evaluación Gestor';

    protected static ?string $pluralModelLabel = 'Evaluaciones Gestor';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        // Los gestores solo ven planes priorizados que:
        // 1. Ya fueron evaluados por al menos 1 evaluador
        // 2. NO son de sus propios emprendedores (no puede evaluar a su gente)
        return parent::getEloquentQuery()
            ->where('is_prioritized', true) // Ya tiene evaluaciones de evaluadores
            ->whereHas('entrepreneur', function ($query) use ($user) {
                $query->where('manager_id', '!=', $user->id); // NO es su emprendedor
            })
            ->with(['entrepreneur.business', 'entrepreneur.city', 'entrepreneur.manager']);
    }

    // Métodos de permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('listEvaluationManagers');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('createEvaluationManager');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('editEvaluationManager');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('deleteEvaluationManager');
    }

    public static function canViewAny(): bool
    {
        return static::userCanList();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::userCanCreate();
    }

    public static function canEdit($record): bool
    {
        return static::userCanEdit();
    }

    public static function canDelete($record): bool
    {
        return static::userCanDelete();
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

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('evaluated')
                    ->label('Mi Evaluación')
                    ->getStateUsing(function (BusinessPlan $record): bool {
                        return BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'manager'
                        );
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),
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

                Tables\Filters\Filter::make('my_pending')
                    ->label('Pendientes por evaluar')
                    ->query(function ($query) {
                        return $query->whereDoesntHave('evaluations', function ($q) {
                            $q->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'manager');
                        });
                    }),

                Tables\Filters\Filter::make('my_completed')
                    ->label('Ya evaluados por mí')
                    ->query(function ($query) {
                        return $query->whereHas('evaluations', function ($q) {
                            $q->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'manager');
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('evaluate')
                    ->label('')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->tooltip('Evaluar')
                    ->visible(
                        fn(BusinessPlan $record): bool =>
                        !BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'manager'
                        ) && static::userCanCreate() // ← AGREGAR ESTA LÍNEA
                    )
                    ->modalHeading('Evaluación Gestor - Criterio Técnico')
                    ->modalWidth('5xl')
                    ->form(function (BusinessPlan $record) {
                        $question = BusinessPlanEvaluationQuestion::forManagers()->first();

                        return [
                            Forms\Components\Section::make('Información del Emprendedor')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Placeholder::make('entrepreneur_name')
                                                ->label('Emprendedor')
                                                ->content($record->entrepreneur->full_name),

                                            Forms\Components\Placeholder::make('business_name')
                                                ->label('Emprendimiento')
                                                ->content($record->entrepreneur->business?->business_name ?? 'Sin emprendimiento'),

                                            Forms\Components\Placeholder::make('city_name')
                                                ->label('Municipio')
                                                ->content($record->entrepreneur->city?->name ?? 'Sin ubicación'),
                                        ]),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make('Promedio de Evaluadores')
                                ->schema([
                                    Forms\Components\Placeholder::make('evaluators_average')
                                        ->label('Calificación promedio de evaluadores')
                                        ->content(function (BusinessPlan $record) {
                                            $average = BusinessPlanEvaluation::getAllEvaluatorsAverage($record->id);
                                            $count = BusinessPlanEvaluation::getEvaluatorsCount($record->id);

                                            return number_format($average, 2) . ' / 10 (' . $count . ' evaluador' . ($count > 1 ? 'es' : '') . ')';
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make($question->question_number . '. ' . $question->question_text)
                                ->description('Ponderación: ' . ($question->weight * 100) . '%')
                                ->schema([
                                    Forms\Components\Placeholder::make('question_description')
                                        ->label('Criterios a evaluar')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">' .
                                                '<p class="font-semibold">Evalúe el plan considerando:</p>' .
                                                '<ul class="list-disc list-inside space-y-1 ml-2">' .
                                                '<li>I. El modelo negocio es escalable, sostenible, posee ventaja competitiva.</li>' .
                                                '<li>II. La propuesta de valor ha sido testeada y ajustada de acuerdo con las necesidades del mercado.</li>' .
                                                '<li>III. Emplea prácticas amigables con el medio ambiente.</li>' .
                                                '<li>IV. El emprendedor tiene diseñada la ruta hacia la formalización empresarial.</li>' .
                                                '</ul>' .
                                                '</div>'
                                        ))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('score')
                                        ->label('Calificación')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(10)
                                        ->step(0.1)
                                        ->suffix('/ 10')
                                        ->helperText('Ingrese una calificación de 1 a 10')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->compact(),

                            Forms\Components\Section::make('Observaciones')
                                ->schema([
                                    Forms\Components\Textarea::make('comments')
                                        ->label('Comentarios u observaciones (opcional)')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                        ];
                    })
                    ->action(function (BusinessPlan $record, array $data) {
                        $question = BusinessPlanEvaluationQuestion::forManagers()->first();

                        if (!$question) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se encontró la pregunta de evaluación para gestores.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            BusinessPlanEvaluation::create([
                                'business_plan_id' => $record->id,
                                'evaluator_id' => auth()->id(),
                                'question_id' => $question->id,
                                'evaluator_type' => 'manager',
                                'question_number' => $question->question_number,
                                'score' => $data['score'],
                                'comments' => $data['comments'] ?? null,
                            ]);

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Evaluación guardada')
                                ->body('Su evaluación como gestor ha sido registrada correctamente.')
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Error al guardar')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),

                // ✅ ACCIÓN PARA EDITAR EVALUACIÓN
                Tables\Actions\Action::make('edit_evaluation')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar mi evaluación')
                    ->color('warning')
                    ->visible(function (BusinessPlan $record): bool {
                        // Solo visible si:
                        // 1. Ya evaluó
                        // 2. Tiene permiso para editar
                        return BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'manager'
                        ) && static::userCanEdit();
                    })
                    ->modalHeading('Editar Evaluación Gestor')
                    ->modalWidth('5xl')
                    ->fillForm(function (BusinessPlan $record): array {
                        $evaluation = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                            ->where('evaluator_id', auth()->id())
                            ->where('evaluator_type', 'manager')
                            ->first();

                        return [
                            'score' => $evaluation->score,
                            'comments' => $evaluation->comments,
                        ];
                    })
                    ->form(function (BusinessPlan $record) {
                        $question = BusinessPlanEvaluationQuestion::forManagers()->first();

                        return [
                            Forms\Components\Section::make('Información del Emprendedor')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Placeholder::make('entrepreneur_name')
                                                ->label('Emprendedor')
                                                ->content($record->entrepreneur->full_name),

                                            Forms\Components\Placeholder::make('business_name')
                                                ->label('Emprendimiento')
                                                ->content($record->entrepreneur->business?->business_name ?? 'Sin emprendimiento'),

                                            Forms\Components\Placeholder::make('city_name')
                                                ->label('Municipio')
                                                ->content($record->entrepreneur->city?->name ?? 'Sin ubicación'),
                                        ]),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make($question->question_number . '. ' . $question->question_text)
                                ->description('Ponderación: ' . ($question->weight * 100) . '%')
                                ->schema([
                                    Forms\Components\Placeholder::make('question_description')
                                        ->label('Criterios a evaluar')
                                        ->content(new \Illuminate\Support\HtmlString(
                                            '<div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">' .
                                                '<p class="font-semibold">Evalúe el plan considerando:</p>' .
                                                '<ul class="list-disc list-inside space-y-1 ml-2">' .
                                                '<li>I. El modelo negocio es escalable, sostenible, posee ventaja competitiva.</li>' .
                                                '<li>II. La propuesta de valor ha sido testeada y ajustada de acuerdo con las necesidades del mercado.</li>' .
                                                '<li>III. Emplea prácticas amigables con el medio ambiente.</li>' .
                                                '<li>IV. El emprendedor tiene diseñada la ruta hacia la formalización empresarial.</li>' .
                                                '</ul>' .
                                                '</div>'
                                        ))
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('score')
                                        ->label('Calificación')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(10)
                                        ->step(0.1)
                                        ->suffix('/ 10')
                                        ->helperText('Ingrese una calificación de 1 a 10')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->compact(),

                            Forms\Components\Section::make('Observaciones')
                                ->schema([
                                    Forms\Components\Textarea::make('comments')
                                        ->label('Comentarios u observaciones (opcional)')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                        ];
                    })
                    ->action(function (BusinessPlan $record, array $data) {
                        try {
                            DB::beginTransaction();

                            $evaluation = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                                ->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'manager')
                                ->first();

                            if (!$evaluation) {
                                throw new \Exception('No se encontró la evaluación.');
                            }

                            $evaluation->update([
                                'score' => $data['score'],
                                'comments' => $data['comments'] ?? null,
                            ]);

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Evaluación actualizada')
                                ->body('Su evaluación ha sido actualizada correctamente.')
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Error al actualizar')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),

                // ✅ ACCIÓN PARA VER EVALUACIÓN
                Tables\Actions\Action::make('view_evaluation')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver mi evaluación')
                    ->color('info')
                    ->visible(
                        fn(BusinessPlan $record): bool =>
                        BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'manager'
                        )
                    )
                    ->modalHeading('Mi Evaluación')
                    ->modalWidth('4xl')
                    ->modalContent(function (BusinessPlan $record) {
                        $evaluation = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                            ->where('evaluator_id', auth()->id())
                            ->where('evaluator_type', 'manager')
                            ->with('question')
                            ->first();

                        if (!$evaluation) {
                            return new \Illuminate\Support\HtmlString('<p class="text-gray-600">No se encontró evaluación.</p>');
                        }

                        $html = '<div class="space-y-4">';

                        // Info del emprendedor
                        $html .= '<div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Emprendedor</p><p class="font-semibold">' . e($record->entrepreneur->full_name) . '</p></div>';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Emprendimiento</p><p class="font-semibold">' . e($record->entrepreneur->business?->business_name ?? 'Sin emprendimiento') . '</p></div>';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Municipio</p><p class="font-semibold">' . e($record->entrepreneur->city?->name ?? 'Sin ubicación') . '</p></div>';
                        $html .= '</div>';

                        // Evaluación
                        $html .= '<div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">';
                        $html .= '<div class="flex justify-between items-start">';
                        $html .= '<div class="flex-1">';
                        $html .= '<p class="font-semibold text-gray-900 dark:text-white">' . $evaluation->question_number . '. ' . e($evaluation->question->question_text) . '</p>';
                        $html .= '<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">' . e($evaluation->question->description) . '</p>';
                        $html .= '<p class="text-xs text-gray-500 mt-1">Ponderación: ' . ($evaluation->question->weight * 100) . '%</p>';
                        $html .= '</div>';
                        $html .= '<div class="ml-4">';
                        $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-bold bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">';
                        $html .= number_format($evaluation->score, 1) . ' / 10';
                        $html .= '</span></div></div></div>';

                        // Comentarios
                        if ($evaluation->comments) {
                            $html .= '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                            $html .= '<p class="font-semibold text-gray-900 dark:text-white mb-2">Observaciones:</p>';
                            $html .= '<p class="text-gray-700 dark:text-gray-300">' . nl2br(e($evaluation->comments)) . '</p>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                // ✅ ACCIÓN PARA ELIMINAR EVALUACIÓN
                Tables\Actions\Action::make('delete_evaluation')
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->tooltip('Eliminar mi evaluación')
                    ->color('danger')
                    ->visible(function (BusinessPlan $record): bool {
                        // Solo visible si:
                        // 1. Ya evaluó
                        // 2. Tiene permiso para eliminar
                        return BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'manager'
                        ) && static::userCanDelete();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Evaluación')
                    ->modalDescription('¿Está seguro de que desea eliminar su evaluación? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(function (BusinessPlan $record) {
                        try {
                            DB::beginTransaction();

                            $deleted = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                                ->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'manager')
                                ->delete();

                            if (!$deleted) {
                                throw new \Exception('No se encontró la evaluación para eliminar.');
                            }

                            DB::commit();

                            Notification::make()
                                ->success()
                                ->title('Evaluación eliminada')
                                ->body('Su evaluación ha sido eliminada correctamente.')
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Error al eliminar')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagerBusinessPlanEvaluations::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        $pending = BusinessPlan::where('is_prioritized', true)
            ->whereHas('evaluatorEvaluations')
            ->whereHas('entrepreneur', function ($query) use ($user) {
                $query->where('manager_id', '!=', $user->id);
            })
            ->whereDoesntHave('evaluations', function ($q) {
                $q->where('evaluator_id', auth()->id())
                    ->where('evaluator_type', 'manager');
            })
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
