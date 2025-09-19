<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PopulationResource\Pages;
use App\Filament\Resources\PopulationResource\RelationManagers;
use App\Models\Population;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PopulationResource extends Resource
{
    protected static ?string $model = Population::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';
    protected static ?string $navigationGroup = 'Gestión Tipos';

    protected static ?string $modelLabel = 'Población';
    protected static ?string $pluralModelLabel = 'Poblaciones vulnerables';

    protected static ?int $navigationSort = 6;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listPopulations');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createPopulation');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editPopulation');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deletePopulation');
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
                Forms\Components\Section::make('Información de la población')
                    ->description('Configuración básica de la población')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la población')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ej: Pueblo, Ciudad, Estado')
                                    ->helperText('Nombre completo de la población'),
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
                            ->helperText('Determina si este la población está disponible para uso')
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
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
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
                    ->tooltip('Editar población')
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
                    ->tooltip('Restaurar población')
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
            'index' => Pages\ListPopulations::route('/'),
            'create' => Pages\CreatePopulation::route('/create'),
            'edit' => Pages\EditPopulation::route('/{record}/edit'),
        ];
    }
}
