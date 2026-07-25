<?php

namespace App\Filament\Resources\ActorResource\RelationManagers;

use App\Models\EntityContact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EntityContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Actores / Contactos';

    protected static ?string $modelLabel = 'Contacto';
    protected static ?string $pluralModelLabel = 'Contactos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Contacto')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nombre y apellidos')
                            ->rule('regex:/^[\pL\s]+$/u')
                            ->validationMessages([
                                'regex' => 'El nombre solo puede contener letras y espacios.',
                            ]),

                        Forms\Components\TextInput::make('role')
                            ->label('Rol / Cargo')
                            ->maxLength(255)
                            ->placeholder('Cargo en la institución'),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('contacto@ejemplo.com'),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono / Celular')
                            ->tel()
                            ->maxLength(50)
                            ->placeholder('+57 300 123 4567'),

                        Forms\Components\TextInput::make('area')
                            ->label('Área / Dependencia')
                            ->maxLength(255)
                            ->placeholder('Ej: Dirección de Emprendimiento'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Clasificación')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Select::make('contact_type')
                            ->label('Tipo de Contacto')
                            ->options(EntityContact::CONTACT_TYPE_OPTIONS)
                            ->placeholder('Seleccione el tipo')
                            ->native(false),

                        Forms\Components\Select::make('decision_level')
                            ->label('Nivel de Decisión')
                            ->options(EntityContact::DECISION_LEVEL_OPTIONS)
                            ->placeholder('Seleccione el nivel')
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(EntityContact::STATUS_OPTIONS)
                            ->default('active')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_primary_contact')
                            ->label('¿Es el contacto principal?')
                            ->helperText('Solo puede haber un contacto principal activo por entidad')
                            ->default(false)
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('gray'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Observaciones')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->placeholder('Información adicional relevante sobre este contacto')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Cargo')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->default('—'),

                Tables\Columns\TextColumn::make('contact_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => EntityContact::CONTACT_TYPE_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'directive'     => 'success',
                        'institutional' => 'info',
                        'commercial'    => 'warning',
                        'financial'     => 'danger',
                        'technical'     => 'info',
                        'formative'     => 'success',
                        'logistic'      => 'warning',
                        default         => 'gray',
                    })
                    ->default('—'),

                Tables\Columns\TextColumn::make('decision_level')
                    ->label('Nivel')
                    ->formatStateUsing(fn($state) => EntityContact::DECISION_LEVEL_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'decision_maker' => 'danger',
                        'influencer'     => 'warning',
                        'operational'    => 'gray',
                        default          => 'gray',
                    })
                    ->default('—'),

                Tables\Columns\IconColumn::make('is_primary_contact')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => EntityContact::STATUS_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active'          => 'success',
                        'pending_contact' => 'warning',
                        'no_response'     => 'danger',
                        'withdrawn'       => 'gray',
                        'inactive'        => 'gray',
                        default           => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar'),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar'),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Eliminar permanentemente')
                    ->requiresConfirmation()
                    ->visible(fn() => auth()->user()->hasRole('Admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),
                ]),
            ])
            ->defaultSort('is_primary_contact', 'desc')
            ->modifyQueryUsing(fn($query) => $query->withTrashed());
    }
}
