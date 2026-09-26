<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\TeacherResource\Pages;
use App\Models\DocumentType;
use App\Models\EducationalInstitution;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Docente';

    protected static ?string $pluralModelLabel = 'Docentes';

    private static function userCanList(): bool   { return auth()->user()?->can('listTeachers') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createTeacher') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editTeacher') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteTeacher') ?? false; }

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

                Forms\Components\Section::make('Identificación y relación')
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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos generales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej: ANA MARTINEZ')
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

                        Forms\Components\TextInput::make('area')
                            ->label('Área')
                            ->placeholder('Ej: CIENCIAS SOCIALES')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->placeholder('Ej: docente@correo.edu.co')
                            ->email()
                            ->maxLength(255),

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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vinculación al programa')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(Teacher::statusOptions())
                            ->placeholder('Seleccione el estado')
                            ->default('active')
                            ->required(),

                        Forms\Components\DatePicker::make('program_start_date')
                            ->label('Fecha de vinculación al programa')
                            ->placeholder('Seleccione la fecha'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->placeholder('Notas del gestor')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('Área'),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (Teacher $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => Teacher::statusOptions()[$state] ?? $state)
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('educational_institution_id')
                    ->label('Institución')
                    ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                        fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                    )),

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
                            ->withFilename(fn () => 'docentes-'.now()->format('Y-m-d-His'))
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
                                ->withFilename(fn () => 'docentes-'.now()->format('Y-m-d-His'))
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportWith(): array
    {
        return ['educationalInstitution.city', 'documentType', 'manager'];
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('name')->heading('Docente'),
            Column::make('doc_type')->heading('Tipo de Documento')
                ->getStateUsing(fn ($record) => $record->documentType?->name ?? ''),
            Column::make('document_number')->heading('Documento de Identidad'),
            Column::make('area')->heading('Área'),
            Column::make('email')->heading('Correo'),
            Column::make('phone')->heading('Teléfono'),
            Column::make('status')->heading('Estado')
                ->getStateUsing(fn ($record) => Teacher::statusOptions()[$record->status] ?? $record->status),
            Column::make('program_start_date')->heading('Fecha de Vinculación')
                ->getStateUsing(fn ($record) => $record->program_start_date?->format('d/m/Y') ?? ''),
            Column::make('institution')->heading('Institución Educativa')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? ''),
            Column::make('city')->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->city?->name ?? ''),
            Column::make('notes')->heading('Observaciones'),
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
