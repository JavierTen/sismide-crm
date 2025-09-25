<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Parametros';

    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?int $navigationSort = 1;


    // Método helper para verificar permisos
    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listUsers');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createUser');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editUser');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteUser');
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
                Forms\Components\Section::make('Información Personal')
                    ->description('Datos básicos del usuario')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Juan Pérez'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('usuario@empresa.com'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                    Forms\Components\Section::make('Seguridad y Acceso')
                    ->description('Configuración de contraseña y verificación')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->placeholder(fn(string $context): string =>
                                $context === 'edit'
                                    ? 'Dejar vacío para mantener la contraseña actual'
                                    : 'Mínimo 8 caracteres'
                            )
                            ->helperText(fn(string $context): string =>
                                $context === 'edit'
                                    ? 'Solo completar si desea cambiar la contraseña actual'
                                    : 'Debe contener al menos 8 caracteres'
                            ),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Roles y Permisos')
                    ->description('Control de acceso y funcionalidades disponibles')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles del Usuario')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->placeholder('Seleccione los roles apropiados')
                            ->helperText('Los roles determinan las funcionalidades disponibles para el usuario')
                            ->options(function () {
                                return \Spatie\Permission\Models\Role::pluck('name', 'id');
                            }),
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
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(), // Filtro para ver eliminados
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
                    ->tooltip('Editar usuario')
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
                    ->tooltip('Restaurar usuario')
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
