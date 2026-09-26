<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentFairResource\Pages;
use App\Models\StudentFair;
use App\Support\ColombiaBounds;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentFairResource extends Resource
{
    protected static ?string $model = StudentFair::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Ferias Estudiantiles';
    protected static ?string $navigationLabel = 'Ferias';
    protected static ?string $modelLabel      = 'Feria Estudiantil';
    protected static ?string $pluralModelLabel = 'Ferias Estudiantiles';
    protected static ?int    $navigationSort  = 1;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentFairs') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentFair') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentFair') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentFair') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Tabs::make('Feria')
                ->columnSpanFull()
                ->tabs([

                    Forms\Components\Tabs\Tab::make('Información General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre de la Feria')
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null)
                                ->rule(static::uniqueNameRule())
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('location')
                                ->label('Municipio / Lugar de Realización')
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('address')
                                ->label('Dirección Exacta / Espacio Asignado')
                                ->required()
                                ->rows(3)
                                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('latitude')
                                ->label('Latitud')
                                ->required()
                                ->numeric()
                                ->step(0.00000001)
                                ->minValue(ColombiaBounds::latitudeRange()[0])
                                ->maxValue(ColombiaBounds::latitudeRange()[1])
                                ->placeholder('Ej: 10.9639997')
                                ->rule(static::colombiaCoordinatesRule()),

                            Forms\Components\TextInput::make('longitude')
                                ->label('Longitud')
                                ->required()
                                ->numeric()
                                ->step(0.00000001)
                                ->minValue(ColombiaBounds::longitudeRange()[0])
                                ->maxValue(ColombiaBounds::longitudeRange()[1])
                                ->placeholder('Ej: -74.7965423')
                                ->helperText('La longitud en Colombia siempre es negativa.')
                                ->rule(static::colombiaCoordinatesRule()),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Fecha de Inicio')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->live(),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Fecha de Finalización')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                // Solo se habilita cuando ya hay fecha de inicio, y nunca
                                // permite una fecha anterior a ésta.
                                ->disabled(fn (Forms\Get $get): bool => blank($get('start_date')))
                                ->minDate(fn (Forms\Get $get) => $get('start_date'))
                                ->afterOrEqual('start_date')
                                ->validationMessages([
                                    'after_or_equal' => 'La fecha de finalización no puede ser anterior a la fecha de inicio.',
                                ])
                                ->helperText(fn (Forms\Get $get): ?string => blank($get('start_date'))
                                    ? 'Registre primero la fecha de inicio.'
                                    : null),
                        ]),

                    Forms\Components\Tabs\Tab::make('Organización')
                        ->icon('heroicon-o-user-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('organizer_name')
                                ->label('Nombre del Organizador')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Responsable del evento')
                                ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                                ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                            Forms\Components\TextInput::make('organizer_position')
                                ->label('Cargo')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Cargo o rol del organizador'),

                            Forms\Components\TextInput::make('organizer_phone')
                                ->label('Teléfono')
                                ->required()
                                ->tel()
                                // La máscara impide teclear o pegar cualquier cosa que no
                                // sea un dígito; el regex es el respaldo en servidor.
                                ->mask('9999999999')
                                ->length(10)
                                ->rule('regex:/^\d{10}$/')
                                ->extraInputAttributes(['inputmode' => 'numeric'])
                                ->placeholder('3001234567')
                                ->helperText('10 dígitos, sin espacios ni símbolos.')
                                ->validationMessages([
                                    'regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
                                    'size'  => 'El teléfono debe tener exactamente 10 dígitos.',
                                ]),

                            Forms\Components\TextInput::make('organizer_email')
                                ->label('Correo Electrónico')
                                ->required()
                                ->email()
                                ->maxLength(255),
                        ]),

                    Forms\Components\Tabs\Tab::make('Observaciones')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->schema([
                            Forms\Components\Textarea::make('observations')
                                ->label('Observaciones')
                                ->required()
                                ->rows(5)
                                ->helperText('Comentarios o detalles adicionales sobre la feria')
                                ->columnSpanFull(),
                        ]),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Feria')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Municipio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('participations_count')
                    ->label('Participaciones')
                    ->counts('participations')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('organizer_name')
                    ->label('Organizador')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin'))
                    ->modalHeading('Eliminar feria definitivamente')
                    ->modalDescription(fn ($record) => static::forceDeleteWarning($record))
                    ->modalSubmitActionLabel('Sí, eliminar definitivamente'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'ferias-estudiantiles-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
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
                                ->withFilename(fn () => 'ferias-estudiantiles-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
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
            'index'  => Pages\ListStudentFairs::route('/'),
            'create' => Pages\CreateStudentFair::route('/create'),
            'edit'   => Pages\EditStudentFair::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    /**
     * Aviso del modal de borrado definitivo. La FK de participaciones está
     * declarada con cascadeOnDelete, así que un DELETE real sobre la feria
     * arrastra todas sus participaciones. Se cuentan sin global scopes porque
     * la cascada también se lleva las deshabilitadas y las de otros años.
     */
    private static function forceDeleteWarning(StudentFair $record): string
    {
        $count = $record->participations()
            ->withoutGlobalScopes()
            ->count();

        if ($count === 0) {
            return 'Esta feria no tiene participaciones registradas. La acción no se puede deshacer.';
        }

        $detalle = $count === 1
            ? 'Se eliminará también 1 participación asociada'
            : sprintf('Se eliminarán también %d participaciones asociadas', $count);

        return $detalle.' a esta feria, junto con sus estudiantes, docentes y aliados vinculados. La acción no se puede deshacer.';
    }

    /**
     * Valida el par latitud/longitud contra el territorio colombiano. Se
     * aplica a ambos campos para que el error se marque en el que el usuario
     * esté corrigiendo; sólo actúa cuando los dos tienen valor.
     */
    private static function colombiaCoordinatesRule(): Closure
    {
        return static function (Forms\Get $get): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($get): void {
                $latitude  = $get('latitude');
                $longitude = $get('longitude');

                if (! is_numeric($latitude) || ! is_numeric($longitude)) {
                    return;
                }

                if (! ColombiaBounds::contains((float) $latitude, (float) $longitude)) {
                    $fail('Las coordenadas no corresponden a territorio colombiano. Verifique que no estén invertidas y que la longitud sea negativa.');
                }
            };
        };
    }

    /**
     * El nombre de la feria no se puede repetir. La comparación ignora
     * mayúsculas/minúsculas y espacios sobrantes, y respeta el año en
     * contexto (YearColumnScope), de modo que una feria anual puede volver
     * a registrarse con el mismo nombre en un año distinto. Se tienen en
     * cuenta las ferias deshabilitadas para que restaurarlas no genere
     * duplicados.
     */
    private static function uniqueNameRule(): Closure
    {
        return static function (?StudentFair $record): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($record): void {
                $name = mb_strtoupper(trim((string) $value));

                if ($name === '') {
                    return;
                }

                $existing = StudentFair::query()
                    ->withoutGlobalScopes([SoftDeletingScope::class])
                    ->whereRaw('UPPER(TRIM(name)) = ?', [$name])
                    ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                    ->first();

                if (! $existing) {
                    return;
                }

                $fail($existing->trashed()
                    ? 'Ya existe una feria con este nombre, pero está deshabilitada. Restáurela o use otro nombre.'
                    : 'Ya existe una feria registrada con este nombre.');
            };
        };
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('name')->heading('Nombre Feria'),
            Column::make('location')->heading('Municipio'),
            Column::make('address')->heading('Dirección'),
            Column::make('latitude')->heading('Latitud'),
            Column::make('longitude')->heading('Longitud'),
            Column::make('start_date')->heading('Fecha Inicio')
                ->getStateUsing(fn ($record) => $record->start_date?->format('d/m/Y') ?? ''),
            Column::make('end_date')->heading('Fecha Fin')
                ->getStateUsing(fn ($record) => $record->end_date?->format('d/m/Y') ?? ''),
            Column::make('organizer_name')->heading('Organizador'),
            Column::make('organizer_position')->heading('Cargo'),
            Column::make('organizer_phone')->heading('Teléfono'),
            Column::make('organizer_email')->heading('Correo'),
            Column::make('observations')->heading('Observaciones'),
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
