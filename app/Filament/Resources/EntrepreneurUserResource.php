<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrepreneurUserResource\Pages;
use App\Filament\Resources\EntrepreneurUserResource\RelationManagers;
use App\Models\EntrepreneurUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EntrepreneurUserResource extends Resource
{
    protected static ?string $model = \App\Models\Entrepreneur::class;

    protected static ?string $navigationLabel = 'Usuarios emprendedores';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Parametros';

    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios emprendedores';

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listUsersEntrepreneurs');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createUserEntrepreneurs');
    }

    private static function UserResendPassword(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('ResendPassUserEntrepreneurs');
    }


    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteUserEntrepreneurs');
    }

    public static function canViewAny(): bool
    {
        return static::userCanList();
    }

    public static function canCreate(): bool
    {
        return static::userCanCreate();
    }

    public static function canDelete($record): bool
    {
        return static::userCanDelete();
    }

    public static function canResendPassword($record): bool
    {
        return static::UserResendPassword();
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
                Forms\Components\Section::make('Información del Emprendedor')
                    ->description('Selecciona el emprendedor para crear sus credenciales de acceso')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\Select::make('entrepreneur_id')
                            ->label('Emprendedor')
                            ->options(function () {
                                $query = \App\Models\Entrepreneur::query()
                                    ->whereNull('password') // Solo emprendedores sin usuario
                                    ->whereNotNull('full_name'); // IMPORTANTE: Excluir emprendedores sin nombre

                                // Filtrar por manager si no es Admin
                                if (!auth()->user()->hasRole('Admin')) {
                                    $query->where('manager_id', auth()->id());
                                }

                                return $query->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->placeholder('Buscar emprendedor sin credenciales...')
                            ->helperText('Solo se muestran emprendedores que aún no tienen usuario creado'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('email_display')
                                    ->label('Correo Electrónico')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::find($entrepreneurId);
                                        return $entrepreneur?->email ?? 'Sin correo registrado';
                                    }),

                                Forms\Components\Placeholder::make('document_display')
                                    ->label('Documento')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('documentType')->find($entrepreneurId);
                                        return $entrepreneur ?
                                            ($entrepreneur->documentType->code ?? 'N/A') . ' ' . $entrepreneur->document_number
                                            : '----';
                                    }),

                                Forms\Components\Placeholder::make('phone_display')
                                    ->label('Teléfono')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::find($entrepreneurId);
                                        return $entrepreneur?->phone ?? 'Sin teléfono';
                                    }),

                                Forms\Components\Placeholder::make('city_display')
                                    ->label('Municipio')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);
                                        return $entrepreneur?->city?->name ?? 'Sin municipio';
                                    }),
                            ]),

                        Forms\Components\Placeholder::make('warning')
                            ->label('⚠️ Importante')
                            ->content('Al crear el usuario, se generará una contraseña aleatoria que será enviada al correo del emprendedor.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Solo mostrar emprendedores CON usuario creado
                $query->whereNotNull('password');

                // Filtrar según rol
                if (!auth()->user()->hasRole('Admin')) {
                    $query->where('manager_id', auth()->id());
                }
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\IconColumn::make('status')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn($record): string => $record->status ? 'Activo' : 'Inactivo'),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        true => 'Activo',
                        false => 'Inactivo',
                    ]),

                Tables\Filters\SelectFilter::make('manager_id')
                    ->label('Gestor')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => auth()->user()->hasRole('Admin')),
            ])
            ->actions([
                Tables\Actions\Action::make('resend_credentials')
                    ->label('')
                    ->icon('heroicon-o-paper-airplane')
                    ->tooltip('Reenviar Credenciales')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar Credenciales')
                    ->modalDescription(fn($record) => "Se generará una nueva contraseña y se enviará a {$record->email}")
                    ->modalSubmitActionLabel('Sí, reenviar')
                    ->action(function ($record) {
                        try {
                            // Validar email
                            if (empty($record->email)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('El emprendedor no tiene correo electrónico.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Generar nueva contraseña
                            $password = Str::random(8);

                            // Actualizar contraseña
                            $record->update([
                                'password' => Hash::make($password),
                            ]);

                            // Reenviar credenciales
                            $record->notify(new \App\Notifications\EntrepreneurCredentialsNotification(
                                $record->email,
                                $password,
                                $record->full_name
                            ));

                            Notification::make()
                                ->title('¡Credenciales reenviadas!')
                                ->body("Nueva contraseña enviada a {$record->email}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al reenviar')
                                ->body('Ocurrió un error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => static::canResendPassword($record)),

                Tables\Actions\Action::make('delete_credentials')
                    ->label('')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->tooltip('Eliminar Acceso')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Credenciales de Acceso')
                    ->modalDescription(fn($record) => "Se eliminarán las credenciales de {$record->full_name}. El emprendedor ya NO podrá acceder a la plataforma.")
                    ->modalSubmitActionLabel('Sí, eliminar acceso')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->action(function ($record) {
                        try {
                            // Eliminar contraseña (establecer como null)
                            $record->update([
                                'password' => null,
                            ]);

                            Notification::make()
                                ->title('¡Acceso eliminado!')
                                ->body("Las credenciales de {$record->full_name} han sido eliminadas. Ya no podrá acceder a la plataforma.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al eliminar acceso')
                                ->body('Ocurrió un error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resend_bulk')
                        ->label('Reenviar Credenciales')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reenviar Credenciales Masivamente')
                        ->modalDescription('Se generarán nuevas contraseñas para todos los emprendedores seleccionados.')
                        ->action(function ($records) {
                            $sent = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                if (!empty($record->email)) {
                                    try {
                                        $password = Str::random(8);

                                        $record->update([
                                            'password' => Hash::make($password),
                                        ]);

                                        $record->notify(new \App\Notifications\EntrepreneurCredentialsNotification(
                                            $record->email,
                                            $password,
                                            $record->full_name
                                        ));

                                        $sent++;
                                    } catch (\Exception $e) {
                                        $errors++;
                                    }
                                } else {
                                    $errors++;
                                }
                            }

                            if ($sent > 0) {
                                Notification::make()
                                    ->title("¡{$sent} credencial(es) reenviada(s)!")
                                    ->body($errors > 0 ? "Errores: {$errors}" : 'Todos los correos fueron enviados.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se enviaron credenciales')
                                    ->body('Verifica que los emprendedores tengan correo electrónico.')
                                    ->warning()
                                    ->send();
                            }
                        }),

                    Tables\Actions\BulkAction::make('delete_credentials_bulk')
                        ->label('Eliminar Acceso Masivo')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Credenciales Masivamente')
                        ->modalDescription('Se eliminarán las credenciales de todos los emprendedores seleccionados. Ya NO podrán acceder a la plataforma.')
                        ->modalSubmitActionLabel('Sí, eliminar acceso')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->action(function ($records) {
                            $deleted = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                try {
                                    $record->update([
                                        'password' => null,
                                    ]);
                                    $deleted++;
                                } catch (\Exception $e) {
                                    $errors++;
                                }
                            }

                            if ($deleted > 0) {
                                Notification::make()
                                    ->title("¡{$deleted} acceso(s) eliminado(s)!")
                                    ->body($errors > 0 ? "Errores: {$errors}" : 'Todas las credenciales fueron eliminadas.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se eliminaron credenciales')
                                    ->body('Ocurrió un error al procesar la solicitud.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn() => auth()->user()->hasRole(['Admin', 'Manager'])),
                ]),
            ]);
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
            'index' => Pages\ListEntrepreneurUsers::route('/'),
            'create' => Pages\CreateEntrepreneurUser::route('/create'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Solo contar emprendedores con contraseña (usuarios creados)
        $query->whereNotNull('password');

        // Si no es admin, filtrar solo sus registros
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
