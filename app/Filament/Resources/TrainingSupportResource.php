<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingSupportResource\Pages;
use App\Models\TrainingSupport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TrainingSupportResource extends Resource
{
    protected static ?string $model = TrainingSupport::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?string $navigationGroup = 'Capacitaciones';

    protected static ?string $modelLabel = 'Carga de Soporte';

    protected static ?string $pluralModelLabel = 'Carga de Soportes';

    protected static ?int $navigationSort = 3;

    // Método helper para verificar permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('listTrainingSupports');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('createTrainingSupport');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('editTrainingSupport');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('deleteTrainingSupport');
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
                Forms\Components\Section::make('Selección de Capacitación')
                    ->description('Selecciona la capacitación para cargar las evidencias y soportes')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\Select::make('training_id')
                            ->label('Seleccionar Capacitación')
                            ->relationship('training', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->placeholder('Buscar capacitación')
                            ->helperText('Capacitación a la que se cargarán los soportes')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' - '.$record->city->name.' ('.$record->training_date->format('d/m/Y').')')
                            ->unique(
                                table: 'training_supports',
                                column: 'training_id',
                                ignoreRecord: true
                            )
                            ->validationMessages([
                                'unique' => 'Esta capacitación ya tiene un soporte registrado.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('training_modality')
                                    ->label('Modalidad')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $training = \App\Models\Training::find($trainingId);
                                        return match ($training?->modality) {
                                            'virtual'   => '🌐 Virtual',
                                            'in_person' => '🏢 Presencial',
                                            'hybrid'    => '🔀 Híbrida',
                                            default     => 'Sin modalidad',
                                        };
                                    }),

                                Forms\Components\Placeholder::make('training_route')
                                    ->label('Ruta')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $training = \App\Models\Training::find($trainingId);
                                        return match ($training?->route) {
                                            'route_1' => 'Ruta 1: Pre-emprendimiento',
                                            'route_2' => 'Ruta 2: Consolidación',
                                            'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                            default   => $training?->route ?? '----',
                                        };
                                    }),

                                Forms\Components\Placeholder::make('training_city')
                                    ->label('Municipio')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $training = \App\Models\Training::with('city')->find($trainingId);
                                        return $training?->city?->name ?? 'Sin municipio';
                                    }),

                                Forms\Components\Placeholder::make('training_date')
                                    ->label('Fecha')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $training = \App\Models\Training::find($trainingId);
                                        return $training?->training_date?->format('d/m/Y') ?? 'Sin fecha';
                                    }),

                                Forms\Components\Placeholder::make('training_organizer')
                                    ->label('Capacitador')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $training = \App\Models\Training::find($trainingId);
                                        $name   = $training?->organizer_name;
                                        $entity = $training?->organizer_entity;
                                        if (! $name) return 'Sin capacitador';
                                        return $entity ? "{$name} – {$entity}" : $name;
                                    })
                                    ->columnSpan(2),

                                Forms\Components\Placeholder::make('session_enabled')
                                    ->label('Participantes habilitados')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $session = \App\Models\TrainingSession::where('training_id', $trainingId)->first();
                                        if (! $session) return 'Sin sesión registrada';
                                        return $session->participations()->count();
                                    }),

                                Forms\Components\Placeholder::make('session_attended')
                                    ->label('Asistentes registrados')
                                    ->content(function ($get) {
                                        $trainingId = $get('training_id');
                                        if (! $trainingId) return '----';
                                        $session = \App\Models\TrainingSession::where('training_id', $trainingId)->first();
                                        if (! $session) return 'Sin sesión registrada';
                                        return $session->participations()->where('attended', true)->count();
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ── PRESENCIAL + HÍBRIDA ────────────────────────────────────────
                Forms\Components\Section::make('Lista de Asistencia Firmada')
                    ->description('Obligatorio para capacitaciones presenciales e híbridas')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Forms\Components\FileUpload::make('attendance_list_path')
                            ->label('Lista de Asistencia Firmada *')
                            ->directory('training-supports/attendance')
                            ->disk('public')
                            ->required(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['in_person', 'hybrid']))
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->helperText('PDF o imagen legible — máximo 10MB')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['in_person', 'hybrid']))
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Registro Fotográfico')
                    ->description('Mínimo 2 fotografías obligatorias para presenciales e híbridas')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Forms\Components\FileUpload::make('photos')
                            ->label('Fotografías de la actividad *')
                            ->directory('training-supports/photos')
                            ->disk('public')
                            ->multiple()
                            ->minFiles(2)
                            ->maxFiles(20)
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->image()
                            ->imagePreviewHeight('120')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->helperText('Mínimo 2 fotos — máximo 5MB por imagen')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['in_person', 'hybrid']))
                    ->collapsible()
                    ->persistCollapsed(),

                // ── VIRTUAL + HÍBRIDA ────────────────────────────────────────────
                Forms\Components\Section::make('Evidencias Virtuales')
                    ->description('Obligatorio para capacitaciones virtuales e híbridas')
                    ->icon('heroicon-o-video-camera')
                    ->schema([
                        Forms\Components\FileUpload::make('connection_evidence_path')
                            ->label('Evidencia de participantes conectados *')
                            ->directory('training-supports/virtual')
                            ->disk('public')
                            ->required(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['virtual', 'hybrid']))
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg', 'image/png', 'image/jpg',
                            ])
                            ->helperText('Captura de participantes, reporte de Meet/Zoom/Teams u equivalente — PDF, Excel o imagen')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('visual_evidence_path')
                            ->label('Evidencia visual de la capacitación *')
                            ->directory('training-supports/virtual-visual')
                            ->disk('public')
                            ->required(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['virtual', 'hybrid']))
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->helperText('Captura(s) de pantalla de la sesión — imagen o PDF')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('recording_file_path')
                            ->label('Archivo de grabación')
                            ->directory('training-supports/recordings')
                            ->disk('public')
                            ->maxSize(512000)
                            ->downloadable()
                            ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/webm', 'application/zip'])
                            ->helperText('Archivo de video o ZIP — opcional, máximo 500MB')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('recording_link')
                            ->label('Link de grabación')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://meet.google.com/... o https://zoom.us/rec/...')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('URL de la grabación (opcional)')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => in_array(\App\Models\Training::find($get('training_id'))?->modality, ['virtual', 'hybrid']))
                    ->collapsible()
                    ->persistCollapsed(),

                // ── COMPLEMENTARIOS (todas las modalidades) ──────────────────────
                Forms\Components\Section::make('Material y Documentos Complementarios')
                    ->description('Archivos adicionales opcionales')
                    ->icon('heroicon-o-folder-open')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\FileUpload::make('material_path')
                                ->label('Material entregado')
                                ->directory('training-supports/materials')
                                ->disk('public')
                                ->maxSize(20480)
                                ->downloadable()
                                ->openable()
                                ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->helperText('PDF, PPT, Excel o ZIP — máximo 20MB'),

                            Forms\Components\FileUpload::make('additional_documents_path')
                                ->label('Documentos complementarios')
                                ->directory('training-supports/documents')
                                ->disk('public')
                                ->maxSize(20480)
                                ->downloadable()
                                ->openable()
                                ->acceptedFileTypes(['application/pdf', 'application/zip', 'image/jpeg', 'image/png'])
                                ->helperText('PDF, imagen o ZIP — máximo 20MB'),
                        ]),
                    ])
                    ->visible(fn ($get) => (bool) $get('training_id'))
                    ->collapsible()
                    ->persistCollapsed()
                    ->collapsed(),

                Forms\Components\Section::make('Observaciones')
                    ->description('Notas o aclaraciones adicionales (opcional)')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->rows(4)
                            ->placeholder('Escribe observaciones, aclaraciones o comentarios sobre la capacitación...')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => (bool) $get('training_id'))
                    ->collapsible()
                    ->persistCollapsed()
                    ->collapsed(),

                // Campo oculto para manager_id (se llena automáticamente)
                Forms\Components\Hidden::make('manager_id')
                    ->default(auth()->id()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                return $query->withTrashed();
            })
            ->columns([
                Tables\Columns\TextColumn::make('training.name')
                    ->label('Capacitación')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('training.modality')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'virtual' => 'success',
                        'in_person' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'virtual' => 'Virtual',
                        'in_person' => 'Presencial',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('training.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin municipio'),

                Tables\Columns\TextColumn::make('training.training_date')
                    ->label('Fecha Capacitación')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Registrado por')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin gestor'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('modality')
                    ->label('Modalidad')
                    ->options([
                        'virtual' => 'Virtual',
                        'in_person' => 'Presencial',
                        'hybrid' => 'Híbrida',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas('training',
                                fn (Builder $sq) => $sq->where('modality', $value)
                            )
                        );
                    }),

                Tables\Filters\SelectFilter::make('route')
                    ->label('Ruta de Capacitación')
                    ->options([
                        'route_1' => 'Ruta 1: Pre-emprendimiento',
                        'route_2' => 'Ruta 2: Consolidación',
                        'route_3' => 'Ruta 3: Escalamiento',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q, $value) => $q->whereHas('training',
                                fn (Builder $sq) => $sq->where('route', $value)
                            )
                        );
                    }),

                Tables\Filters\SelectFilter::make('training.city_id')
                    ->label('Municipio')
                    ->relationship(
                        'training.city',
                        'name',
                        fn (Builder $query) => $query
                            ->where('status', true)  // Solo ciudades activas
                            ->whereHas('department', fn (Builder $q) => $q->where('status', true))  // Solo de departamentos activos
                            ->orderBy('name', 'asc')
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->visible(fn () => static::userCanList()),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar soporte')
                    ->visible(
                        fn ($record) => ! $record->trashed() &&
                            static::userCanEdit() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(
                        fn ($record) => ! $record->trashed() &&
                            static::userCanDelete() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar soporte')
                    ->visible(fn ($record) => $record->trashed() && static::userCanDelete()),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Eliminar permanentemente')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar permanentemente?')
                    ->modalDescription('Esta acción NO se puede deshacer y eliminará todos los archivos.')
                    ->visible(fn () => auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        ExcelExport::make()
                            ->withFilename(fn () => 'soportes-capacitaciones-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'training.city',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                Column::make('training.name')->heading('Nombre de la Capacitación'),
                                Column::make('training.city.name')->heading('Municipio'),
                                Column::make('training.training_date')->heading('Fecha y Hora')->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i')),
                                Column::make('training.route')->heading('Ruta')->formatStateUsing(fn ($state) => match ($state) {
                                    'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                    'route_2' => 'Ruta 2: Consolidación',
                                    'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                    default => $state,
                                }),
                                Column::make('training.modality')->heading('Modalidad')->formatStateUsing(fn ($state) => match ($state) {
                                    'virtual' => 'Virtual',
                                    'in_person' => 'Presencial',
                                    default => $state,
                                }),
                                Column::make('training.organizer_name')->heading('Organizador'),

                                // === EVIDENCIAS CARGADAS ===
                                Column::make('attendance_list_path')->heading('Tiene Lista de Asistencia')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('recording_link')->heading('Link de Grabación')->formatStateUsing(fn ($state) => ! empty($state) ? $state : 'No disponible'),
                                Column::make('georeference_photo_path')->heading('Tiene Foto Georeferenciación')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('additional_photo_1_path')->heading('Foto Adicional 1')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('additional_photo_2_path')->heading('Foto Adicional 2')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('additional_photo_3_path')->heading('Foto Adicional 3')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),

                                // === OBSERVACIONES ===
                                Column::make('observations')->heading('Observaciones'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
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
                                ->withFilename(fn () => 'soportes-capacitaciones-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with([
                                    'training.city',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                    Column::make('training.name')->heading('Nombre de la Capacitación'),
                                    Column::make('training.city.name')->heading('Municipio'),
                                    Column::make('training.training_date')->heading('Fecha y Hora')->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i')),
                                    Column::make('training.route')->heading('Ruta')->formatStateUsing(fn ($state) => match ($state) {
                                        'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                        'route_2' => 'Ruta 2: Consolidación',
                                        'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                        default => $state,
                                    }),
                                    Column::make('training.modality')->heading('Modalidad')->formatStateUsing(fn ($state) => match ($state) {
                                        'virtual' => 'Virtual',
                                        'in_person' => 'Presencial',
                                        default => $state,
                                    }),
                                    Column::make('training.organizer_name')->heading('Organizador'),

                                    // === EVIDENCIAS CARGADAS ===
                                    Column::make('attendance_list_path')->heading('Tiene Lista de Asistencia')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('recording_link')->heading('Link de Grabación')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No disponible'),
                                    Column::make('georeference_photo_path')->heading('Tiene Foto Georeferenciación')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('additional_photo_1_path')->heading('Foto Adicional 1')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('additional_photo_2_path')->heading('Foto Adicional 2')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('additional_photo_3_path')->heading('Foto Adicional 3')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),

                                    // === OBSERVACIONES ===
                                    Column::make('observations')->heading('Observaciones'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Admin')),
                ]),
            ])
            // Modificar query para incluir registros eliminados cuando sea necesario
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
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
            'index' => Pages\ListTrainingSupports::route('/'),
            'create' => Pages\CreateTrainingSupport::route('/create'),
            'edit' => Pages\EditTrainingSupport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Ajusta según tu sistema de roles
        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Si no es admin, filtrar solo sus registros
        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
