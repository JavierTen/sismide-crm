<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrepreneurResource\Pages;
use App\Filament\Resources\EntrepreneurResource\RelationManagers;
use App\Models\Entrepreneur;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EntrepreneurResource extends Resource
{
    protected static ?string $model = Entrepreneur::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Emprendedor';
    protected static ?string $pluralModelLabel = 'Emprendedores';

    protected static ?int $navigationSort = 1;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listEntrepreneurs');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createEntrepreneur');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editEntrepreneur');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteEntrepreneur');
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
                Forms\Components\Tabs::make('Registro Completo')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Emprendedor')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Información Personal')
                                    ->description('Datos básicos del emprendedor')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Forms\Components\Select::make('document_type_id')
                                            ->label('Tipo de Documento')
                                            ->options(function () {
                                                return \App\Models\DocumentType::active()
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->pluck('code_name_combined', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione el tipo de documento'),

                                        Forms\Components\TextInput::make('document_number')
                                            ->label('Número de Documento')
                                            ->maxLength(20)
                                            ->numeric()
                                            ->required()
                                            ->placeholder('Ej: 12345678')
                                            ->unique(ignoreRecord: true),

                                        Forms\Components\TextInput::make('full_name')
                                            ->label('Nombre Completo')
                                            ->maxLength(100)
                                            ->required()
                                            ->placeholder('Nombres y apellidos completos')
                                            ->columnSpanFull()
                                            ->rule('regex:/^[\pL\s]+$/u')
                                            ->validationMessages([
                                                'regex' => 'El nombre solo puede contener letras y espacios.',
                                            ]),

                                        Forms\Components\Select::make('gender_id')
                                            ->label('Género')
                                            ->relationship('gender', 'name', fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione el género'),

                                        Forms\Components\Select::make('marital_status_id')
                                            ->label('Estado Civil')
                                            ->relationship('maritalStatus', 'name', fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Seleccione el estado civil'),

                                        Forms\Components\DatePicker::make('birth_date')
                                            ->label('Fecha de Nacimiento')
                                            ->required()
                                            ->maxDate(now())
                                            ->displayFormat('d/m/Y')
                                            ->native(true),

                                        Forms\Components\Select::make('population_id')
                                            ->label('Población vulnerable')
                                            ->relationship('Population', 'name', fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione una opción'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Información de Contacto')
                                    ->description('Datos de contacto')
                                    ->icon('heroicon-o-phone')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Teléfono')
                                            ->tel()
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Ej: +57 300 123 4567')
                                            ->regex('/^[\+]?[0-9\s\-\(\)]+$/'),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('usuario@ejemplo.com')
                                            ->unique(ignoreRecord: true),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Información Académica')
                                    ->description('Datos educativos')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Forms\Components\Select::make('education_level_id')
                                            ->label('Nivel Educativo')
                                            ->columnSpanFull()
                                            ->relationship('educationLevel', 'name', fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione el nivel educativo'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Estado')
                                    ->description('Estado general del emprendedor')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        Forms\Components\Toggle::make('status')
                                            ->label('Estado Activo')
                                            ->helperText('Determina si el emprendedor está activo en el sistema')
                                            ->default(true)
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check-circle')
                                            ->offIcon('heroicon-m-x-circle')
                                            ->onColor('success')
                                            ->offColor('gray'),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Emprendimiento')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\Section::make('Información General del Emprendimiento')
                                    ->description('Datos básicos del negocio o emprendimiento')
                                    ->icon('heroicon-o-building-storefront')
                                    ->schema([
                                        Forms\Components\TextInput::make('business_name')
                                            ->label('Nombre del Emprendimiento')
                                            ->maxLength(100)
                                            ->required()
                                            ->placeholder('Nombre comercial del negocio'),

                                        Forms\Components\DatePicker::make('creation_date')
                                            ->label('Fecha de creación')
                                            ->required()
                                            ->maxDate(now())
                                            ->displayFormat('d/m/Y')
                                            ->native(true),

                                        Forms\Components\Textarea::make('business_description')
                                            ->label('Descripción del Emprendimiento')
                                            ->placeholder('Describe brevemente el emprendimiento')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('entrepreneurship_stage_id')
                                            ->label('Etapa del Emprendimiento')
                                            ->options(function () {
                                                return \App\Models\EntrepreneurshipStage::active()
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->pluck('code_name_combined', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione una etapa'),

                                        Forms\Components\Select::make('economic_activity_id')
                                            ->label('Actividad Económica')
                                            ->options(function () {
                                                return \App\Models\EconomicActivity::active()
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->placeholder('Seleccione una actividad económica'),

                                        Forms\Components\Select::make('productive_line_id')
                                            ->label('Linea Productiva')
                                            ->options(function () {
                                                return \App\Models\ProductiveLine::active()
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->placeholder('Seleccione una linea productiva'),

                                        Forms\Components\Select::make('code_ciiu')
                                            ->label('Codigo CIUU')
                                            ->options(function () {
                                                return \App\Models\CiiuCode::active()
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->pluck('code_descripcion_combined', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione el tipo de documento'),

                                        Forms\Components\Select::make('marsital_statusS')
                                            ->label('Proyecto')
                                            ->relationship('project', 'name', fn($query) => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull()
                                            ->placeholder('Seleccione un proyecto'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Ubicación y Contacto del Negocio')
                                    ->description('Información de ubicación del emprendimiento')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('department_id')
                                                    ->label('Departamento')
                                                    ->options(function () {
                                                        return \App\Models\Department::active()
                                                            ->orderBy('name')
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('city_id', null);
                                                        $set('ward_id', null);
                                                        $set('village_id', null);
                                                    })
                                                    ->placeholder('Seleccione un departamento'),

                                                Forms\Components\Select::make('city_id')
                                                    ->label('Ciudad')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->options(function (Get $get) {
                                                        $departmentId = $get('department_id');
                                                        if (!$departmentId) {
                                                            return [];
                                                        }
                                                        return \App\Models\City::active()
                                                            ->where('department_id', $departmentId)
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('ward_id', null);
                                                        $set('village_id', null);
                                                    })
                                                    ->placeholder('Seleccione una ciudad'),

                                                Forms\Components\Select::make('ward_id')
                                                    ->label('Corregimiento')
                                                    ->searchable()
                                                    ->live()
                                                    ->visible(function ($get) {
                                                        // Solo mostrar si hay una ciudad seleccionada
                                                        $cityId = $get('city_id');
                                                        if (!$cityId) {
                                                            return false;
                                                        }

                                                        // Verificar si la ciudad tiene corregimientos
                                                        $wardsCount = \App\Models\Ward::where('city_id', $cityId)
                                                            ->active()
                                                            ->count();

                                                        return $wardsCount > 0;
                                                    })
                                                    ->options(function ($get) {
                                                        $cityId = $get('city_id');
                                                        if (!$cityId) {
                                                            return [];
                                                        }

                                                        return \App\Models\Ward::where('city_id', $cityId)
                                                            ->active()
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('village_id', null);
                                                    })
                                                    ->placeholder('Seleccione un corregimiento')
                                                    ->helperText('Solo se muestran si el municipio tiene corregimientos'),

                                                Forms\Components\Select::make('village_id')
                                                    ->label('Vereda')
                                                    ->searchable()
                                                    ->visible(function ($get) {
                                                        // Solo mostrar si hay un corregimiento seleccionado
                                                        $wardId = $get('ward_id');
                                                        if (!$wardId) {
                                                            return false;
                                                        }

                                                        // Verificar si el corregimiento tiene veredas
                                                        $villagesCount = \App\Models\Village::where('ward_id', $wardId)
                                                            ->active()
                                                            ->count();

                                                        return $villagesCount > 0;
                                                    })
                                                    ->options(function ($get) {
                                                        $wardId = $get('ward_id');
                                                        if (!$wardId) {
                                                            return [];
                                                        }

                                                        return \App\Models\Village::where('ward_id', $wardId)
                                                            ->active()
                                                            ->orderBy('name')
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->placeholder('Seleccione una vereda')
                                                    ->helperText('Solo se muestran si el corregimiento tiene veredas'),
                                            ]),



                                        Forms\Components\Textarea::make('business_address')
                                            ->label('Dirección del Negocio')
                                            ->placeholder('Dirección completa del establecimiento')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('business_phone')
                                            ->label('Teléfono del Negocio')
                                            ->tel()
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('Teléfono comercial'),

                                        Forms\Components\TextInput::make('business_email')
                                            ->label('Email del Negocio')
                                            ->email()
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('contacto@negocio.com'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),
                    ])
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin emprendimiento'),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin ubicación'),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin gestor'),


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
                    ->tooltip('Editar emprendedor')
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
                    ->tooltip('Restaurar emprendedor')
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();


        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
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
            'index' => Pages\ListEntrepreneurs::route('/'),
            'create' => Pages\CreateEntrepreneur::route('/create'),
            'edit' => Pages\EditEntrepreneur::route('/{record}/edit'),
        ];
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
}
