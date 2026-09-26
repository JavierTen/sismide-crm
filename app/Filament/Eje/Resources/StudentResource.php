<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentResource\Pages;
use App\Models\DocumentType;
use App\Models\EducationalInstitution;
use App\Models\Gender;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Estudiante';

    protected static ?string $pluralModelLabel = 'Estudiantes';

    private static function userCanList(): bool   { return auth()->user()?->can('listStudents') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudent') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudent') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudent') ?? false; }

    public static function canViewAny(): bool              { return static::userCanList(); }
    public static function canCreate(): bool               { return static::userCanCreate(); }
    public static function canEdit($record): bool          { return static::userCanEdit(); }
    public static function canDelete($record): bool        { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Identificación y relaciones')
                    ->schema([
                        Forms\Components\Select::make('educational_institution_id')
                            ->label('Institución Educativa')
                            ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                                fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                            ))
                            ->placeholder('Seleccione la institución')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set(
                                    'municipio_derivado',
                                    $state ? EducationalInstitution::find($state)?->city?->name : null
                                );
                                $set('teachers', []);
                            })
                            ->required(),

                        Forms\Components\TextInput::make('municipio_derivado')
                            ->label('Municipio')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se completa según la institución')
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $record) {
                                if ($record) {
                                    $component->state($record->educationalInstitution?->city?->name);
                                }
                            }),

                        Forms\Components\Select::make('teachers')
                            ->label('Docente(s) a cargo')
                            ->relationship(
                                'teachers',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) => $query
                                    ->where('educational_institution_id', $get('educational_institution_id')),
                            )
                            ->placeholder('Primero seleccione la institución')
                            ->helperText('Solo se muestran los docentes registrados en la institución seleccionada.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Forms\Get $get) => blank($get('educational_institution_id')))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos personales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->placeholder('Ej: LAURA GOMEZ')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\Select::make('document_type_id')
                            ->label('Tipo de documento')
                            ->options(fn () => DocumentType::pluck('name', 'id'))
                            ->placeholder('Seleccione el tipo de documento')
                            ->required(),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Documento de identidad')
                            ->placeholder('Ej: 1065432109')
                            ->inputMode('numeric')
                            ->maxLength(20)
                            ->regex('/^[0-9]+$/')
                            ->extraInputAttributes([
                                'maxlength' => '20',
                                'oninput'   => "this.value=this.value.replace(/[^0-9]/g,'').slice(0,20)",
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('age')
                            ->label('Edad')
                            ->placeholder('Ej: 16')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(25),

                        Forms\Components\Select::make('gender_id')
                            ->label('Género')
                            ->options(fn () => Gender::pluck('name', 'id'))
                            ->placeholder('Seleccione el género'),

                        Forms\Components\Select::make('grade')
                            ->label('Grado')
                            ->options(Student::gradeOptions())
                            ->placeholder('Seleccione el grado')
                            ->required(),

                        Forms\Components\TextInput::make('course')
                            ->label('Curso')
                            ->placeholder('Ej: 10-1')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos de contacto')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Ej: 3001234567')
                            ->tel()
                            ->inputMode('numeric')
                            ->maxLength(10)
                            ->regex('/^[0-9]{10}$/')
                            ->extraInputAttributes([
                                'maxlength' => '10',
                                'oninput'   => "this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)",
                            ]),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->placeholder('Ej: estudiante@correo.com')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Acudiente')
                    ->schema([
                        Forms\Components\TextInput::make('guardian_name')
                            ->label('Nombre acudiente')
                            ->placeholder('Ej: MARIA GOMEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('guardian_phone')
                            ->label('Teléfono acudiente')
                            ->placeholder('Ej: 3007654321')
                            ->tel()
                            ->inputMode('numeric')
                            ->maxLength(10)
                            ->regex('/^[0-9]{10}$/')
                            ->extraInputAttributes([
                                'maxlength' => '10',
                                'oninput'   => "this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)",
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('Grado')
                    ->formatStateUsing(fn (?string $state) => Student::gradeOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('course')
                    ->label('Curso'),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (Student $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('gender.name')
                    ->label('Género'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('educational_institution_id')
                    ->label('Institución')
                    ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                        fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                    )),

                Tables\Filters\SelectFilter::make('grade')
                    ->label('Grado')
                    ->options(Student::gradeOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar')
                    ->visible(fn ($record) => !$record->trashed() && static::userCanEdit() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Deshabilitar')
                    ->visible(fn ($record) => !$record->trashed() && static::userCanDelete() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\RestoreAction::make()->label('')->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
                Tables\Actions\ForceDeleteAction::make()->label('')->tooltip('Eliminar definitivamente')
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'estudiantes-'.now()->format('Y-m-d-His'))
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
                                ->withFilename(fn () => 'estudiantes-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with(self::exportWith()))
                                ->withColumns(self::exportColumns())
                                ->afterSheet(self::afterSheetCallback()),
                        ]),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportWith(): array
    {
        return ['educationalInstitution.city', 'documentType', 'gender', 'teachers', 'manager'];
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('name')->heading('Nombre'),
            Column::make('doc_type')->heading('Tipo de Documento')
                ->getStateUsing(fn ($record) => $record->documentType?->name ?? ''),
            Column::make('document_number')->heading('Documento de Identidad'),
            Column::make('age')->heading('Edad'),
            Column::make('gender')->heading('Género')
                ->getStateUsing(fn ($record) => $record->gender?->name ?? ''),
            Column::make('grade')->heading('Grado')
                ->getStateUsing(fn ($record) => Student::gradeOptions()[$record->grade] ?? $record->grade),
            Column::make('course')->heading('Curso'),
            Column::make('phone')->heading('Teléfono'),
            Column::make('email')->heading('Correo'),
            Column::make('guardian_name')->heading('Nombre Acudiente'),
            Column::make('guardian_phone')->heading('Teléfono Acudiente'),
            Column::make('institution')->heading('Institución Educativa')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? ''),
            Column::make('city')->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->city?->name ?? ''),
            Column::make('teachers')->heading('Docente(s) a cargo')
                ->getStateUsing(fn ($record) => $record->teachers->pluck('name')->implode(', ')),
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
}
