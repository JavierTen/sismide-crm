<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas';

    protected static ?int $navigationSort = 2;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listVisits');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createVisit');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editVisit');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteVisit');
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
                Forms\Components\Section::make('Agendamiento de Visitas')
                    ->description('Registra una nueva visita, fecha, hora y tipo. Elige el emprendedor para autocompletar datos relacionados.')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('entrepreneur_id')
                                    ->label('Emprendedor')
                                    ->relationship('entrepreneur', 'full_name') // ajusta el campo mostrable si usas otro
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull()
                                    ->reactive()
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->helperText(fn(string $operation): string =>
                                        $operation === 'edit'
                                            ? 'El emprendedor no puede ser modificado después de crear la visita.'
                                            : 'Selecciona el emprendedor al que se le agendará la visita.'
                                    ),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('business_name')
                                            ->label('Nombre del Emprendimiento')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with('business')->find($get('entrepreneur_id'))
                                                )?->business?->business_name ?? '----'
                                            )
                                            ->reactive(),
                                        Forms\Components\Placeholder::make('city_name')
                                            ->label('Municipio')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with(['city', 'manager', 'business'])
                                                        ->find($get('entrepreneur_id'))
                                                )?->city?->name ?? '----'
                                            )
                                            ->reactive(),
                                        Forms\Components\Placeholder::make('user_name')
                                            ->label('Gestor')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with(['city', 'manager', 'business'])
                                                        ->find($get('entrepreneur_id'))
                                                )?->manager?->name ?? '----'
                                            )
                                            ->reactive(),
                                    ]),


                                Forms\Components\Select::make('visit_type')
                                    ->label('Tipo de visita')
                                    ->required()
                                    ->options([
                                        'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                        'caracterizacion'    => 'Visita de Caracterización',
                                        'diagnostico'        => 'Visita levantamiento de Diagnóstico',
                                        'seguimiento'        => 'Visita de Seguimiento',
                                    ])
                                    ->placeholder('Seleccione el tipo de visita')
                                    ->columnSpanFull()
                                    ->helperText('Elige el propósito principal de la visita.'),

                                Forms\Components\DatePicker::make('visit_date')
                                    ->label('Fecha de la visita')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Selecciona la fecha programada.'),

                                Forms\Components\TimePicker::make('visit_time')
                                    ->label('Hora de la visita')
                                    ->required()
                                    ->placeholder('HH:MM')
                                    ->helperText('Hora prevista para la visita.'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Resultado y Reagendamiento')
                    ->description('Indica si la visita fortaleció al emprendedor y maneja reagendamientos.')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('strengthened')
                                    ->label('Se ha fortalecido')
                                    ->helperText('Indica si la visita logró fortalecer al emprendedor.')
                                    ->default(false)
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Toggle::make('rescheduled')
                                    ->label('Reagendamiento')
                                    ->helperText('Marcar si la visita debe ser reagendada (ej. por ausencia del emprendedor).')
                                    ->default(false)
                                    ->reactive() // <- importante: permite que los campos dependientes se actualicen
                                    ->inline(false)
                                    ->hiddenOn('create')
                                    ->onIcon('heroicon-m-arrow-path')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Si se desmarca reagendamiento, limpiar el motivo
                                        if (! $state) {
                                            $set('reschedule_reason', null);
                                        }
                                    }),
                            ]),

                        // Motivo solo visible y obligatorio si rescheduled = true
                        Forms\Components\Textarea::make('reschedule_reason')
                            ->label('Motivo de Reagendamiento')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn($get) => (bool) $get('rescheduled'))
                            ->required(fn($get) => (bool) $get('rescheduled'))
                            ->placeholder('Especifique el motivo por el cual se reagenda la visita (por ejemplo: emprendedor ausente, clima, etc.)'),

                        Forms\Components\Select::make('new_visit_type')
                            ->label('Tipo de visita (reagendada)')
                            ->options([
                                'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                'caracterizacion'    => 'Visita de Caracterización',
                                'diagnostico'        => 'Visita levantamiento de Diagnóstico',
                                'seguimiento'        => 'Visita de Seguimiento',
                            ])
                            ->visible(fn($get) => (bool) $get('rescheduled'))
                            ->required(fn($get) => (bool) $get('rescheduled')),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('new_visit_date')
                                    ->label('Nueva Fecha (reagendada)')
                                    ->displayFormat('d/m/Y')
                                    ->visible(fn($get) => (bool) $get('rescheduled'))
                                    ->required(fn($get) => (bool) $get('rescheduled')),

                                Forms\Components\TimePicker::make('new_visit_time')
                                    ->label('Nueva Hora (reagendada)')
                                    ->visible(fn($get) => (bool) $get('rescheduled'))
                                    ->required(fn($get) => (bool) $get('rescheduled')),
                            ]),




                    ])
                    ->collapsible()
                    ->persistCollapsed(),


            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entrepreneur.full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin ubicación'),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Fecha visita')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),

                Tables\Columns\TextColumn::make('visit_time')
                    ->label('Hora visita')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
                    ->tooltip('Editar visita')
                    ->visible(fn($record) => !$record->trashed() && static::userCanEdit()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(fn($record) => !$record->trashed() && static::userCanDelete()),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar visita')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::userCanDelete()),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn() => static::userCanDelete()),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),
                ]),
            ])
            // Modificar query para incluir registros eliminados cuando sea necesario
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
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
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
