<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrepreneurshipStageResource\Pages;
use App\Filament\Resources\EntrepreneurshipStageResource\RelationManagers;
use App\Models\EntrepreneurshipStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EntrepreneurshipStageResource extends Resource
{
    protected static ?string $model = EntrepreneurshipStage::class;

    protected static ?string $navigationGroup = 'Gestión Tipos';

    protected static ?string $modelLabel = 'Etapa de Emprendimiento';
    protected static ?string $pluralModelLabel = 'Etapas de Emprendimiento';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 7;


    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listEntrepreneurshipStageResources');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createEntrepreneurshipStageResource');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editEntrepreneurshipStageResource');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteEntrepreneurshipStageResource');
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
                Forms\Components\Section::make('Información de la Etapa de Emprendimiento')
                    ->description('Configuración básica de la etapa de emprendimiento')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('Ej: NIF, NCF')
                                    ->helperText('Código único de identificación (máximo 10 caracteres)')
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash(),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de La Etapa')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ej: Negocio Individual en Funcionamiento')
                                    ->helperText('Nombre completo de la etapa'),
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
                            ->helperText('Determina si esta etapa está disponible para uso')
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
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
                    ->tooltip('Editar etapa de emprendimiento')
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
                    ->tooltip('Restaurar etapa de emprendimiento')
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
            'index' => Pages\ListEntrepreneurshipStages::route('/'),
            'create' => Pages\CreateEntrepreneurshipStage::route('/create'),
            'edit' => Pages\EditEntrepreneurshipStage::route('/{record}/edit'),
        ];
    }
}
