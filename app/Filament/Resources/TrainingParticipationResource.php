<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingParticipationResource\Pages;
use App\Models\TrainingParticipation;
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

class TrainingParticipationResource extends Resource
{
    protected static ?string $model = TrainingParticipation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $modelLabel = 'Participación';
    protected static ?string $pluralModelLabel = 'Participaciones';
    protected static ?int $navigationSort = 2;

    // Método helper para verificar permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listTrainingParticipations');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createTrainingParticipation');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editTrainingParticipation');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteTrainingParticipation');
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
                Forms\Components\Section::make('Registro de Participación en Capacitación')
                    ->description('Registra la participación de un emprendedor en una capacitación')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('training_id')
                                    ->label('Seleccionar Capacitación')
                                    ->relationship('training', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->placeholder('Buscar capacitación')
                                    ->helperText('Capacitación en la que participará')
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('entrepreneur_id')
                                    ->label('Emprendedor')
                                    ->relationship(
                                        'entrepreneur',
                                        'full_name',
                                        fn($query) => $query->when(
                                            !auth()->user()->hasRole('Admin'),
                                            fn($q) => $q->where('manager_id', auth()->id())
                                        )
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull()
                                    ->reactive()
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->helperText(
                                        fn(string $operation): string =>
                                        $operation === 'edit'
                                            ? 'El emprendedor no puede ser modificado después de crear la participación.'
                                            : 'Selecciona el emprendedor que participará en la capacitación.'
                                    )
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        return $record->full_name ?? $record->email ?? 'Emprendedor #' . $record->id;
                                    }),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('business_name')
                                    ->label('Emprendimiento')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('business')->find($entrepreneurId);
                                        return $entrepreneur?->business?->business_name ?? 'Sin emprendimiento';
                                    }),

                                Forms\Components\Placeholder::make('city_name')
                                    ->label('Municipio')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);
                                        return $entrepreneur?->city?->name ?? 'Sin ubicación';
                                    }),

                                Forms\Components\Placeholder::make('manager_name')
                                    ->label('Gestor')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('manager')->find($entrepreneurId);
                                        return $entrepreneur?->manager?->name ?? 'Sin gestor asignado';
                                    }),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Registro de Asistencia')
                    ->description('Indica si el emprendedor asistió a la capacitación')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Forms\Components\Select::make('attended')
                            ->label('¿Asistió a la Capacitación?')
                            ->options([
                                1 => 'Sí',
                                0 => 'No',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->placeholder('Seleccione si asistió o no')
                            ->helperText('Indica si el emprendedor asistió a la capacitación'),

                        Forms\Components\Textarea::make('non_attendance_reason')
                            ->label('Motivo de la No Asistencia')
                            ->required(fn($get) => $get('attended') === 0 || $get('attended') === '0')
                            ->visible(fn($get) => $get('attended') === 0 || $get('attended') === '0')
                            ->rows(4)
                            ->placeholder('Especifica el motivo por el cual no asistió...')
                            ->validationMessages([
                                'required' => 'Debes especificar el motivo de la no asistencia.',
                            ]),
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
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                return $query->withTrashed();
            })
            ->columns([
                Tables\Columns\TextColumn::make('training.name')
                    ->label('Capacitación')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('entrepreneur.full_name')
                    ->label('Emprendedor')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sin emprendedor'),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sin emprendimiento')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('training.modality')
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
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('training.training_date')
                    ->label('Fecha Capacitación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Registrado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('route')
                    ->label('Ruta de Capacitación')
                    ->options([
                        'route_1' => 'Ruta 1: Pre-emprendimiento',
                        'route_2' => 'Ruta 2: Consolidación',
                        'route_3' => 'Ruta 3: Escalamiento',
                    ])
                    ->query(
                        fn(Builder $query, $data) =>
                        $query->when(
                            $data['value'],
                            fn($q, $route) =>
                            $q->whereHas('training', fn($q) => $q->where('route', $route))
                        )
                    ),

                Tables\Filters\SelectFilter::make('modality')
                    ->label('Modalidad')
                    ->options([
                        'virtual' => 'Virtual',
                        'in_person' => 'Presencial',
                    ])
                    ->query(
                        fn(Builder $query, $data) =>
                        $query->when(
                            $data['value'],
                            fn($q, $modality) =>
                            $q->whereHas('training', fn($q) => $q->where('modality', $modality))
                        )
                    ),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio del Emprendedor')
                    ->relationship('entrepreneur.city', 'name')
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
                    ->tooltip('Editar participación')
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
                    ->tooltip('Restaurar participación')
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
                            ->withFilename(fn() => 'participaciones-capacitaciones-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'training',
                                'entrepreneur.business',
                                'entrepreneur.city',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN DE LA PARTICIPACIÓN ===
                                Column::make('training.name')->heading('Capacitación'),
                                Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                Column::make('entrepreneur.city.name')->heading('Municipio'),

                                // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                Column::make('training.route')->heading('Ruta')->formatStateUsing(fn($state) => match ($state) {
                                    'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                    'route_2' => 'Ruta 2: Consolidación',
                                    'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                    default => $state,
                                }),
                                Column::make('training.modality')->heading('Modalidad')->formatStateUsing(fn($state) => match ($state) {
                                    'virtual' => 'Virtual',
                                    'in_person' => 'Presencial',
                                    default => $state,
                                }),
                                Column::make('training.training_date')->heading('Fecha Capacitación')->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),

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
                                ->withFilename(fn() => 'participaciones-capacitaciones-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'training',
                                    'entrepreneur.business',
                                    'entrepreneur.city',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN DE LA PARTICIPACIÓN ===
                                    Column::make('training.name')->heading('Capacitación'),
                                    Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                    Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                    Column::make('entrepreneur.city.name')->heading('Municipio'),

                                    // === INFORMACIÓN DE LA CAPACITACIÓN ===
                                    Column::make('training.route')->heading('Ruta')->formatStateUsing(fn($state) => match ($state) {
                                        'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
                                        'route_2' => 'Ruta 2: Consolidación',
                                        'route_3' => 'Ruta 3: Escalamiento e Innovación',
                                        default => $state,
                                    }),
                                    Column::make('training.modality')->heading('Modalidad')->formatStateUsing(fn($state) => match ($state) {
                                        'virtual' => 'Virtual',
                                        'in_person' => 'Presencial',
                                        default => $state,
                                    }),
                                    Column::make('training.training_date')->heading('Fecha Capacitación')->formatStateUsing(fn($state) => $state?->format('d/m/Y H:i')),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::userCanDelete()),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => static::userCanDelete()),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    public static function getEloquentQuery(): Builder
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
            'index' => Pages\ListTrainingParticipations::route('/'),
            'create' => Pages\CreateTrainingParticipation::route('/create'),
            'edit' => Pages\EditTrainingParticipation::route('/{record}/edit'),
        ];
    }
}
