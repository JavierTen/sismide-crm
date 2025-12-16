<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessPlanEvaluationResource\Pages;
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

class BusinessPlanEvaluationResource extends Resource
{
    protected static ?string $model = BusinessPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Evaluaciones';

    protected static ?string $modelLabel = 'Evaluación Calificador';

    protected static ?string $pluralModelLabel = 'Evaluaciones Calificadores';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('is_prioritized', true)
            ->with(['entrepreneur.business', 'entrepreneur.city', 'entrepreneur.manager']);
    }

    // Métodos de permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('listEvaluationEvaluators');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('createEvaluationEvaluator');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('editEvaluationEvaluator');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('deleteEvaluationEvaluator');
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
                            'evaluator'
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
                                ->where('evaluator_type', 'evaluator');
                        });
                    }),

                Tables\Filters\Filter::make('my_completed')
                    ->label('Ya evaluados por mí')
                    ->query(function ($query) {
                        return $query->whereHas('evaluations', function ($q) {
                            $q->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'evaluator');
                        });
                    }),
            ])
            ->actions([
                // ✅ ACCIÓN PARA EVALUAR (MODAL)
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
                            'evaluator'
                        ) && static::userCanCreate() // ← AGREGAR ESTA LÍNEA
                    )
                    ->modalHeading('Evaluación de Plan de Negocio')
                    ->modalWidth('7xl')
                    ->form(function (BusinessPlan $record) {
                        return static::getEvaluationForm($record);
                    })
                    ->action(function (BusinessPlan $record, array $data) {
                        static::saveEvaluation($record, $data);
                    }),

                // ✅ ACCIÓN PARA EDITAR EVALUACIÓN
                Tables\Actions\Action::make('edit_evaluation')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar mi evaluación')
                    ->color('warning')
                    ->visible(function (BusinessPlan $record): bool {
                        return BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'evaluator'
                        ) && static::userCanEdit();
                    })
                    ->modalHeading('Editar Evaluación de Plan de Negocio')
                    ->modalWidth('7xl')
                    ->fillForm(function (BusinessPlan $record): array {
                        $evaluations = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                            ->where('evaluator_id', auth()->id())
                            ->where('evaluator_type', 'evaluator')
                            ->with('question')
                            ->get();

                        $formData = [];
                        foreach ($evaluations as $evaluation) {
                            $formData["score_{$evaluation->question_id}"] = $evaluation->score;
                        }
                        $formData['comments'] = $evaluations->first()?->comments;

                        return $formData;
                    })
                    ->form(function (BusinessPlan $record) {
                        return static::getEvaluationForm($record);
                    })
                    ->action(function (BusinessPlan $record, array $data) {
                        static::updateEvaluation($record, $data);
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
                            'evaluator'
                        )
                    )
                    ->modalHeading(fn(BusinessPlan $record) => 'Mi Evaluación: ' . $record->entrepreneur->full_name)
                    ->modalWidth('5xl')
                    ->modalContent(function (BusinessPlan $record) {
                        $evaluations = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                            ->where('evaluator_id', auth()->id())
                            ->where('evaluator_type', 'evaluator')
                            ->with('question')
                            ->get();

                        $average = BusinessPlanEvaluation::getEvaluatorAverage($record->id, auth()->id());

                        $html = '<div class="space-y-4">';

                        // Info del emprendedor
                        $html .= '<div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Emprendedor</p><p class="font-semibold">' . e($record->entrepreneur->full_name) . '</p></div>';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Emprendimiento</p><p class="font-semibold">' . e($record->entrepreneur->business?->business_name ?? 'Sin emprendimiento') . '</p></div>';
                        $html .= '<div><p class="text-sm text-gray-600 dark:text-gray-400">Municipio</p><p class="font-semibold">' . e($record->entrepreneur->city?->name ?? 'Sin ubicación') . '</p></div>';
                        $html .= '</div>';

                        // Evaluaciones
                        foreach ($evaluations as $evaluation) {
                            $html .= '<div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">';
                            $html .= '<div class="flex justify-between items-start">';
                            $html .= '<div class="flex-1">';
                            $html .= '<p class="font-semibold text-gray-900 dark:text-white">' . $evaluation->question_number . '. ' . e($evaluation->question->question_text) . '</p>';
                            $html .= '<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">' . e($evaluation->question->description) . '</p>';
                            $html .= '<p class="text-xs text-gray-500 mt-1">Ponderación: ' . ($evaluation->question->weight * 100) . '%</p>';
                            $html .= '</div>';
                            $html .= '<div class="ml-4">';
                            $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-lg font-bold bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">';
                            $html .= number_format($evaluation->score, 1) . ' / 10';
                            $html .= '</span></div></div></div>';
                        }

                        // Promedio
                        $html .= '<div class="p-4 bg-success-50 dark:bg-success-900/20 rounded-lg border border-success-200 dark:border-success-800">';
                        $html .= '<div class="flex justify-between items-center">';
                        $html .= '<span class="text-lg font-semibold text-success-800 dark:text-success-200">Promedio Ponderado:</span>';
                        $html .= '<span class="text-2xl font-bold text-success-600 dark:text-success-400">' . number_format($average, 2) . ' / 10</span>';
                        $html .= '</div></div>';

                        // Comentarios
                        if ($evaluations->first()?->comments) {
                            $html .= '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                            $html .= '<p class="font-semibold text-gray-900 dark:text-white mb-2">Recomendaciones:</p>';
                            $html .= '<p class="text-gray-700 dark:text-gray-300">' . nl2br(e($evaluations->first()->comments)) . '</p>';
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
                        return BusinessPlanEvaluation::hasCompletedEvaluation(
                            $record->id,
                            auth()->id(),
                            'evaluator'
                        ) && static::userCanDelete();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Evaluación')
                    ->modalDescription('¿Está seguro de que desea eliminar su evaluación completa? Esta acción no se puede deshacer y eliminará todas las calificaciones de las 11 preguntas.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->action(function (BusinessPlan $record) {
                        try {
                            DB::beginTransaction();

                            $deleted = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                                ->where('evaluator_id', auth()->id())
                                ->where('evaluator_type', 'evaluator')
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

    /**
     * Genera el formulario de evaluación (reutilizable para crear y editar)
     */
    private static function getEvaluationForm(BusinessPlan $record): array
    {
        $questions = BusinessPlanEvaluationQuestion::forEvaluators()->get();

        $formFields = [
            Forms\Components\Section::make('Datos básicos del emprendedor')
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

                            Forms\Components\Placeholder::make('business_type')
                                ->label('Tipo de Negocio')
                                ->content($record->entrepreneur->characterizations->first()?->business_type
                                    ? \App\Models\Characterization::businessTypes()[$record->entrepreneur->characterizations->first()->business_type] ?? 'N/A'
                                    : 'N/A'),

                            Forms\Components\Placeholder::make('economic_activity')
                                ->label('Actividad Económica')
                                ->content($record->entrepreneur->characterizations->first()?->economicActivity?->name ?? 'N/A'),
                        ]),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Métricas clave')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('business_definition')
                                ->label('Producto o Servicio')
                                ->content($record->business_definition ?? 'N/A'),

                            Forms\Components\Placeholder::make('value_proposition')
                                ->label('Propuesta de Valor')
                                ->content($record->value_proposition ?? 'N/A'),

                            Forms\Components\Placeholder::make('requirements_needs')
                                ->label('Solicitud')
                                ->content($record->requirements_needs ?? 'N/A'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('monthly_sales_cop')
                                ->label('Volumen de ventas mensual en COP')
                                ->content($record->monthly_sales_cop ? '$' . number_format($record->monthly_sales_cop, 2) : 'N/A'),

                            Forms\Components\Placeholder::make('monthly_sales_units')
                                ->label('Volumen de ventas mensual en unidades')
                                ->content($record->monthly_sales_units ?? 'N/A'),

                            Forms\Components\Placeholder::make('production_frequency')
                                ->label('Frecuencia de producción')
                                ->content($record->production_frequency
                                    ? \App\Models\BusinessPlan::productionFrequencyOptions()[$record->production_frequency] ?? 'N/A'
                                    : 'N/A'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('gross_profitability_rate')
                                ->label('Tasa de rentabilidad bruta')
                                ->content($record->gross_profitability_rate ? $record->gross_profitability_rate . '%' : 'N/A'),

                            Forms\Components\Placeholder::make('cash_flow_growth_rate')
                                ->label('Tasa de crecimiento proyectada del flujo de caja')
                                ->content($record->cash_flow_growth_rate ? $record->cash_flow_growth_rate . '%' : 'N/A'),

                            Forms\Components\Placeholder::make('internal_return_rate')
                                ->label('Tasa Interna de Retorno')
                                ->content($record->internal_return_rate ? $record->internal_return_rate . '%' : 'N/A'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('break_even_units')
                                ->label('Punto de equilibrio en unidades')
                                ->content($record->break_even_units ?? 'N/A'),

                            Forms\Components\Placeholder::make('break_even_cop')
                                ->label('Punto de equilibrio en COP')
                                ->content($record->break_even_cop ? '$' . number_format($record->break_even_cop, 2) : 'N/A'),

                            Forms\Components\Placeholder::make('current_investment_value')
                                ->label('Valor inversión actual')
                                ->content($record->current_investment_value ? '$' . number_format($record->current_investment_value, 2) : 'N/A'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('jobs_generated')
                                ->label('Número de empleos generados')
                                ->content($record->jobs_generated ?? 'N/A'),

                            Forms\Components\Placeholder::make('direct_competitors')
                                ->label('Competidores directos')
                                ->content($record->direct_competitors ?? 'N/A'),

                            Forms\Components\Placeholder::make('target_market')
                                ->label('Mercado objetivo')
                                ->content($record->target_market ?? 'N/A'),
                        ]),
                ])
                ->collapsible(),
        ];

        // Agregar cada pregunta
        foreach ($questions as $question) {
            $descriptionContent = '<div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">' .
                nl2br(e($question->description)) .
                '</div>';

            // Si es la pregunta 11 (Criterio global), agregar subcriterios
            if ($question->question_number == 11 && $question->target_role == 'evaluator') {
                $descriptionContent .= '
    <div class="mt-4 grid grid-cols-6 gap-2">
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Escalabilidad</strong>
            <p class="text-xs mt-1">El negocio tiene la capacidad de crecer exponencialmente.</p>
        </div>
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Tendencias y disrupciones del mercado</strong>
            <p class="text-xs mt-1">Los cambios disruptivos en la industria, pueden afectar a la empresa.</p>
        </div>
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Adaptabilidad del equipo emprendedor</strong>
            <p class="text-xs mt-1">El equipo de liderazgo de la empresa puede adaptarse y manejar cambios en el entorno.</p>
        </div>
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Barreras de entrada y competencia</strong>
            <p class="text-xs mt-1">Dificultad para entrar al mercado y enfrentar a la competencia existente en el largo plazo.</p>
        </div>
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Expansión de mercados y productos</strong>
            <p class="text-xs mt-1">Capacidad para introducir nuevos productos o servicios y expandirse a nuevos mercados.</p>
        </div>
        <div class="border border-gray-300 dark:border-gray-600 p-2 text-center">
            <strong class="text-xs">Retorno de la inversión</strong>
            <p class="text-xs mt-1">Expectativas de beneficios económicos que la empresa podría generar tanto a corto como a largo plazo.</p>
        </div>
    </div>
';
            }

            $formFields[] = Forms\Components\Section::make($question->question_number . '. ' . $question->question_text)
                ->schema([
                    Forms\Components\Placeholder::make("description_{$question->id}")
                        ->label('Descripción')
                        ->content(new \Illuminate\Support\HtmlString($descriptionContent))
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make("score_{$question->id}")
                        ->label('Calificación')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10)
                        ->step(0.1)
                        ->suffix('/ 10')
                        ->helperText('Ponderación: ' . ($question->weight * 100) . '%')
                ])
                ->collapsible()
                ->compact();
        }

        // Agregar campo de comentarios
        $formFields[] = Forms\Components\Section::make('Recomendaciones')
            ->schema([
                Forms\Components\Textarea::make('comments')
                    ->label('¿Qué recomendación le daría al emprendedor(a) para mejorar su plan de negocio?')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->collapsible();

        return $formFields;
    }

    /**
     * Guarda una nueva evaluación
     */
    private static function saveEvaluation(BusinessPlan $record, array $data): void
    {
        $questions = BusinessPlanEvaluationQuestion::forEvaluators()->get();

        try {
            DB::beginTransaction();

            foreach ($questions as $question) {
                $scoreKey = "score_{$question->id}";

                if (!isset($data[$scoreKey])) {
                    throw new \Exception("Falta la calificación para: {$question->question_text}");
                }

                BusinessPlanEvaluation::create([
                    'business_plan_id' => $record->id,
                    'evaluator_id' => auth()->id(),
                    'question_id' => $question->id,
                    'evaluator_type' => 'evaluator',
                    'question_number' => $question->question_number,
                    'score' => $data[$scoreKey],
                    'comments' => $data['comments'] ?? null,
                ]);
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Evaluación guardada')
                ->body('Su evaluación ha sido registrada correctamente.')
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
    }

    /**
     * Actualiza una evaluación existente
     */
    private static function updateEvaluation(BusinessPlan $record, array $data): void
    {
        $questions = BusinessPlanEvaluationQuestion::forEvaluators()->get();

        try {
            DB::beginTransaction();

            foreach ($questions as $question) {
                $scoreKey = "score_{$question->id}";

                if (!isset($data[$scoreKey])) {
                    throw new \Exception("Falta la calificación para: {$question->question_text}");
                }

                $evaluation = BusinessPlanEvaluation::where('business_plan_id', $record->id)
                    ->where('evaluator_id', auth()->id())
                    ->where('evaluator_type', 'evaluator')
                    ->where('question_id', $question->id)
                    ->first();

                if ($evaluation) {
                    $evaluation->update([
                        'score' => $data[$scoreKey],
                        'comments' => $data['comments'] ?? null,
                    ]);
                } else {
                    // Si por alguna razón no existe, crearla
                    BusinessPlanEvaluation::create([
                        'business_plan_id' => $record->id,
                        'evaluator_id' => auth()->id(),
                        'question_id' => $question->id,
                        'evaluator_type' => 'evaluator',
                        'question_number' => $question->question_number,
                        'score' => $data[$scoreKey],
                        'comments' => $data['comments'] ?? null,
                    ]);
                }
            }

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
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessPlanEvaluations::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = BusinessPlan::where('is_prioritized', true)
            ->whereDoesntHave('evaluations', function ($q) {
                $q->where('evaluator_id', auth()->id())
                    ->where('evaluator_type', 'evaluator');
            })
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
