<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingResource\Pages;
use App\Filament\Resources\TrainingResource\RelationManagers;
use App\Models\Training;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

//Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $modelLabel = 'Capacitación';

    protected static ?string $pluralModelLabel = 'Capacitaciones';

    protected static ?int $navigationSort = 1;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listTrainings');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createTraining');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editTraining');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteTraining');
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
                Forms\Components\Section::make('Información de la Capacitación')
                    ->description('Datos principales del evento de formación')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la Capacitación')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Taller de Marketing Digital')
                                    ->helperText('Título descriptivo de la capacitación')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('city_id')
                                    ->label('Municipio')
                                    ->relationship(
                                        'city',
                                        'name',
                                        fn($query) => $query->where('department_id', 47)
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Seleccione el municipio')
                                    ->helperText('Lugar donde se realizará la capacitación'),

                                Forms\Components\DateTimePicker::make('training_date')
                                    ->label('Fecha y Hora')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->seconds(false)
                                    ->placeholder('Seleccione fecha y hora')
                                    ->helperText('Fecha y hora programada del evento')
                                    ->minDate(now()->subMonths(6))
                                    ->maxDate(now()->addYear()),

                                Forms\Components\Select::make('route')
                                    ->label('Ruta de Formación')
                                    ->options([
                                        'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                        'route_2' => 'Ruta 2: Consolidación',
                                        'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->placeholder('Seleccione la ruta')
                                    ->helperText('Nivel del emprendimiento al que está dirigida')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Datos del Organizador')
                    ->description('Información de contacto del responsable')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('organizer_name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Juan Carlos Pérez')
                                    ->helperText('Nombre del responsable de la capacitación'),

                                Forms\Components\TextInput::make('organizer_position')
                                    ->label('Cargo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Coordinador de Capacitaciones')
                                    ->helperText('Cargo que desempeña el organizador'),

                                Forms\Components\TextInput::make('organizer_phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->required()
                                    ->maxLength(10)
                                    ->minLength(10)
                                    ->placeholder('Ej: 3001234567')
                                    ->helperText('Número de contacto del organizador (10 dígitos)')
                                    ->numeric()
                                    ->extraInputAttributes([
                                        'pattern' => '[0-9]*',
                                        'inputmode' => 'numeric',
                                    ])
                                    ->rules([
                                        'regex:/^[0-9]{10}$/'
                                    ]),

                                Forms\Components\TextInput::make('organizer_entity')
                                    ->label('Entidad u Organización')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Fundación Emprendedores')
                                    ->helperText('Institución que organiza el evento'),

                                Forms\Components\TextInput::make('organizer_email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: contacto@organizacion.com')
                                    ->helperText('Email de contacto del organizador')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Modalidad y Material de Apoyo')
                    ->description('Formato del evento y recursos complementarios')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('modality')
                                    ->label('Modalidad')
                                    ->options([
                                        'virtual' => 'Virtual',
                                        'in_person' => 'Presencial',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->placeholder('Seleccione modalidad')
                                    ->helperText('Formato de realización del evento')
                                    ->live()
                                    ->reactive(),

                                Forms\Components\Textarea::make('objective')
                                    ->label('Objetivo o Descripción')
                                    ->rows(3)
                                    ->placeholder('Describa el objetivo principal de la capacitación...')
                                    ->helperText('Propósito y alcance del evento (opcional)')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Archivos y Material Digital')
                    ->description('Documentos de soporte y recursos multimedia')
                    ->icon('heroicon-o-folder-open')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\FileUpload::make('ppt_file_path')
                                    ->label('Presentación PPT/PDF')
                                    ->directory('trainings/presentations')
                                    ->disk('public')
                                    ->maxSize(10240) // 10MB
                                    ->downloadable()
                                    ->openable()
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                    ])
                                    ->helperText('Archivo PPT/PPTX/PDF utilizado en la capacitación (máximo 10MB) - No obligatorio')
                                    ->validationMessages([
                                        'max' => 'El archivo no puede superar los 10MB.',
                                    ]),

                                Forms\Components\FileUpload::make('promotional_file_path')
                                    ->label('Pieza de Divulgación')
                                    ->directory('trainings/promotional')
                                    ->disk('public')
                                    ->maxSize(5120) // 5MB
                                    ->downloadable()
                                    ->openable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->helperText('Afiche, flyer o material promocional (máximo 5MB) - No obligatorio')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->validationMessages([
                                        'max' => 'El archivo no puede superar los 5MB.',
                                    ]),

                                Forms\Components\TextInput::make('recording_link')
                                    ->label('Link de Grabación')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://meet.google.com/xxx-xxxx-xxx o https://zoom.us/rec/share/...')
                                    ->helperText('URL de la grabación (solo para modalidad virtual) - No obligatorio')
                                    ->visible(fn($get) => $get('modality') === 'virtual')
                                    ->prefixIcon('heroicon-o-video-camera'),
                            ]),

                        Forms\Components\Placeholder::make('files_info')
                            ->label('Nota Importante')
                            ->content('Todos los archivos son opcionales. Solo suba los documentos que considere relevantes para la capacitación.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Campo oculto para manager_id (se llena automáticamente)
                Forms\Components\Hidden::make('manager_id')
                    ->default(auth()->id()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('training_date', 'desc')
            ->modifyQueryUsing(function ($query) {
                return $query->withTrashed();
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Capacitación')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin municipio'),

                Tables\Columns\TextColumn::make('training_date')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),

                Tables\Columns\TextColumn::make('modality')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'virtual' => 'success',
                        'in_person' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'virtual' => 'Virtual',
                        'in_person' => 'Presencial',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('route')
                    ->label('Ruta')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'route_1' => 'warning',
                        'route_2' => 'info',
                        'route_3' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'route_1' => 'Ruta 1',
                        'route_2' => 'Ruta 2',
                        'route_3' => 'Ruta 3',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('route')
                    ->label('Ruta')
                    ->options([
                        'route_1' => 'Ruta 1: Pre-emprendimiento',
                        'route_2' => 'Ruta 2: Consolidación',
                        'route_3' => 'Ruta 3: Escalamiento',
                    ]),

                Tables\Filters\SelectFilter::make('modality')
                    ->label('Modalidad')
                    ->options([
                        'virtual' => 'Virtual',
                        'in_person' => 'Presencial',
                    ]),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(),
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
                    ->tooltip('Editar capacitación')
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
                    ->tooltip('Restaurar capacitación')
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
                            ->withFilename(fn() => 'capacitaciones-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'city',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                Column::make('name')->heading('Nombre de la Capacitación'),
                                Column::make('city.name')->heading('Municipio'),
                                Column::make('training_date')->heading('Fecha y Hora')->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),
                                Column::make('route')->heading('Ruta')->formatStateUsing(fn($state) => match ($state) {
                                    'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                    'route_2' => 'Ruta 2: Consolidación',
                                    'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                    default => $state,
                                }),

                                // === DATOS DEL ORGANIZADOR ===
                                Column::make('organizer_name')->heading('Nombre del Organizador'),
                                Column::make('organizer_position')->heading('Cargo'),
                                Column::make('organizer_phone')->heading('Teléfono'),
                                Column::make('organizer_entity')->heading('Entidad u Organización'),
                                Column::make('organizer_email')->heading('Correo Electrónico'),

                                // === MODALIDAD Y MATERIAL ===
                                Column::make('modality')->heading('Modalidad')->formatStateUsing(fn($state) => match ($state) {
                                    'virtual' => 'Virtual',
                                    'in_person' => 'Presencial',
                                    default => $state,
                                }),
                                Column::make('objective')->heading('Objetivo o Descripción'),

                                // === ARCHIVOS ===
                                Column::make('ppt_file_path')->heading('Tiene PPT')->formatStateUsing(fn($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('promotional_file_path')->heading('Tiene Pieza de Divulgación')->formatStateUsing(fn($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('recording_link')->heading('Link de Grabación')->formatStateUsing(fn($state) => !empty($state) ? $state : 'No disponible'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
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
                                ->withFilename(fn() => 'capacitaciones-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'city',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                    Column::make('name')->heading('Nombre de la Capacitación'),
                                    Column::make('city.name')->heading('Municipio'),
                                    Column::make('training_date')->heading('Fecha y Hora')->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),
                                    Column::make('route')->heading('Ruta')->formatStateUsing(fn($state) => match ($state) {
                                        'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                        'route_2' => 'Ruta 2: Consolidación',
                                        'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                        default => $state,
                                    }),

                                    // === DATOS DEL ORGANIZADOR ===
                                    Column::make('organizer_name')->heading('Nombre del Organizador'),
                                    Column::make('organizer_position')->heading('Cargo'),
                                    Column::make('organizer_phone')->heading('Teléfono'),
                                    Column::make('organizer_entity')->heading('Entidad u Organización'),
                                    Column::make('organizer_email')->heading('Correo Electrónico'),

                                    // === MODALIDAD Y MATERIAL ===
                                    Column::make('modality')->heading('Modalidad')->formatStateUsing(fn($state) => match ($state) {
                                        'virtual' => 'Virtual',
                                        'in_person' => 'Presencial',
                                        default => $state,
                                    }),
                                    Column::make('objective')->heading('Objetivo o Descripción'),

                                    // === ARCHIVOS ===
                                    Column::make('ppt_file_path')->heading('Tiene PPT')->formatStateUsing(fn($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('promotional_file_path')->heading('Tiene Pieza de Divulgación')->formatStateUsing(fn($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('recording_link')->heading('Link de Grabación')->formatStateUsing(fn($state) => !empty($state) ? $state : 'No disponible'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),
                ]),
            ])
            // Modificar query para incluir registros eliminados cuando sea necesario
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
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

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Si no es admin, filtrar solo sus registros
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'edit' => Pages\EditTraining::route('/{record}/edit'),
        ];
    }


}
