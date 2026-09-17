<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\EducationalInstitutionResource\Pages;
use App\Models\City;
use App\Models\EducationalInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class EducationalInstitutionResource extends Resource
{
    protected static ?string $model = EducationalInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Institución Educativa';

    protected static ?string $pluralModelLabel = 'Instituciones Educativas';

    /**
     * Municipios habilitados para este subproyecto.
     */
    private const ALLOWED_CITY_IDS = [21, 49, 219, 352, 1096];

    private static function userCanList(): bool   { return auth()->user()?->can('listEducationalInstitutions') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createEducationalInstitution') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editEducationalInstitution') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteEducationalInstitution') ?? false; }

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

                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('Municipio')
                            ->options(fn () => City::whereIn('id', self::ALLOWED_CITY_IDS)->pluck('name', 'id'))
                            ->placeholder('Seleccione el municipio')
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Institución Educativa')
                            ->placeholder('Ej: I.E. NORMAL SUPERIOR')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('campus')
                            ->label('Sede')
                            ->placeholder('Ej: SEDE PRINCIPAL')
                            ->helperText('Si la institución no tiene sedes, escriba "Principal".')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('principal_name')
                            ->label('Nombre Rector(a)')
                            ->placeholder('Ej: MARIA RODRIGUEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('proposed_teacher')
                            ->label('Docente Propuesto')
                            ->placeholder('Ej: CARLOS GOMEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

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
                            ->placeholder('Ej: institucion@correo.edu.co')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Institución')
                    ->formatStateUsing(fn (EducationalInstitution $record) => $record->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('principal_name')
                    ->label('Rector(a)'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->trashed() && static::userCanEdit() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->trashed() && static::userCanDelete() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'instituciones-educativas-'.now()->format('Y-m-d-His'))
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
                                ->withFilename(fn () => 'instituciones-educativas-'.now()->format('Y-m-d-His'))
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
            'index' => Pages\ListEducationalInstitutions::route('/'),
            'create' => Pages\CreateEducationalInstitution::route('/create'),
            'edit' => Pages\EditEducationalInstitution::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportWith(): array
    {
        return ['city', 'manager'];
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('display_name')->heading('Institución Educativa')
                ->getStateUsing(fn ($record) => $record->display_name),
            Column::make('city_name')->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->city?->name ?? ''),
            Column::make('principal_name')->heading('Rector(a)'),
            Column::make('proposed_teacher')->heading('Docente Propuesto'),
            Column::make('phone')->heading('Teléfono'),
            Column::make('email')->heading('Correo'),
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
