<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentCanvasResource\Pages;
use App\Models\Student;
use App\Models\StudentCanvas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentCanvasResource extends Resource
{
    protected static ?string $model = StudentCanvas::class;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Comunidad Educativa';
    protected static ?string $navigationLabel = 'Canvas Estudiantes';
    protected static ?string $modelLabel      = 'Canvas';
    protected static ?string $pluralModelLabel = 'Canvas';
    protected static ?int    $navigationSort  = 10;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentCanvases') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentCanvas') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentCanvas') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentCanvas') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student.educationalInstitution']);
    }

    // ── FORMULARIO ─────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Estudiante')
                ->icon('heroicon-o-academic-cap')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Estudiante')
                        ->columnSpan(2)
                        ->options(fn () => Student::withoutTrashed()
                            ->with('educationalInstitution')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => $s->name . ' — ' . ($s->educationalInstitution?->name ?? '—'),
                            ])
                            ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated(true)
                        ->live()
                        ->unique(table: 'student_canvases', column: 'student_id', ignoreRecord: true),

                    Forms\Components\Placeholder::make('institution')
                        ->label('Institución')
                        ->content(fn (Get $get) => static::getStudentField($get('student_id'), 'institution')),

                    Forms\Components\Placeholder::make('grade')
                        ->label('Grado')
                        ->content(fn (Get $get) => static::getStudentField($get('student_id'), 'grade')),
                ]),

            Forms\Components\Section::make('Modelo de Negocios / Canvas')
                ->icon('heroicon-o-squares-2x2')
                ->description('Documenta las 6 dimensiones del modelo de negocio')
                ->columns(2)
                ->schema([
                    Forms\Components\Textarea::make('problem_identification')
                        ->label('El problema — ¿Qué quieres solucionar?')
                        ->helperText('¿Qué problema o necesidad has identificado en tu entorno?')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('business_idea')
                        ->label('Tu idea — Tu solución')
                        ->helperText('¿Cuál es tu idea de negocio y cómo busca solucionar el problema?')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('differentiator')
                        ->label('¿Qué te hace diferente?')
                        ->helperText('¿Qué hace diferente a tu producto o servicio frente a otras alternativas?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('achievements')
                        ->label('Tus resultados — Lo que has logrado')
                        ->helperText('¿Qué has logrado hasta ahora con tu emprendimiento?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('business_model_description')
                        ->label('¿Cómo funciona?')
                        ->helperText('¿Cómo funciona tu negocio y cómo generas ingresos?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('next_steps')
                        ->label('Tu próximo paso — ¿Qué necesitas ahora?')
                        ->helperText('¿Qué necesitas actualmente para fortalecer o hacer crecer tu negocio?')
                        ->rows(4)
                        ->required(),
                ]),

            Forms\Components\Section::make('Documentos')
                ->icon('heroicon-o-document-arrow-up')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('canvas_file_path')
                        ->label('Documento Canvas *')
                        ->disk('public')
                        ->directory('student-canvas')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->required()
                        ->downloadable()
                        ->openable()
                        ->helperText('Obligatorio — PDF, JPG o PNG (máx. 10 MB)'),

                    Forms\Components\TextInput::make('fire_pitch_video_url')
                        ->label('Video Fire Pitch (URL) *')
                        ->url()
                        ->required()
                        ->placeholder('https://youtube.com/...')
                        ->helperText('Obligatorio para estudiantes EJE'),
                ]),

        ]);
    }

    // ── TABLA ───────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.educationalInstitution.name')
                    ->label('Institución')
                    ->default('—'),

                Tables\Columns\TextColumn::make('student.grade')
                    ->label('Grado')
                    ->formatStateUsing(fn ($state) => $state ? $state . '°' : '—'),

                Tables\Columns\TextColumn::make('canvas_file_path')
                    ->label('Archivo')
                    ->getStateUsing(fn ($record) => ! empty($record->canvas_file_path) ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sí' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('fire_pitch_video_url')
                    ->label('Fire Pitch')
                    ->getStateUsing(fn ($record) => ! empty($record->fire_pitch_video_url) ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sí' ? 'success' : 'danger'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar')
                    ->visible(fn ($record) => ! $record->trashed() && static::userCanEdit() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Deshabilitar')
                    ->visible(fn ($record) => ! $record->trashed() && static::userCanDelete() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\RestoreAction::make()->label('')->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),
                Tables\Actions\ForceDeleteAction::make()->label('')->tooltip('Eliminar definitivamente')
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'canvas-estudiantes-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with(self::exportWith()))
                            ->withColumns(self::exportColumns())
                            ->afterSheet(self::afterSheetCallback()),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel')
                        ->exports([
                            FormattedExcelExport::make()
                                ->withFilename(fn () => 'canvas-estudiantes-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with(self::exportWith()))
                                ->withColumns(self::exportColumns())
                                ->afterSheet(self::afterSheetCallback()),
                        ]),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentCanvases::route('/'),
            'create' => Pages\CreateStudentCanvas::route('/create'),
            'edit'   => Pages\EditStudentCanvas::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportWith(): array
    {
        return ['student.educationalInstitution.city', 'manager'];
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('student_name')->heading('Estudiante')
                ->getStateUsing(fn ($record) => $record->student?->name ?? ''),
            Column::make('institution')->heading('Institución Educativa')
                ->getStateUsing(fn ($record) => $record->student?->educationalInstitution?->display_name ?? ''),
            Column::make('city')->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->student?->educationalInstitution?->city?->name ?? ''),
            Column::make('grade')->heading('Grado')
                ->getStateUsing(fn ($record) => $record->student?->grade ? $record->student->grade.'°' : ''),
            Column::make('problem_identification')->heading('El problema'),
            Column::make('business_idea')->heading('Tu idea / Solución'),
            Column::make('differentiator')->heading('¿Qué te hace diferente?'),
            Column::make('achievements')->heading('Resultados logrados'),
            Column::make('business_model_description')->heading('¿Cómo funciona?'),
            Column::make('next_steps')->heading('Próximo paso / Necesidades'),
            Column::make('canvas_file_path')->heading('Documento Canvas')
                ->getStateUsing(fn ($record) => ! empty($record->canvas_file_path) ? 'Sí' : 'No'),
            Column::make('fire_pitch_video_url')->heading('Video Fire Pitch'),
            Column::make('manager_name')->heading('Registrado por')
                ->getStateUsing(fn ($record) => $record->manager?->name ?? ''),
            Column::make('created_at')->heading('Fecha Registro')
                ->getStateUsing(fn ($record) => $record->created_at?->format('d/m/Y H:i') ?? ''),
        ];
    }

    private static function afterSheetCallback(): \Closure
    {
        return function (\Maatwebsite\Excel\Events\AfterSheet $event) {
            $sheet        = $event->sheet->getDelegate();
            $highest      = $sheet->getHighestRowAndColumn();
            $lastCol      = $highest['column'];
            $lastRow      = $highest['row'];
            $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);

            $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E40AF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);

            if ($lastRow > 1) {
                $sheet->getStyle('A2:'.$lastCol.$lastRow)->getAlignment()
                    ->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            }

            for ($i = 1; $i <= $lastColIndex; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastCol.'1');
        };
    }

    // ── HELPERS ─────────────────────────────────────────────────────────────────

    private static function getStudentField(?int $id, string $field): string
    {
        if (! $id) return '—';
        $s = Student::with('educationalInstitution')->find($id);
        if (! $s) return '—';

        return match ($field) {
            'institution' => $s->educationalInstitution?->name ?? '—',
            'grade'       => $s->grade ? $s->grade . '°' : '—',
            default       => '—',
        };
    }
}
