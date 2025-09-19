<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WardResource\Pages;
use App\Filament\Resources\WardResource\RelationManagers;
use App\Models\Ward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WardResource extends Resource
{
    protected static ?string $model = Ward::class;

    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?string $navigationGroup = 'Gestión Tipos';

    protected static ?string $modelLabel = 'Corregimiento';
    protected static ?string $pluralModelLabel = 'Corregimientos';

    protected static ?int $navigationSort = 14;


    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listWards');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createWard');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editWard');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteWard');
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
                Forms\Components\Section::make('Información del Corregimiento')
                    ->description('Configuración básica del corregimiento')
                    ->icon('heroicon-o-sun')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([


                                Forms\Components\Select::make('department_id')
                                    ->label('Departamento')
                                    ->relationship('city.department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccionar departamento')
                                    ->helperText('Selecciona primero el departamento')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('city_id', null); // Limpiar municipio cuando cambia departamento
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('city_id')
                                    ->label('Municipio')
                                    ->relationship(
                                        name: 'city',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn(Builder $query, Get $get): Builder =>
                                        $query->when(
                                            $get('department_id'),
                                            fn(Builder $query, $departmentId): Builder =>
                                            $query->where('department_id', $departmentId)
                                        )
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload() // No precargar hasta que se seleccione departamento
                                    ->placeholder('Primero selecciona un departamento')
                                    ->helperText('Municipio al que pertenece el corregimiento')
                                    ->disabled(fn(Get $get): bool => ! $get('department_id'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del Corregimiento')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: El Cabrero, Santa Rosa, La Paz')
                                    ->helperText('Nombre completo del corregimiento')
                                    ->columnSpan(2),

                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Configuración')
                    ->description('Estado y configuraciones adicionales')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('Estado Activo')
                            ->helperText('Determina si este corregimiento está disponible para uso')
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Corregimiento')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Estado')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()
                    ->tooltip(fn($record) => $record->status ? 'Activo - Click para desactivar' : 'Inactivo - Click para activar'),
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
                    ->tooltip('Editar corregimiento')
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
                    ->tooltip('Restaurar corregimiento')
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
            'index' => Pages\ListWards::route('/'),
            'create' => Pages\CreateWard::route('/create'),
            'edit' => Pages\EditWard::route('/{record}/edit'),
        ];
    }
}
