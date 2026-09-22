<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentFairParticipationResource\Pages;
use App\Models\Actor;
use App\Models\EducationalInstitution;
use App\Models\Student;
use App\Models\StudentFair;
use App\Models\StudentFairParticipation;
use App\Models\Teacher;
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

class StudentFairParticipationResource extends Resource
{
    protected static ?string $model = StudentFairParticipation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Ferias Estudiantiles';
    protected static ?string $navigationLabel = 'Participaciones';
    protected static ?string $modelLabel      = 'Participación en Feria';
    protected static ?string $pluralModelLabel = 'Participaciones en Ferias';
    protected static ?int    $navigationSort  = 2;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentFairParticipations') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentFairParticipation') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentFairParticipation') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentFairParticipation') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['fair', 'educationalInstitution']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Tab: Feria y Participantes ────────────────────────────────────
            Forms\Components\Tabs::make('Participación')
                ->columnSpanFull()
                ->tabs([

                    Forms\Components\Tabs\Tab::make('Feria y Participantes')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([

                            Forms\Components\Select::make('student_fair_id')
                                ->label('Feria')
                                ->options(fn () => StudentFair::withoutTrashed()
                                    ->orderBy('start_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($f) => [
                                        $f->id => $f->name.' — '.$f->location.' ('.$f->start_date->format('d/m/Y').')',
                                    ])
                                )
                                ->searchable()
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('educational_institution_id')
                                ->label('Institución Educativa')
                                ->options(fn () => EducationalInstitution::withoutTrashed()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($i) => [$i->id => $i->display_name])
                                )
                                ->searchable()
                                ->required()
                                ->live(),

                            Forms\Components\DatePicker::make('participation_date')
                                ->label('Fecha de Participación')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\Select::make('students')
                                ->label('Estudiantes Participantes')
                                ->multiple()
                                ->relationship('students', 'name')
                                ->options(fn (Get $get) => Student::withoutTrashed()
                                    ->when($get('educational_institution_id'), fn ($q, $id) =>
                                        $q->where('educational_institution_id', $id)
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->helperText('Seleccione primero la institución para filtrar estudiantes'),

                            Forms\Components\Select::make('teachers')
                                ->label('Docentes Participantes')
                                ->multiple()
                                ->relationship('teachers', 'name')
                                ->options(fn (Get $get) => Teacher::withoutTrashed()
                                    ->when($get('educational_institution_id'), fn ($q, $id) =>
                                        $q->where('educational_institution_id', $id)
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->helperText('Seleccione primero la institución para filtrar docentes'),
                        ]),

                    // ── Tab: Aliados ──────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Aliados Invitados')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\Select::make('actors')
                                ->label('Actores de Ruta D / Aliados')
                                ->multiple()
                                ->relationship('actors', 'name')
                                ->options(fn () => Actor::withoutTrashed()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                )
                                ->searchable(),
                        ]),

                    // ── Tab: Resultados ───────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Resultados')
                        ->icon('heroicon-o-chart-bar')
                        ->columns(2)
                        ->schema([

                            Forms\Components\Section::make('Articulaciones')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('generated_articulations')
                                        ->label('¿Se generaron articulaciones con actores externos?')
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\CheckboxList::make('articulation_actor_types')
                                        ->label('Tipos de actores articulados')
                                        ->options(Actor::TYPE_OPTIONS)
                                        ->columns(2)
                                        ->visible(fn (Get $g) => (bool) $g('generated_articulations'))
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('articulations_count')
                                        ->label('Número de articulaciones generadas')
                                        ->options(StudentFair::ARTICULATIONS_COUNT_OPTIONS)
                                        ->visible(fn (Get $g) => (bool) $g('generated_articulations')),
                                ]),

                            Forms\Components\Section::make('Encadenamiento Productivo')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('identified_chain_opportunity')
                                        ->label('¿Se identificó oportunidad de encadenamiento productivo?')
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('chain_link')
                                        ->label('Eslabón en la cadena')
                                        ->options(StudentFair::CHAIN_LINK_OPTIONS)
                                        ->visible(fn (Get $g) => (bool) $g('identified_chain_opportunity')),

                                    Forms\Components\CheckboxList::make('chain_actor_types')
                                        ->label('Tipos de actores en la cadena')
                                        ->options(Actor::TYPE_OPTIONS)
                                        ->columns(2)
                                        ->visible(fn (Get $g) => (bool) $g('identified_chain_opportunity'))
                                        ->columnSpanFull(),
                                ]),

                            Forms\Components\Section::make('Ventas')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Toggle::make('had_sales')
                                        ->label('¿Se realizaron ventas durante la feria?')
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('sales_range')
                                        ->label('Rango de ventas')
                                        ->options(StudentFair::SALES_RANGE_OPTIONS)
                                        ->visible(fn (Get $g) => (bool) $g('had_sales')),

                                    Forms\Components\TextInput::make('exact_sales_amount')
                                        ->label('Monto exacto de ventas (opcional)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->visible(fn (Get $g) => (bool) $g('had_sales')),

                                    Forms\Components\Select::make('sales_balance')
                                        ->label('Balance de ventas')
                                        ->options(StudentFair::SALES_BALANCE_OPTIONS)
                                        ->visible(fn (Get $g) => (bool) $g('had_sales')),
                                ]),
                        ]),

                    // ── Tab: Experiencia ──────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Experiencia')
                        ->icon('heroicon-o-star')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('organization_rating')
                                ->label('Calificación de la organización')
                                ->options(StudentFair::ORGANIZATION_RATING_OPTIONS)
                                ->required(),

                            Forms\Components\Select::make('visitor_flow')
                                ->label('Flujo de visitantes')
                                ->options(StudentFair::VISITOR_FLOW_OPTIONS)
                                ->required(),

                            Forms\Components\Toggle::make('generated_contacts')
                                ->label('¿Se generaron contactos comerciales o institucionales?')
                                ->columnSpanFull(),
                        ]),

                    // ── Tab: Evidencias ───────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Evidencias')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción de la participación')
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('attendance_list_path')
                                ->label('Lista de Asistencia')
                                ->disk('public')
                                ->directory('student-fair-attendance')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->downloadable()
                                ->openable()
                                ->helperText('PDF, JPG o PNG (máx. 10 MB)'),

                            Forms\Components\FileUpload::make('photos')
                                ->label('Fotografías')
                                ->disk('public')
                                ->directory('student-fair-photos')
                                ->image()
                                ->multiple()
                                ->maxFiles(10)
                                ->maxSize(5120)
                                ->reorderable()
                                ->helperText('Hasta 10 fotos (máx. 5 MB cada una)'),
                        ]),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('participation_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fair.name')
                    ->label('Feria')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? '—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('participation_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Estudiantes')
                    ->counts('students')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('organization_rating')
                    ->label('Calificación')
                    ->formatStateUsing(fn ($state) => StudentFair::ORGANIZATION_RATING_OPTIONS[$state] ?? '—')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'excelente' => 'success',
                        'buena'     => 'info',
                        'regular'   => 'warning',
                        'deficiente'=> 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('had_sales')
                    ->label('Ventas')
                    ->getStateUsing(fn ($record) => $record->had_sales ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sí' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('student_fair_id')
                    ->label('Feria')
                    ->options(fn () => StudentFair::withoutTrashed()->orderBy('start_date', 'desc')->pluck('name', 'id')),
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
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'participaciones-ferias-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($q) => $q->with(['fair', 'educationalInstitution', 'students', 'teachers', 'actors', 'manager']))
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
                                ->withFilename(fn () => 'participaciones-ferias-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($q) => $q->with(['fair', 'educationalInstitution', 'students', 'teachers', 'actors', 'manager']))
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
            'index'  => Pages\ListStudentFairParticipations::route('/'),
            'create' => Pages\CreateStudentFairParticipation::route('/create'),
            'edit'   => Pages\EditStudentFairParticipation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('fair_name')->heading('Feria')
                ->getStateUsing(fn ($r) => $r->fair?->name ?? ''),
            Column::make('fair_location')->heading('Municipio Feria')
                ->getStateUsing(fn ($r) => $r->fair?->location ?? ''),
            Column::make('institution')->heading('Institución Educativa')
                ->getStateUsing(fn ($r) => $r->educationalInstitution?->display_name ?? ''),
            Column::make('participation_date')->heading('Fecha Participación')
                ->getStateUsing(fn ($r) => $r->participation_date?->format('d/m/Y') ?? ''),
            Column::make('students')->heading('Estudiantes')
                ->getStateUsing(fn ($r) => $r->students->pluck('name')->implode(', ')),
            Column::make('teachers')->heading('Docentes')
                ->getStateUsing(fn ($r) => $r->teachers->pluck('name')->implode(', ')),
            Column::make('actors')->heading('Aliados Invitados')
                ->getStateUsing(fn ($r) => $r->actors->pluck('name')->implode(', ')),
            Column::make('generated_articulations')->heading('Articulaciones Generadas')
                ->getStateUsing(fn ($r) => $r->generated_articulations ? 'Sí' : 'No'),
            Column::make('articulations_count')->heading('N° Articulaciones')
                ->getStateUsing(fn ($r) => $r->articulations_count ? (StudentFair::ARTICULATIONS_COUNT_OPTIONS[$r->articulations_count] ?? $r->articulations_count) : ''),
            Column::make('identified_chain_opportunity')->heading('Oportunidad Encadenamiento')
                ->getStateUsing(fn ($r) => $r->identified_chain_opportunity ? 'Sí' : 'No'),
            Column::make('chain_link')->heading('Eslabón Cadena')
                ->getStateUsing(fn ($r) => $r->chain_link ? (StudentFair::CHAIN_LINK_OPTIONS[$r->chain_link] ?? $r->chain_link) : ''),
            Column::make('had_sales')->heading('Tuvo Ventas')
                ->getStateUsing(fn ($r) => $r->had_sales ? 'Sí' : 'No'),
            Column::make('sales_range')->heading('Rango Ventas')
                ->getStateUsing(fn ($r) => $r->sales_range ? (StudentFair::SALES_RANGE_OPTIONS[$r->sales_range] ?? $r->sales_range) : ''),
            Column::make('exact_sales_amount')->heading('Monto Exacto Ventas'),
            Column::make('sales_balance')->heading('Balance Ventas')
                ->getStateUsing(fn ($r) => $r->sales_balance ? (StudentFair::SALES_BALANCE_OPTIONS[$r->sales_balance] ?? $r->sales_balance) : ''),
            Column::make('organization_rating')->heading('Calificación Organización')
                ->getStateUsing(fn ($r) => $r->organization_rating ? (StudentFair::ORGANIZATION_RATING_OPTIONS[$r->organization_rating] ?? $r->organization_rating) : ''),
            Column::make('visitor_flow')->heading('Flujo Visitantes')
                ->getStateUsing(fn ($r) => $r->visitor_flow ? (StudentFair::VISITOR_FLOW_OPTIONS[$r->visitor_flow] ?? $r->visitor_flow) : ''),
            Column::make('generated_contacts')->heading('Contactos Generados')
                ->getStateUsing(fn ($r) => $r->generated_contacts ? 'Sí' : 'No'),
            Column::make('description')->heading('Descripción'),
            Column::make('manager_name')->heading('Registrado por')
                ->getStateUsing(fn ($r) => $r->manager?->name ?? ''),
            Column::make('created_at')->heading('Fecha Registro')
                ->getStateUsing(fn ($r) => $r->created_at?->format('d/m/Y H:i') ?? ''),
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
}
