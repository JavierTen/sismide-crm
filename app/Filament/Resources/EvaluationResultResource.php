<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResultResource\Pages;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
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
                        $evaluatorsAvg = BusinessPlanEvaluation::getAllEvaluatorsAverage($record->id);
                        $managersAvg = BusinessPlanEvaluation::getAllManagersAverage($record->id);

                        if ($evaluatorsAvg == 0 && $managersAvg == 0) {
                            return 'Pendiente';
                        }

                        $finalScore = BusinessPlanEvaluation::getFinalScore($record->id);
                        return number_format($finalScore, 2) . ' / 10';
                    })
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
        $sheet->mergeCells('A' . $row . ':E' . $row);
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
        $sheet->setCellValue('E' . $row, 'Comentarios');
        static::styleTableHeader($sheet, 'A' . $row . ':E' . $row);
        $row++;

        $startRow = $row;

        // Listar evaluaciones
        foreach ($evaluations->sortBy('question.question_number') as $evaluation) {
            $sheet->setCellValue('A' . $row, $evaluation->question_number);
            $sheet->setCellValue('B' . $row, $evaluation->question->question_text);
            $sheet->setCellValue('C' . $row, $evaluation->question->weight);
            $sheet->setCellValue('D' . $row, $evaluation->score);
            $sheet->setCellValue('E' . $row, $evaluation->comments ?? '');

            // Formatear peso como porcentaje
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0%');

            static::styleTableRow($sheet, 'A' . $row . ':E' . $row);
            $row++;
        }

        // Promedio ponderado
        $row++;
        $sheet->setCellValue('C' . $row, 'PROMEDIO PONDERADO:');
        $sheet->setCellValue('D' . $row, '=SUMPRODUCT(D' . $startRow . ':D' . ($row - 2) . ',C' . $startRow . ':C' . ($row - 2) . ')');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.00');
        static::styleTotalRow($sheet, 'C' . $row . ':D' . $row);

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(50);
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

        // Tabla de resultados
        $sheet->setCellValue('A' . $row, 'Tipo');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Promedio');
        $sheet->setCellValue('D' . $row, 'Ponderación');
        static::styleTableHeader($sheet, 'A' . $row . ':D' . $row);
        $row++;

        // Evaluadores
        $evaluatorsCount = BusinessPlanEvaluation::getEvaluatorsCount($businessPlan->id);
        $evaluatorsAvg = BusinessPlanEvaluation::getAllEvaluatorsAverage($businessPlan->id);

        $sheet->setCellValue('A' . $row, 'Evaluadores');
        $sheet->setCellValue('B' . $row, $evaluatorsCount);
        $sheet->setCellValue('C' . $row, $evaluatorsAvg);
        $sheet->setCellValue('D' . $row, 0.90);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0%');
        static::styleTableRow($sheet, 'A' . $row . ':D' . $row);
        $row++;

        // Gestores
        $managersCount = BusinessPlanEvaluation::getManagersCount($businessPlan->id);
        $managersAvg = BusinessPlanEvaluation::getAllManagersAverage($businessPlan->id);

        $sheet->setCellValue('A' . $row, 'Gestores');
        $sheet->setCellValue('B' . $row, $managersCount);
        $sheet->setCellValue('C' . $row, $managersAvg);
        $sheet->setCellValue('D' . $row, 0.10);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0%');
        static::styleTableRow($sheet, 'A' . $row . ':D' . $row);
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
