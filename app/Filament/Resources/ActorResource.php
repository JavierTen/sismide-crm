<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActorResource\Pages;
use App\Filament\Resources\ActorResource\RelationManagers;
use App\Models\Actor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;

//Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ActorResource extends Resource
{
    protected static ?string $model = Actor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Actores';

    protected static ?string $modelLabel = 'Actor';
    protected static ?string $pluralModelLabel = 'Actores';

    protected static ?int $navigationSort = 1;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listActors');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createActor');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editActor');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteActor');
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
                Forms\Components\Tabs::make('Registro de Actor')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Datos del Actor')
                                    ->description('Información básica de la organización o entidad')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre del Actor')
                                            ->maxLength(255)
                                            ->required()
                                            ->placeholder('Nombre de la organización o entidad')
                                            ->unique(
                                                table: Actor::class,
                                                column: 'name',
                                                ignoreRecord: true
                                            )
                                            ->validationMessages([
                                                'unique' => 'Ya existe un actor registrado con este nombre.',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('type')
                                            ->label('Tipo de Actor')
                                            ->options(Actor::TYPE_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->placeholder('Seleccione el tipo de actor')
                                            ->columnSpanFull()
                                            ->native(false),

                                        Forms\Components\TextInput::make('type_other')
                                            ->label('Especifique el tipo')
                                            ->maxLength(255)
                                            ->placeholder('Especifique otro tipo de actor')
                                            ->visible(fn(Get $get) => $get('type') === 'other')
                                            ->requiredIf('type', 'other'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Contacto Principal')
                                    ->description('Información de la persona de contacto')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_name')
                                            ->label('Nombre Completo del Contacto')
                                            ->maxLength(255)
                                            ->required()
                                            ->placeholder('Nombre y apellidos completos')
                                            ->rule('regex:/^[\pL\s]+$/u')
                                            ->validationMessages([
                                                'regex' => 'El nombre solo puede contener letras y espacios.',
                                            ]),

                                        Forms\Components\TextInput::make('contact_role')
                                            ->label('Rol o Cargo')
                                            ->maxLength(255)
                                            ->required()
                                            ->placeholder('Cargo en la institución'),

                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('Correo Electrónico')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('contacto@ejemplo.com'),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Teléfono / Celular')
                                            ->tel()
                                            ->required()
                                            ->maxLength(50)
                                            ->placeholder('+57 300 123 4567')
                                            ->regex('/^[\+]?[0-9\s\-\(\)]+$/'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Ubicación')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Ubicación y Accesibilidad')
                                    ->description('Información de ubicación física del actor')
                                    ->icon('heroicon-o-building-office-2')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_physical_office')
                                            ->label('¿Cuenta con oficina física?')
                                            ->helperText('Indique si el actor tiene una ubicación física')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check-circle')
                                            ->offIcon('heroicon-m-x-circle')
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('office_address')
                                            ->label('Dirección de la Oficina Física')
                                            ->placeholder('Dirección completa del establecimiento')
                                            ->rows(2)
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('department_id')
                                            ->label('Departamento')
                                            ->options(function () {
                                                return \App\Models\Department::active()
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('city_id', null);
                                            })
                                            ->placeholder('Seleccione un departamento'),

                                        Forms\Components\Select::make('city_id')
                                            ->label('Municipio')
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->options(function (Get $get) {
                                                $departmentId = $get('department_id');
                                                if (!$departmentId) {
                                                    return [];
                                                }
                                                return \App\Models\City::active()
                                                    ->where('department_id', $departmentId)
                                                    ->pluck('name', 'id');
                                            })
                                            ->placeholder('Seleccione un municipio'),

                                        Forms\Components\TextInput::make('main_location')
                                            ->label('Lugar de Ubicación Principal')
                                            ->maxLength(255)
                                            ->requiredIf('has_physical_office', true)
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->placeholder('Ej: Sede administrativa, local comercial, plaza de mercado')
                                            ->helperText('Especifique el tipo de ubicación'),

                                        Forms\Components\TextInput::make('office_hours')
                                            ->label('Horarios de Atención / Disponibilidad')
                                            ->maxLength(255)
                                            ->requiredIf('has_physical_office', true)
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->placeholder('Ej: Lunes a Viernes 8:00 AM - 5:00 PM'),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Aportes y Compromisos')
                            ->icon('heroicon-o-hand-raised')
                            ->schema([
                                Forms\Components\Section::make('Áreas de Aporte')
                                    ->description('Áreas en las que el actor puede contribuir')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        Forms\Components\Radio::make('contribution_areas')
                                            ->label('Áreas en las que puede aportar')
                                            ->options(Actor::CONTRIBUTION_AREAS_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->columns(3) // 3 columnas para distribuir mejor
                                            ->gridDirection('row'),

                                        Forms\Components\TextInput::make('contribution_areas_other')
                                            ->label('Especifique otra área')
                                            ->maxLength(255)
                                            ->placeholder('Describa otra área de aporte')
                                            ->visible(fn(Get $get) => $get('contribution_areas') === 'other')
                                            ->requiredIf('contribution_areas', 'other')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Experiencia Previa')
                                    ->description('Experiencia con proyectos de emprendimiento')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_entrepreneurship_experience')
                                            ->label('¿Tiene experiencia previa con proyectos de emprendimiento?')
                                            ->helperText('Indique si ha trabajado anteriormente con emprendedores')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check-circle')
                                            ->offIcon('heroicon-m-x-circle')
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('entrepreneurship_experience_details')
                                            ->label('Especifique cuáles')
                                            ->placeholder('Describa los proyectos o experiencias previas')
                                            ->rows(3)
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true)
                                            ->requiredIf('has_entrepreneurship_experience', true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Compromisos con Ruta D')
                                    ->description('Compromisos que el actor puede asumir')
                                    ->icon('heroicon-o-document-check')
                                    ->schema([
                                        Forms\Components\Radio::make('commitments')
                                            ->label('Compromisos que estaría dispuesto a asumir')
                                            ->options(Actor::COMMITMENTS_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->columns(3)
                                            ->gridDirection('row'),

                                        Forms\Components\TextInput::make('commitments_other')
                                            ->label('Especifique otro compromiso')
                                            ->maxLength(255)
                                            ->placeholder('Describa otro tipo de compromiso')
                                            ->visible(fn(Get $get) => $get('commitments') === 'other') // Cambiado aquí
                                            ->requiredIf('commitments', 'other')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Utilidad Estratégica')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Utilidad Estratégica del Contacto')
                                    ->description('¿Para qué nos puede servir este actor?')
                                    ->icon('heroicon-o-light-bulb')
                                    ->schema([
                                        Forms\Components\Textarea::make('market_connection')
                                            ->label('Conexión con Mercados')
                                            ->placeholder('Describa cómo este actor puede ayudar en conexiones de mercado')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('authority_management')
                                            ->label('Gestiones con Autoridades')
                                            ->placeholder('Describa el apoyo que puede brindar en gestiones gubernamentales')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('financing_access')
                                            ->label('Acceso a Financiamiento / Inversión')
                                            ->placeholder('Describa las oportunidades de financiamiento que puede facilitar')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('training_advisory')
                                            ->label('Capacitación / Asesorías')
                                            ->placeholder('Describa los servicios de capacitación o asesoría disponibles')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('logistic_support')
                                            ->label('Apoyo Logístico')
                                            ->placeholder('Describa el apoyo logístico que puede ofrecer (espacios, transporte, infraestructura)')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Valor Diferencial')
                                    ->description('Alcance y diferenciación del actor')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        Forms\Components\Select::make('action_scope')
                                            ->label('Ámbito de Acción del Actor')
                                            ->options(Actor::ACTION_SCOPE_OPTIONS)
                                            ->required()
                                            ->placeholder('Seleccione el ámbito de acción')
                                            ->native(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
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
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => Actor::TYPE_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'government_authority' => 'success',
                        'financial_entity' => 'warning',
                        'educational_institution' => 'info',
                        'ngo_foundation' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contacto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->visible(fn() => static::userCanList()),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar actor')
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
                    ->tooltip('Restaurar actor')
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
                            ->withFilename(fn() => 'actores-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'department',
                                'city',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN GENERAL ===
                                Column::make('name')->heading('Nombre del Actor'),

                                Column::make('type')->heading('Tipo de Actor')
                                    ->formatStateUsing(fn($state) => \App\Models\Actor::TYPE_OPTIONS[$state] ?? $state),

                                Column::make('type_other')->heading('Otro Tipo (especificado)'),

                                // === CONTACTO PRINCIPAL ===
                                Column::make('contact_name')->heading('Nombre del Contacto'),
                                Column::make('contact_role')->heading('Rol/Cargo del Contacto'),
                                Column::make('contact_email')->heading('Email del Contacto'),
                                Column::make('contact_phone')->heading('Teléfono del Contacto'),

                                // === UBICACIÓN Y ACCESIBILIDAD ===
                                Column::make('has_physical_office')->heading('Tiene Oficina Física')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                Column::make('office_address')->heading('Dirección de la Oficina'),
                                Column::make('department.name')->heading('Departamento'),
                                Column::make('city.name')->heading('Municipio'),
                                Column::make('main_location')->heading('Ubicación Principal'),
                                Column::make('office_hours')->heading('Horarios de Atención'),

                                // === ÁREAS DE APORTE ===
                                Column::make('contribution_areas')->heading('Áreas de Aporte')
                                    ->formatStateUsing(function ($state, $record) {
                                        if (!$state) return '';

                                        // Si es un string simple (no JSON), devolverlo traducido
                                        if (is_string($state) && !str_starts_with($state, '[') && !str_starts_with($state, '{')) {
                                            $options = \App\Models\Actor::CONTRIBUTION_AREAS_OPTIONS;
                                            return $options[$state] ?? $state;
                                        }

                                        // Si es JSON, decodificar y traducir
                                        if (is_string($state)) {
                                            $decoded = json_decode($state, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $state = $decoded;
                                            }
                                        }

                                        // Si es array, traducir cada elemento
                                        if (is_array($state)) {
                                            $options = \App\Models\Actor::CONTRIBUTION_AREAS_OPTIONS;
                                            return collect($state)->map(fn($key) => $options[$key] ?? $key)->join(', ');
                                        }

                                        return $state;
                                    }),

                                Column::make('contribution_areas_other')->heading('Otra Área de Aporte (especificada)'),

                                // === EXPERIENCIA PREVIA ===
                                Column::make('has_entrepreneurship_experience')->heading('Tiene Experiencia en Emprendimiento')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                Column::make('entrepreneurship_experience_details')->heading('Detalles de Experiencia'),

                                // === COMPROMISOS ===
                                Column::make('commitments')->heading('Compromisos con Ruta D')
                                    ->formatStateUsing(fn($state) => \App\Models\Actor::COMMITMENTS_OPTIONS[$state] ?? $state),

                                Column::make('commitments_other')->heading('Otro Compromiso (especificado)'),

                                // === UTILIDAD ESTRATÉGICA ===
                                Column::make('market_connection')->heading('Conexión con Mercados'),
                                Column::make('authority_management')->heading('Gestiones con Autoridades'),
                                Column::make('financing_access')->heading('Acceso a Financiamiento'),
                                Column::make('training_advisory')->heading('Capacitación/Asesorías'),
                                Column::make('logistic_support')->heading('Apoyo Logístico'),

                                // === VALOR DIFERENCIAL ===
                                Column::make('action_scope')->heading('Ámbito de Acción')
                                    ->formatStateUsing(fn($state) => \App\Models\Actor::ACTION_SCOPE_OPTIONS[$state] ?? $state),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha de Registro')
                                    ->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                Column::make('updated_at')->heading('Última Actualización')
                                    ->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
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
                                ->withFilename(fn() => 'actores-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'department',
                                    'city',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN GENERAL ===
                                    Column::make('name')->heading('Nombre del Actor'),

                                    Column::make('type')->heading('Tipo de Actor')
                                        ->formatStateUsing(fn($state) => \App\Models\Actor::TYPE_OPTIONS[$state] ?? $state),

                                    Column::make('type_other')->heading('Otro Tipo (especificado)'),

                                    // === CONTACTO PRINCIPAL ===
                                    Column::make('contact_name')->heading('Nombre del Contacto'),
                                    Column::make('contact_role')->heading('Rol/Cargo del Contacto'),
                                    Column::make('contact_email')->heading('Email del Contacto'),
                                    Column::make('contact_phone')->heading('Teléfono del Contacto'),

                                    // === UBICACIÓN Y ACCESIBILIDAD ===
                                    Column::make('has_physical_office')->heading('Tiene Oficina Física')
                                        ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                    Column::make('office_address')->heading('Dirección de la Oficina'),
                                    Column::make('department.name')->heading('Departamento'),
                                    Column::make('city.name')->heading('Municipio'),
                                    Column::make('main_location')->heading('Ubicación Principal'),
                                    Column::make('office_hours')->heading('Horarios de Atención'),

                                    // === ÁREAS DE APORTE ===
                                    Column::make('contribution_areas')->heading('Áreas de Aporte')
                                        ->formatStateUsing(function ($state, $record) {
                                            if (!$state) return '';

                                            // Si es un string simple (no JSON), devolverlo traducido
                                            if (is_string($state) && !str_starts_with($state, '[') && !str_starts_with($state, '{')) {
                                                $options = \App\Models\Actor::CONTRIBUTION_AREAS_OPTIONS;
                                                return $options[$state] ?? $state;
                                            }

                                            // Si es JSON, decodificar y traducir
                                            if (is_string($state)) {
                                                $decoded = json_decode($state, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $state = $decoded;
                                                }
                                            }

                                            // Si es array, traducir cada elemento
                                            if (is_array($state)) {
                                                $options = \App\Models\Actor::CONTRIBUTION_AREAS_OPTIONS;
                                                return collect($state)->map(fn($key) => $options[$key] ?? $key)->join(', ');
                                            }

                                            return $state;
                                        }),

                                    Column::make('contribution_areas_other')->heading('Otra Área de Aporte (especificada)'),

                                    // === EXPERIENCIA PREVIA ===
                                    Column::make('has_entrepreneurship_experience')->heading('Tiene Experiencia en Emprendimiento')
                                        ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                    Column::make('entrepreneurship_experience_details')->heading('Detalles de Experiencia'),

                                    // === COMPROMISOS ===
                                    Column::make('commitments')->heading('Compromisos con Ruta D')
                                        ->formatStateUsing(fn($state) => \App\Models\Actor::COMMITMENTS_OPTIONS[$state] ?? $state),

                                    Column::make('commitments_other')->heading('Otro Compromiso (especificado)'),

                                    // === UTILIDAD ESTRATÉGICA ===
                                    Column::make('market_connection')->heading('Conexión con Mercados'),
                                    Column::make('authority_management')->heading('Gestiones con Autoridades'),
                                    Column::make('financing_access')->heading('Acceso a Financiamiento'),
                                    Column::make('training_advisory')->heading('Capacitación/Asesorías'),
                                    Column::make('logistic_support')->heading('Apoyo Logístico'),

                                    // === VALOR DIFERENCIAL ===
                                    Column::make('action_scope')->heading('Ámbito de Acción')
                                        ->formatStateUsing(fn($state) => \App\Models\Actor::ACTION_SCOPE_OPTIONS[$state] ?? $state),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha de Registro')
                                        ->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                    Column::make('updated_at')->heading('Última Actualización')
                                        ->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('Admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Ajusta según tu sistema de roles
        if (auth()->user()->hasRole(['Admin', 'Viewer'])) { // o hasRole('admin')
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
            'index' => Pages\ListActors::route('/'),
            'create' => Pages\CreateActor::route('/create'),
            'edit' => Pages\EditActor::route('/{record}/edit'),
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
