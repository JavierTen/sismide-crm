<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResultResource\Pages;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use App\Models\BusinessPlanEvaluationQuestion;
use App\Exports\EvaluationResultsExport;
use App\Support\YearContext;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

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

    private static function userCanExport(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->can('exportEvaluationResults');
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
            ->select('business_plans.*')
            ->selectRaw(BusinessPlanEvaluation::finalScoreSql() . ' AS final_score_value')
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
            // De mayor a menor nota; quien aún no tiene nota va al final.
            ->defaultSort('final_score', 'desc')
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

                // Solo cuántos evaluadores calificaron: la nota está en "Calificación Final".
                Tables\Columns\TextColumn::make('evaluators_score')
                    ->label('N.º de evaluadores')
                    ->getStateUsing(fn (BusinessPlan $record): int => BusinessPlanEvaluation::getEvaluatorsCount($record->id))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('managers_score')
                    ->label('Gestores')
                    // Desde 2026 el gestor ya no califica.
                    ->visible(fn (): bool => BusinessPlanEvaluationQuestion::managerApplies(YearContext::effectiveYear()))
                    ->getStateUsing(function (BusinessPlan $record): string {
                        if (! BusinessPlanEvaluation::managerAppliesToPlan($record->id)) {
                            return 'No aplica';
                        }

                        $average = BusinessPlanEvaluation::getAllManagersAverage($record->id);
                        $count = BusinessPlanEvaluation::getManagersCount($record->id);

                        if ($count === 0) {
                            return 'Sin evaluar';
                        }

                        return number_format($average, 2) . ' (' . $count . ' gestor' . ($count > 1 ? 'es' : '') . ')';
                    })
                    ->badge()
                    ->color(fn (string $state) => str_contains($state, 'Sin') || $state === 'No aplica' ? 'gray' : 'warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Calificación Final')
                    ->getStateUsing(fn (BusinessPlan $record): string => $record->final_score_value === null
                        ? 'Pendiente'
                        : number_format((float) $record->final_score_value, 2) . ' / 10')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw('final_score_value IS NULL')
                        ->orderBy('final_score_value', $direction))
                    ->badge()
                    ->color(function (string $state) {
                        if (str_contains($state, 'Pendiente')) {
                            return 'gray';
                        }

                        $score = (float) str_replace(' / 10', '', $state);

                        if ($score >= 8) return 'success';
                        if ($score >= 6) return 'warning';
                        return 'danger';
                    })
                    ->weight('bold')
                    ->alignCenter()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('score_order')
                    ->label('Puntaje')
                    ->options([
                        'desc' => 'De mayor a menor',
                        'asc'  => 'De menor a mayor',
                    ])
                    ->default('desc')
                    ->selectablePlaceholder(false)
                    // El orden por defecto no cuenta como filtro activo.
                    ->indicateUsing(fn (array $data): array => ($data['value'] ?? null) === 'asc'
                        ? ['Puntaje: de menor a mayor']
                        : [])
                    // No filtra registros: el orden lo aplica ListEvaluationResults.
                    ->query(fn (Builder $query): Builder => $query),

                // Solo los municipios que tienen algún emprendedor en el consolidado.
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->options(fn (): array => static::evaluatedCities())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('entrepreneur', fn (Builder $q) => $q->where('city_id', $data['value']))
                        : $query)
                    ->searchable(),

                Tables\Filters\Filter::make('fully_evaluated')
                    ->label('Completamente evaluados')
                    ->visible(fn (): bool => BusinessPlanEvaluationQuestion::managerApplies(YearContext::effectiveYear()))
                    ->query(function ($query) {
                        return $query->whereHas('evaluatorEvaluations')
                            ->whereHas('managerEvaluations');
                    }),

                Tables\Filters\Filter::make('pending_managers')
                    ->label('Pendientes de gestores')
                    ->visible(fn (): bool => BusinessPlanEvaluationQuestion::managerApplies(YearContext::effectiveYear()))
                    ->query(function ($query) {
                        return $query->whereHas('evaluatorEvaluations')
                            ->whereDoesntHave('managerEvaluations');
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_results')
                    ->label('Exportar resultados')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn () => static::userCanExport())
                    ->action(function ($livewire) {
                        // Respeta los filtros, la búsqueda y el orden del listado.
                        $plans    = $livewire->getFilteredSortedTableQuery()->get();
                        $fileName = 'Consolidado_evaluaciones_' . now()->format('Y-m-d-His') . '.xlsx';

                        \App\Support\ExportLogger::log('Consolidado de evaluaciones del plan de negocio', [
                            'file'    => $fileName,
                            'records' => $plans->count(),
                            'filters' => \App\Support\ExportLogger::activeFilters($livewire->tableFilters ?? null),
                            'search'  => $livewire->tableSearch ?? null,
                        ]);

                        return Excel::download(new EvaluationResultsExport($plans), $fileName);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('export_excel')
                    ->label('')
                    ->icon('heroicon-o-document-arrow-down')
                    ->tooltip('Exportar a Excel')
                    ->color('success')
                    ->visible(fn () => static::userCanExport())
                    ->action(function (BusinessPlan $record) {
                        try {
                            $tempFile = static::exportToExcel($record);

                            $fileName = 'Evaluacion_' . \Illuminate\Support\Str::slug($record->entrepreneur->full_name) . '.xlsx';

                            \App\Support\ExportLogger::log('Evaluación del plan de negocio de ' . $record->entrepreneur->full_name, [
                                'file'    => $fileName,
                                'records' => 1,
                            ]);

                            return response()->download($tempFile, $fileName, [
                                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])->deleteFileAfterSend(true);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al exportar')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // Sin acciones bulk
            ]);
    }

    /**
     * Exportar evaluación a Excel
     */
    private static function exportToExcel(BusinessPlan $businessPlan): string
    {
        $spreadsheet = new Spreadsheet();

        // Eliminar la hoja por defecto
        $spreadsheet->removeSheetByIndex(0);

        // Obtener todas las evaluaciones
        $evaluatorEvaluations = $businessPlan->evaluatorEvaluations()
            ->with(['question', 'evaluator'])
            ->get()
            ->groupBy('evaluator_id');

        $managerEvaluations = $businessPlan->managerEvaluations()
            ->with(['question', 'evaluator'])
            ->get()
            ->groupBy('evaluator_id');

        // Crear hoja por cada evaluador
        $evaluatorCount = 1;
        foreach ($evaluatorEvaluations as $evaluatorId => $evaluations) {
            $evaluator = $evaluations->first()->evaluator;
            $sheetName = "Evaluador_" . $evaluatorCount;

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            static::createEvaluatorSheet($sheet, $businessPlan, $evaluator, $evaluations);

            $evaluatorCount++;
        }

        // Crear hoja por cada gestor
        $managerCount = 1;
        foreach ($managerEvaluations as $managerId => $evaluations) {
            $manager = $evaluations->first()->evaluator;
            $sheetName = "Gestor_" . $managerCount;

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);

            static::createManagerSheet($sheet, $businessPlan, $manager, $evaluations);

            $managerCount++;
        }

        // Crear hoja de resultado final
        $finalSheet = $spreadsheet->createSheet();
        $finalSheet->setTitle("Resultado_Final");
        static::createFinalResultSheet($finalSheet, $businessPlan);

        // Activar la primera hoja
        $spreadsheet->setActiveSheetIndex(0);

        // Guardar archivo
        $tempFile = tempnam(sys_get_temp_dir(), 'evaluation_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    private static function createEvaluatorSheet($sheet, $businessPlan, $evaluator, $evaluations)
    {
        $row = 1;

        // Encabezado
        $sheet->setCellValue('A' . $row, 'EVALUACIÓN DE PLAN DE NEGOCIO - EVALUADOR');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        static::styleHeader($sheet, 'A' . $row);
        $row += 2;

        // Información del emprendedor
        $sheet->setCellValue('A' . $row, 'Emprendedor:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->full_name);
        $row++;

        $sheet->setCellValue('A' . $row, 'Emprendimiento:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->business?->business_name ?? 'N/A');
        $row++;

        $sheet->setCellValue('A' . $row, 'Fecha:');
        $sheet->setCellValue('B' . $row, $evaluations->first()->created_at->format('d/m/Y'));
        $row += 2;

        // Encabezados de tabla
        $sheet->setCellValue('A' . $row, '#');
        $sheet->setCellValue('B' . $row, 'Pregunta');
        $sheet->setCellValue('C' . $row, 'Ponderación');
        $sheet->setCellValue('D' . $row, 'Calificación');
        static::styleTableHeader($sheet, 'A' . $row . ':D' . $row);
        $row++;

        $startRow = $row;

        // Listar evaluaciones
        foreach ($evaluations->sortBy('question.question_number') as $evaluation) {
            $sheet->setCellValue('A' . $row, $evaluation->question_number);
            $sheet->setCellValue('B' . $row, $evaluation->question->question_text);
            $sheet->setCellValue('C' . $row, $evaluation->question->weight);
            $sheet->setCellValue('D' . $row, $evaluation->score);

            // Formatear peso como porcentaje
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0%');

            static::styleTableRow($sheet, 'A' . $row . ':D' . $row);
            $row++;
        }

        $endRow = $row - 1;

        // Comentarios: son los mismos para todo el formulario, van una sola vez.
        $row++;
        $comments = (string) ($evaluations->first()->comments ?? '');

        $sheet->setCellValue('A' . $row, 'Comentarios');
        static::styleTableHeader($sheet, 'A' . $row);
        $sheet->setCellValue('B' . $row, $comments);
        $sheet->mergeCells('B' . $row . ':D' . $row);
        $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        // Las celdas combinadas no se ajustan solas al texto: alto según su largo.
        $lines = max(1, (int) ceil(mb_strlen($comments) / 85) + substr_count($comments, "\n"));
        $sheet->getRowDimension($row)->setRowHeight(max(15, $lines * 15));
        $row += 2;

        // Promedio ponderado: dividido por la suma de las ponderaciones, igual
        // que en la pantalla (el formato 2025 no suma exactamente 100 %).
        $sheet->setCellValue('B' . $row, 'PROMEDIO PONDERADO:');
        $sheet->mergeCells('B' . $row . ':C' . $row);
        $sheet->setCellValue('D' . $row, "=SUMPRODUCT(D{$startRow}:D{$endRow},C{$startRow}:C{$endRow})/SUM(C{$startRow}:C{$endRow})");
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.00');
        static::styleTotalRow($sheet, 'B' . $row . ':D' . $row);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
    }

    private static function createManagerSheet($sheet, $businessPlan, $manager, $evaluations)
    {
        $row = 1;

        // Encabezado
        $sheet->setCellValue('A' . $row, 'EVALUACIÓN DE PLAN DE NEGOCIO - GESTOR');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        static::styleHeader($sheet, 'A' . $row);
        $row += 2;

        // Información del emprendedor
        $sheet->setCellValue('A' . $row, 'Emprendedor:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->full_name);
        $row++;

        $sheet->setCellValue('A' . $row, 'Emprendimiento:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->business?->business_name ?? 'N/A');
        $row++;

        $sheet->setCellValue('A' . $row, 'Fecha:');
        $sheet->setCellValue('B' . $row, $evaluations->first()->created_at->format('d/m/Y'));
        $row += 2;

        // Encabezados de tabla
        $sheet->setCellValue('A' . $row, 'Criterio');
        $sheet->setCellValue('B' . $row, 'Ponderación');
        $sheet->setCellValue('C' . $row, 'Calificación');
        $sheet->setCellValue('D' . $row, 'Comentarios');
        static::styleTableHeader($sheet, 'A' . $row . ':D' . $row);
        $row++;

        // Listar evaluación
        $evaluation = $evaluations->first();
        $sheet->setCellValue('A' . $row, $evaluation->question->question_text);
        $sheet->setCellValue('B' . $row, $evaluation->question->weight);
        $sheet->setCellValue('C' . $row, $evaluation->score);
        $sheet->setCellValue('D' . $row, $evaluation->comments ?? '');

        // Formatear peso como porcentaje
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('0%');

        static::styleTableRow($sheet, 'A' . $row . ':D' . $row);

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(50);
    }

    private static function createFinalResultSheet($sheet, $businessPlan)
    {
        $row = 1;

        // Encabezado
        $sheet->setCellValue('A' . $row, 'RESULTADO FINAL DE EVALUACIÓN');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        static::styleHeader($sheet, 'A' . $row);
        $row += 2;

        // Información del emprendedor
        $sheet->setCellValue('A' . $row, 'Emprendedor:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->full_name);
        $sheet->mergeCells('B' . $row . ':D' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'Emprendimiento:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->business?->business_name ?? 'N/A');
        $sheet->mergeCells('B' . $row . ':D' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'Municipio:');
        $sheet->setCellValue('B' . $row, $businessPlan->entrepreneur->city?->name ?? 'N/A');
        $sheet->mergeCells('B' . $row . ':D' . $row);
        $row += 2;

        // Calificación de cada evaluador, sin su nombre: "Evaluador 1", "Evaluador 2"…
        // en el mismo orden de sus hojas (Evaluador_1, Evaluador_2…).
        $sheet->setCellValue('A' . $row, 'Evaluador');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('D' . $row, 'Calificación');
        static::styleTableHeader($sheet, 'A' . $row . ':D' . $row);
        $row++;

        $scores = $businessPlan->evaluatorEvaluations()
            ->get()
            ->groupBy('evaluator_id')
            ->keys()
            ->values()
            ->map(fn ($evaluatorId, $i) => [
                'name'  => 'Evaluador ' . ($i + 1),
                'score' => BusinessPlanEvaluation::getEvaluatorAverage($businessPlan->id, $evaluatorId),
            ]);

        // Formato 2025: el gestor también califica (10 % de la nota final).
        if (BusinessPlanEvaluation::managerAppliesToPlan($businessPlan->id)) {
            $managerScores = $businessPlan->managerEvaluations()
                ->get()
                ->values()
                ->map(fn ($rating, $i) => [
                    'name'  => 'Gestor ' . ($i + 1),
                    'score' => (float) $rating->score,
                ]);

            $scores = $scores->concat($managerScores);
        }

        foreach ($scores as $score) {
            $sheet->setCellValue('A' . $row, $score['name']);
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('D' . $row, $score['score']);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        $row += 2;

        // Calificación final
        $finalScore = BusinessPlanEvaluation::getFinalScore($businessPlan->id);

        $sheet->setCellValue('A' . $row, 'CALIFICACIÓN FINAL:');
        $sheet->setCellValue('B' . $row, number_format($finalScore, 2) . ' / 10');
        $sheet->mergeCells('B' . $row . ':D' . $row);
        static::styleTotalRow($sheet, 'A' . $row . ':D' . $row);

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
    }

    private static function styleHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private static function styleTableHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B9BD5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private static function styleTableRow($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
    }

    private static function styleTotalRow($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFD966'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                ],
            ],
        ]);
    }

    /**
     * Municipios con al menos un emprendedor en el consolidado (plan
     * priorizado y evaluado) en el año que se está consultando.
     *
     * @return array<int, string>
     */
    private static function evaluatedCities(): array
    {
        $entrepreneurIds = BusinessPlan::query()
            ->where('is_prioritized', true)
            ->whereHas('evaluations')
            ->pluck('entrepreneur_id');

        $cityIds = \App\Models\Entrepreneur::withoutGlobalScope(\App\Scopes\YearColumnScope::class)
            ->whereIn('id', $entrepreneurIds)
            ->whereNotNull('city_id')
            ->distinct()
            ->pluck('city_id');

        return \App\Models\City::whereIn('id', $cityIds)->orderBy('name')->pluck('name', 'id')->all();
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
        // Desde 2026 basta con los evaluadores: el gestor ya no califica.
        $count = BusinessPlan::where('is_prioritized', true)
            ->whereHas('evaluatorEvaluations')
            ->where(fn ($query) => $query
                ->whereYear('business_plans.created_at', '>=', BusinessPlanEvaluationQuestion::EVALUATORS_ONLY_FROM)
                ->orWhereHas('managerEvaluations'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
