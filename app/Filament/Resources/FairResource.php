<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FairResource\Pages;
use App\Filament\Resources\FairResource\RelationManagers;
use App\Models\Fair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;

//Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class FairResource extends Resource
{
    protected static ?string $model = Fair::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Actores';

    protected static ?string $modelLabel = 'Feria';

    protected static ?string $pluralModelLabel = 'Ferias';

    protected static ?int $navigationSort = 2;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listFairs');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createFair');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editFair');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteFair');
    }

    public static function canViewAny(): bool
    {
        return static::userCanList();
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

    // Permitir ver registros eliminados
    public static function canRestore($record): bool
    {
        return static::userCanDelete();
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()->hasRole('Admin'); // Solo Admin puede eliminar permanentemente
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Registro de Feria')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Datos de la Feria')
                                    ->description('Información básica del evento')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Feria')
                                            ->maxLength(100)
                                            ->required()
                                            ->placeholder('Nombre del evento en mayúsculas')
                                            ->unique(
                                                table: Fair::class,
                                                column: 'name',
                                                ignoreRecord: true
                                            )
                                            ->validationMessages([
                                                'unique' => 'Ya existe una feria registrada con este nombre.',
                                            ]),

                                        Forms\Components\TextInput::make('location')
                                            ->label('Municipio / Lugar de Realización')
                                            ->maxLength(50)
                                            ->required()
                                            ->placeholder('Ciudad o municipio del evento'),

                                        Forms\Components\Textarea::make('address')
                                            ->label('Dirección Exacta / Espacio Asignado')
                                            ->required()
                                            ->rows(3)
                                            ->placeholder('Dirección completa donde se realizará la feria')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('latitude')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->placeholder('Ej: 10.9639997')
                                            ->helperText('Coordenada de latitud del lugar')
                                            ->step(0.00000001)
                                            ->required()
                                            ->minValue(-90)
                                            ->maxValue(90),

                                        Forms\Components\TextInput::make('longitude')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->placeholder('Ej: -74.7965423')
                                            ->helperText('Coordenada de longitud del lugar')
                                            ->step(0.00000001)

                                            ->minValue(-180)
                                            ->maxValue(180),

                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Fecha de Inicio')
                                            ->required()
                                            ->native(true)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->live()
                                            ->maxDate(fn(Get $get) => $get('end_date'))
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                $endDate = $get('end_date');
                                                if ($endDate && $state > $endDate) {
                                                    $set('end_date', null);
                                                }
                                            }),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('Fecha de Finalización')
                                            ->required()
                                            ->native(true)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->live()
                                            ->minDate(fn(Get $get) => $get('start_date'))
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                $startDate = $get('start_date');
                                                if ($startDate && $state < $startDate) {
                                                    $set('start_date', null);
                                                }
                                            })
                                            ->helperText(function (Get $get) {
                                                $startDate = $get('start_date');
                                                $endDate = $get('end_date');

                                                if ($startDate && $endDate) {
                                                    $start = \Carbon\Carbon::parse($startDate);
                                                    $end = \Carbon\Carbon::parse($endDate);
                                                    $days = $start->diffInDays($end) + 1;
                                                    return "Duración: {$days} " . ($days === 1 ? 'día' : 'días');
                                                }

                                                return null;
                                            }),

                                        // Campos de auditoría (solo en edit y view)
                                        Forms\Components\Placeholder::make('manager_info')
                                            ->label('Registrado por')
                                            ->content(fn($record) => $record?->manager?->name ?? '----')
                                            ->visible(fn($operation) => $operation === 'edit' || $operation === 'view'),

                                        Forms\Components\Placeholder::make('created_info')
                                            ->label('Fecha de Registro')
                                            ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? '----')
                                            ->visible(fn($operation) => $operation === 'edit' || $operation === 'view'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Organización')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('Organización y Propiedad')
                                    ->description('Datos del responsable o coordinador del evento')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Forms\Components\TextInput::make('organizer_name')
                                            ->label('Nombre del Organizador')
                                            ->maxLength(100)
                                            ->required()
                                            ->placeholder('Nombre completo del responsable')
                                            ->rule('regex:/^[\pL\s]+$/u')
                                            ->validationMessages([
                                                'regex' => 'El nombre solo puede contener letras y espacios.',
                                            ]),

                                        Forms\Components\TextInput::make('organizer_position')
                                            ->label('Cargo')
                                            ->maxLength(100)
                                            ->required()
                                            ->placeholder('Cargo o rol del organizador'),

                                        Forms\Components\TextInput::make('organizer_phone')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('+57 300 123 4567')
                                            ->regex('/^[\+]?[0-9\s\-\(\)]+$/'),

                                        Forms\Components\TextInput::make('organizer_email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('organizador@ejemplo.com'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Observaciones')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Notas Adicionales')
                                    ->description('Información complementaria sobre la feria')
                                    ->icon('heroicon-o-pencil-square')
                                    ->schema([
                                        Forms\Components\Textarea::make('observations')
                                            ->label('Observaciones')
                                            ->placeholder('Agregue cualquier comentario, nota o detalle adicional sobre la feria')
                                            ->rows(5)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Feria')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Finalización')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('organizer_name')
                    ->label('Organizador')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->visible(fn() => static::userCanList()),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar emprendedor')
                    ->visible(
                        fn($record) =>
                        !$record->trashed() &&
                            static::userCanEdit() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(
                        fn($record) =>
                        !$record->trashed() &&
                            static::userCanDelete() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar caracterización')
                    ->visible(fn($record) => $record->trashed() && static::userCanDelete()),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Eliminar permanentemente')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar permanentemente?')
                    ->modalDescription('Esta acción NO se puede deshacer.')
                    ->visible(fn() => auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn() => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        ExcelExport::make()
                            ->withFilename(fn() => 'ferias-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN GENERAL DE LA FERIA ===
                                Column::make('name')->heading('Nombre de la Feria'),
                                Column::make('location')->heading('Municipio / Lugar'),
                                Column::make('address')->heading('Dirección / Espacio'),

                                Column::make('start_date')->heading('Fecha de Inicio')
                                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                Column::make('end_date')->heading('Fecha de Finalización')
                                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                Column::make('duration_days')->heading('Duración (días)')
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($record->start_date && $record->end_date) {
                                            return $record->start_date->diffInDays($record->end_date) + 1;
                                        }
                                        return '';
                                    }),

                                Column::make('status')->heading('Estado')
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'upcoming' => 'Próxima',
                                        'in_progress' => 'En Curso',
                                        'finished' => 'Finalizada',
                                        default => $state,
                                    }),

                                // === ORGANIZACIÓN Y PROPIEDAD ===
                                Column::make('organizer_name')->heading('Nombre del Organizador'),
                                Column::make('organizer_position')->heading('Cargo'),
                                Column::make('organizer_phone')->heading('Teléfono'),
                                Column::make('organizer_email')->heading('Correo Electrónico'),

                                // === OBSERVACIONES ===
                                Column::make('observations')->heading('Observaciones'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),

                                Column::make('created_at')->heading('Fecha de Registro')
                                    ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),

                                Column::make('updated_at')->heading('Última Actualización')
                                    ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),
                            ]),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(fn() => 'ferias-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN GENERAL DE LA FERIA ===
                                    Column::make('name')->heading('Nombre de la Feria'),
                                    Column::make('location')->heading('Municipio / Lugar'),
                                    Column::make('address')->heading('Dirección / Espacio'),

                                    Column::make('start_date')->heading('Fecha de Inicio')
                                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                    Column::make('end_date')->heading('Fecha de Finalización')
                                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                    Column::make('duration_days')->heading('Duración (días)')
                                        ->formatStateUsing(function ($state, $record) {
                                            if ($record->start_date && $record->end_date) {
                                                return $record->start_date->diffInDays($record->end_date) + 1;
                                            }
                                            return '';
                                        }),

                                    Column::make('status')->heading('Estado')
                                        ->formatStateUsing(fn($state) => match ($state) {
                                            'upcoming' => 'Próxima',
                                            'in_progress' => 'En Curso',
                                            'finished' => 'Finalizada',
                                            default => $state,
                                        }),

                                    // === ORGANIZACIÓN Y PROPIEDAD ===
                                    Column::make('organizer_name')->heading('Nombre del Organizador'),
                                    Column::make('organizer_position')->heading('Cargo'),
                                    Column::make('organizer_phone')->heading('Teléfono'),
                                    Column::make('organizer_email')->heading('Correo Electrónico'),

                                    // === OBSERVACIONES ===
                                    Column::make('observations')->heading('Observaciones'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),

                                    Column::make('created_at')->heading('Fecha de Registro')
                                        ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),

                                    Column::make('updated_at')->heading('Última Actualización')
                                        ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),
                                ]),
                        ]),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Ajusta según tu sistema de roles
        if (auth()->user()->hasRole(['Admin', 'Viewer'])) { // o hasRole('admin')
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFairs::route('/'),
            'create' => Pages\CreateFair::route('/create'),
            'edit' => Pages\EditFair::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Si no es admin, filtrar solo sus registros
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
