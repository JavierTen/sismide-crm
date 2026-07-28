<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActorResource\Pages;
use App\Filament\Resources\ActorResource\RelationManagers\EntityContactsRelationManager;
use App\Models\Actor;
use App\Models\EntityContact;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class ActorResource extends Resource
{
    protected static ?string $model = Actor::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Actores';

    protected static ?string $modelLabel       = 'Entidad';
    protected static ?string $pluralModelLabel = 'Entidades y Actores';

    protected static ?int $navigationSort = 1;

    // ─── Helpers de permisos ─────────────────────────────────────────────────

    private static function userCanList(): bool
    {
        return auth()->user()?->can('listActors') ?? false;
    }

    private static function userCanCreate(): bool
    {
        return auth()->user()?->can('createActor') ?? false;
    }

    private static function userCanEdit(): bool
    {
        return auth()->user()?->can('editActor') ?? false;
    }

    private static function userCanDelete(): bool
    {
        return auth()->user()?->can('deleteActor') ?? false;
    }

    public static function canViewAny(): bool       { return static::userCanList(); }
    public static function canCreate(): bool        { return static::userCanCreate(); }
    public static function canEdit($record): bool   { return static::userCanEdit(); }
    public static function canDelete($record): bool { return static::userCanDelete(); }

    public static function canRestore($record): bool
    {
        return static::userCanDelete();
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    // ─── Validación mínimo de palabras ────────────────────────────────────────

    private static function minWordsRule(int $min = 35): Closure
    {
        return static function (string $attribute, $value, Closure $fail) use ($min) {
            $wordCount = count(array_filter(preg_split('/\s+/', trim((string) $value))));
            if ($wordCount < $min) {
                $fail("Debe escribir al menos {$min} palabras.");
            }
        };
    }

    // ─── Formulario ──────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Registro de Entidad')
                    ->tabs([

                        // ══════════════════════════════════════════════════════
                        // TAB 1 — INFORMACIÓN GENERAL
                        // ══════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Datos de la Entidad')
                                    ->description('Información básica de la organización o entidad')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la Entidad')
                                            ->maxLength(255)
                                            ->required()
                                            ->placeholder('Nombre de la organización o entidad')
                                            ->unique(
                                                table: Actor::class,
                                                column: 'name',
                                                ignoreRecord: true
                                            )
                                            ->validationMessages([
                                                'unique' => 'Ya existe una entidad registrada con este nombre.',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('nit')
                                            ->label('NIT o Identificación')
                                            ->maxLength(50)
                                            ->placeholder('Ej: 900.123.456-7')
                                            ->helperText('Cuando aplique'),

                                        Forms\Components\Select::make('type')
                                            ->label('Tipo de Entidad')
                                            ->options(Actor::TYPE_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->placeholder('Seleccione el tipo')
                                            ->native(false),

                                        Forms\Components\TextInput::make('type_other')
                                            ->label('Especifique el tipo')
                                            ->maxLength(255)
                                            ->placeholder('Especifique otro tipo de entidad')
                                            ->visible(fn(Get $get) => $get('type') === 'other')
                                            ->requiredIf('type', 'other')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('nature')
                                            ->label('Naturaleza de la Entidad')
                                            ->options(Actor::NATURE_OPTIONS)
                                            ->required()
                                            ->placeholder('Seleccione la naturaleza')
                                            ->native(false),

                                        Forms\Components\TextInput::make('economic_sector')
                                            ->label('Sector Económico o Institucional')
                                            ->maxLength(255)
                                            ->placeholder('Ej: Agropecuario, Servicios, Tecnología'),

                                        Forms\Components\TextInput::make('institutional_phone')
                                            ->label('Teléfono Institucional')
                                            ->tel()
                                            ->maxLength(50)
                                            ->placeholder('+57 5 123 4567'),

                                        Forms\Components\TextInput::make('institutional_email')
                                            ->label('Correo Institucional')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('entidad@ejemplo.com'),

                                        Forms\Components\TextInput::make('website')
                                            ->label('Página Web o Redes Sociales')
                                            ->maxLength(255)
                                            ->placeholder('https://www.entidad.com o @entidad')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción Breve de la Entidad')
                                            ->placeholder('Descripción de la misión, objetivos y actividades principales')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('linkage_status')
                                            ->label('Estado de Vinculación')
                                            ->options(Actor::LINKAGE_STATUS_OPTIONS)
                                            ->required()
                                            ->placeholder('Seleccione el estado')
                                            ->native(false),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        // ══════════════════════════════════════════════════════
                        // TAB 2 — UBICACIÓN Y COBERTURA
                        // ══════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Ubicación y Cobertura')
                            ->icon('heroicon-o-map-pin')
                            ->schema([

                                Forms\Components\Section::make('Ubicación Física')
                                    ->description('La ubicación física y la cobertura son conceptos diferentes. Una entidad puede tener su oficina en un municipio y brindar apoyo en varios.')
                                    ->icon('heroicon-o-building-office-2')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_physical_office')
                                            ->label('¿Cuenta con oficina física?')
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
                                            ->options(fn() => \App\Models\Department::active()->orderBy('name')->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->afterStateUpdated(fn(callable $set) => $set('city_id', null))
                                            ->placeholder('Seleccione un departamento'),

                                        Forms\Components\Select::make('city_id')
                                            ->label('Municipio')
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->options(function (Get $get) {
                                                $departmentId = $get('department_id');
                                                if (!$departmentId) return [];
                                                return \App\Models\City::active()
                                                    ->where('department_id', $departmentId)
                                                    ->pluck('name', 'id');
                                            })
                                            ->placeholder('Seleccione un municipio'),

                                        Forms\Components\TextInput::make('main_location')
                                            ->label('Tipo de Ubicación')
                                            ->maxLength(255)
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->placeholder('Ej: Sede administrativa, local comercial, plaza de mercado'),

                                        Forms\Components\TextInput::make('office_hours')
                                            ->label('Horarios de Atención')
                                            ->maxLength(255)
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->placeholder('Ej: Lunes a Viernes 8:00 AM - 5:00 PM'),

                                        Forms\Components\TextInput::make('latitude')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->placeholder('Ej: 10.9639997')
                                            ->helperText('Coordenada de latitud')
                                            ->step(0.00000001)
                                            ->minValue(-90)
                                            ->maxValue(90),

                                        Forms\Components\TextInput::make('longitude')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->placeholder('Ej: -74.7965423')
                                            ->helperText('Coordenada de longitud')
                                            ->step(0.00000001)
                                            ->minValue(-180)
                                            ->maxValue(180),

                                        Forms\Components\FileUpload::make('georeference_photo_path')
                                            ->label('Foto de Georreferenciación')
                                            ->image()
                                            ->visible(fn(Get $get) => $get('has_physical_office') === true)
                                            ->requiredIf('has_physical_office', true)
                                            ->directory('actors/georeference-photos')
                                            ->downloadable()
                                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                                            ->maxSize(5120)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                            ->helperText('Imagen de la ubicación (máx. 5MB)')
                                            ->validationMessages(['max' => 'El archivo no puede superar los 5MB.'])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Cobertura Territorial')
                                    ->description('Territorios en los que la entidad puede brindar apoyo')
                                    ->icon('heroicon-o-globe-americas')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('territorial_coverage')
                                            ->label('Territorios en los que la entidad puede brindar apoyo')
                                            ->options(Actor::TERRITORIAL_COVERAGE_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('attention_modality')
                                            ->label('Modalidad de Atención')
                                            ->options(Actor::ATTENTION_MODALITY_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        // ══════════════════════════════════════════════════════
                        // TAB 3 — CAPACIDADES Y COMPROMISOS
                        // ══════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Capacidades y Compromisos')
                            ->icon('heroicon-o-hand-raised')
                            ->schema([

                                Forms\Components\Section::make('Áreas en las que puede aportar')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('contribution_areas')
                                            ->label('Áreas en las que puede aportar')
                                            ->options(Actor::CONTRIBUTION_AREAS_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('contribution_areas_other')
                                            ->label('Especifique el área de aporte')
                                            ->maxLength(255)
                                            ->placeholder('Describa el área de aporte')
                                            ->visible(fn(Get $get) => is_array($get('contribution_areas')) && in_array('other', $get('contribution_areas')))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Experiencia Previa')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Forms\Components\Toggle::make('has_entrepreneurship_experience')
                                            ->label('¿Tiene experiencia previa con proyectos de emprendimiento?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onIcon('heroicon-m-check-circle')
                                            ->offIcon('heroicon-m-x-circle')
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('entrepreneurship_experience_details')
                                            ->label('Describa la experiencia')
                                            ->placeholder('Describa los proyectos o experiencias previas con emprendimientos')
                                            ->rows(3)
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true)
                                            ->requiredIf('has_entrepreneurship_experience', true)
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('experience_population_type')
                                            ->label('Tipo de Población Atendida')
                                            ->options(Actor::EXPERIENCE_POPULATION_TYPE_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('experience_economic_sectors')
                                            ->label('Sectores Económicos Atendidos')
                                            ->options(Actor::EXPERIENCE_ECONOMIC_SECTORS_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('experience_territories')
                                            ->label('Territorios en los que ha trabajado')
                                            ->options(Actor::TERRITORIAL_COVERAGE_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('experience_entrepreneurships_count')
                                            ->label('Número Aproximado de Emprendimientos Atendidos')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('Ej: 25')
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true),

                                        Forms\Components\FileUpload::make('experience_evidence_path')
                                            ->label('Evidencia de Experiencia')
                                            ->helperText('Cuando esté disponible (máx. 5MB)')
                                            ->directory('actors/experience-evidence')
                                            ->downloadable()
                                            ->maxSize(5120)
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                            ->visible(fn(Get $get) => $get('has_entrepreneurship_experience') === true),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Compromisos con Ruta D')
                                    ->icon('heroicon-o-document-check')
                                    ->schema([
                                        Forms\Components\Radio::make('commitments_confirmed')
                                            ->label('¿La entidad manifiesta interés en asumir compromisos con Ruta D?')
                                            ->options(Actor::COMMITMENTS_CONFIRMED_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->columns(3)
                                            ->gridDirection('row'),

                                        Forms\Components\CheckboxList::make('commitment_types')
                                            ->label('Tipos de Compromisos')
                                            ->options(Actor::COMMITMENT_TYPES_OPTIONS)
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('commitment_description')
                                            ->label('Descripción General del Posible Compromiso')
                                            ->placeholder('Describa detalladamente el compromiso que asumiría')
                                            ->rows(3)
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('commitment_responsible_contact')
                                            ->label('Contacto Responsable')
                                            ->maxLength(255)
                                            ->placeholder('Nombre de quien lidera el compromiso')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes'),

                                        Forms\Components\TextInput::make('commitment_modality')
                                            ->label('Modalidad')
                                            ->maxLength(255)
                                            ->placeholder('Ej: Presencial, Virtual, Mixta')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes'),

                                        Forms\Components\CheckboxList::make('commitment_routes')
                                            ->label('Ruta o Rutas Beneficiadas')
                                            ->options(Actor::COMMITMENT_ROUTES_OPTIONS)
                                            ->columns(1)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('commitment_municipalities')
                                            ->label('Municipios Beneficiados')
                                            ->options(Actor::TERRITORIAL_COVERAGE_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('commitment_estimated_count')
                                            ->label('Número Estimado de Emprendedores que puede Atender')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('Ej: 10')
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes'),

                                        Forms\Components\Textarea::make('commitment_observations')
                                            ->label('Observaciones')
                                            ->placeholder('Condiciones, restricciones u observaciones sobre el compromiso')
                                            ->rows(2)
                                            ->visible(fn(Get $get) => $get('commitments_confirmed') === 'yes')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        // ══════════════════════════════════════════════════════
                        // TAB 4 — ARTICULACIÓN POR RUTAS
                        // ══════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Articulación por Rutas')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->schema([
                                Forms\Components\Placeholder::make('routes_intro')
                                    ->label('')
                                    ->content('¿Con qué rutas puede articularse esta entidad? Seleccione las rutas e indique los tipos de apoyo que puede ofrecer.')
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Ruta 1. Preemprendimiento y Validación Temprana')
                                    ->description('Niveles asociados: 0, 1 y 2')
                                    ->icon('heroicon-o-light-bulb')
                                    ->schema([
                                        Forms\Components\Toggle::make('route_1_enabled')
                                            ->label('¿Puede articularse con emprendimientos de Ruta 1?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('route_1_support_types')
                                            ->label('Tipos de apoyo')
                                            ->options(Actor::ROUTE_1_SUPPORT_TYPES_OPTIONS)
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('route_1_enabled') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Ruta 2. Consolidación Empresarial')
                                    ->description('Niveles asociados: 3 y 4')
                                    ->icon('heroicon-o-rocket-launch')
                                    ->schema([
                                        Forms\Components\Toggle::make('route_2_enabled')
                                            ->label('¿Puede articularse con emprendimientos de Ruta 2?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('route_2_support_types')
                                            ->label('Tipos de apoyo')
                                            ->options(Actor::ROUTE_2_SUPPORT_TYPES_OPTIONS)
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('route_2_enabled') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                Forms\Components\Section::make('Ruta 3. Escalamiento y Expansión Empresarial')
                                    ->description('Nivel asociado: 5')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        Forms\Components\Toggle::make('route_3_enabled')
                                            ->label('¿Puede articularse con emprendimientos de Ruta 3?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('route_3_support_types')
                                            ->label('Tipos de apoyo')
                                            ->options(Actor::ROUTE_3_SUPPORT_TYPES_OPTIONS)
                                            ->columns(2)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('route_3_enabled') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        // ══════════════════════════════════════════════════════
                        // TAB 5 — UTILIDAD ESTRATÉGICA
                        // ══════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Utilidad Estratégica')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([

                                // Conexión con mercados
                                Forms\Components\Section::make('Conexión con Mercados')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->schema([
                                        Forms\Components\Toggle::make('market_connection_enabled')
                                            ->label('¿La entidad puede facilitar conexiones con mercados?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('market_connection_types')
                                            ->label('Tipo de mercado')
                                            ->options(Actor::MARKET_CONNECTION_TYPES_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('market_connection_enabled') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('market_connection')
                                            ->label('Describa cómo puede ayudar en conexiones de mercado')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('market_connection_enabled') === true)
                                            ->rule(fn(Get $get) => $get('market_connection_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Gestiones con autoridades
                                Forms\Components\Section::make('Gestiones con Autoridades')
                                    ->icon('heroicon-o-building-library')
                                    ->schema([
                                        Forms\Components\Toggle::make('authority_management_enabled')
                                            ->label('¿La entidad puede apoyar gestiones con autoridades o entidades públicas?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('authority_management')
                                            ->label('Describa el apoyo que puede brindar')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('authority_management_enabled') === true)
                                            ->rule(fn(Get $get) => $get('authority_management_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('authority_management_entities')
                                            ->label('Entidades o Autoridades con las que puede Articular')
                                            ->placeholder('Liste las entidades o autoridades gubernamentales con las que tiene relación')
                                            ->rows(3)
                                            ->visible(fn(Get $get) => $get('authority_management_enabled') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('authority_management_routes')
                                            ->label('Rutas Beneficiadas')
                                            ->options(Actor::COMMITMENT_ROUTES_OPTIONS)
                                            ->columns(1)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('authority_management_enabled') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Acceso a financiación o inversión
                                Forms\Components\Section::make('Acceso a Financiación o Inversión')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->schema([
                                        Forms\Components\Toggle::make('financing_access_enabled')
                                            ->label('¿La entidad puede facilitar acceso a financiación o inversión?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('financing_types')
                                            ->label('Tipo de Financiación')
                                            ->options(Actor::FINANCING_TYPES_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('financing_access_enabled') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('financing_access')
                                            ->label('Descripción de las Oportunidades')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('financing_access_enabled') === true)
                                            ->rule(fn(Get $get) => $get('financing_access_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Capacitación, mentoría o asesoría
                                Forms\Components\Section::make('Capacitación, Mentoría o Asesoría')
                                    ->icon('heroicon-o-academic-cap')
                                    ->schema([
                                        Forms\Components\Toggle::make('training_advisory_enabled')
                                            ->label('¿La entidad puede brindar capacitaciones, mentorías o asesorías?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('training_service_types')
                                            ->label('Tipo de Servicio')
                                            ->options(Actor::TRAINING_SERVICE_TYPES_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('training_advisory_enabled') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('training_advisory')
                                            ->label('Temáticas Disponibles')
                                            ->placeholder('Mínimo 35 palabras — describa las temáticas, áreas de conocimiento o especializaciones que ofrece')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('training_advisory_enabled') === true)
                                            ->rule(fn(Get $get) => $get('training_advisory_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('training_modality')
                                            ->label('Modalidad')
                                            ->maxLength(100)
                                            ->placeholder('Ej: Presencial, Virtual, Híbrida')
                                            ->visible(fn(Get $get) => $get('training_advisory_enabled') === true),

                                        Forms\Components\TextInput::make('training_capacity_count')
                                            ->label('Capacidad Estimada de Participantes')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('Ej: 30')
                                            ->visible(fn(Get $get) => $get('training_advisory_enabled') === true),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Apoyo logístico
                                Forms\Components\Section::make('Apoyo Logístico')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        Forms\Components\Toggle::make('logistic_support_enabled')
                                            ->label('¿La entidad puede brindar apoyo logístico?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\CheckboxList::make('logistic_support_types')
                                            ->label('Tipo de Apoyo')
                                            ->options(Actor::LOGISTIC_SUPPORT_TYPES_OPTIONS)
                                            ->columns(3)
                                            ->gridDirection('row')
                                            ->visible(fn(Get $get) => $get('logistic_support_enabled') === true)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('logistic_support')
                                            ->label('Descripción del Apoyo')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('logistic_support_enabled') === true)
                                            ->rule(fn(Get $get) => $get('logistic_support_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('logistic_support_coverage')
                                            ->label('Ubicación o Cobertura')
                                            ->placeholder('Indique dónde puede ofrecer este apoyo logístico')
                                            ->rows(2)
                                            ->visible(fn(Get $get) => $get('logistic_support_enabled') === true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Fortalecimiento organizacional e innovación
                                Forms\Components\Section::make('Fortalecimiento Organizacional e Innovación')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Forms\Components\Toggle::make('organizational_strengthening_enabled')
                                            ->label('¿Puede apoyar procesos de fortalecimiento organizacional?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('organizational_strengthening_desc')
                                            ->label('Descripción')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('organizational_strengthening_enabled') === true)
                                            ->rule(fn(Get $get) => $get('organizational_strengthening_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('innovation_digital_enabled')
                                            ->label('¿Puede apoyar procesos de innovación o transformación digital?')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('gray')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('innovation_digital_desc')
                                            ->label('Descripción')
                                            ->placeholder('Mínimo 35 palabras...')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('innovation_digital_enabled') === true)
                                            ->rule(fn(Get $get) => $get('innovation_digital_enabled')
                                                ? static::minWordsRule(35) : null)
                                            ->helperText('Mínimo 35 palabras')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),

                                // Ámbito de acción (mantenido del formulario actual)
                                Forms\Components\Section::make('Ámbito de Acción')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        Forms\Components\Select::make('action_scope')
                                            ->label('Ámbito de Acción del Actor')
                                            ->options(Actor::ACTION_SCOPE_OPTIONS)
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
                    ->persistTabInQueryString(),
            ])
            ->columns(1);
    }

    // ─── Infolist (modal de vista) ────────────────────────────────────────────

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Vista de Entidad')
                    ->tabs([

                        // TAB 1 — Información General
                        Infolists\Components\Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Infolists\Components\Section::make('Datos de la Entidad')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Nombre de la Entidad')
                                            ->weight('bold')
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('nit')
                                            ->label('NIT o Identificación')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('type')
                                            ->label('Tipo de Entidad')
                                            ->formatStateUsing(fn($state) => Actor::TYPE_OPTIONS[$state] ?? $state)
                                            ->badge(),

                                        Infolists\Components\TextEntry::make('nature')
                                            ->label('Naturaleza')
                                            ->formatStateUsing(fn($state) => Actor::NATURE_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color('gray')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('economic_sector')
                                            ->label('Sector Económico')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('institutional_phone')
                                            ->label('Teléfono Institucional')
                                            ->default('—')
                                            ->columnSpan(1),

                                        Infolists\Components\TextEntry::make('institutional_email')
                                            ->label('Correo Institucional')
                                            ->default('—')
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('website')
                                            ->label('Página Web / Redes')
                                            ->default('—')
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('description')
                                            ->label('Descripción')
                                            ->columnSpanFull()
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('linkage_status')
                                            ->label('Estado de Vinculación')
                                            ->formatStateUsing(fn($state) => Actor::LINKAGE_STATUS_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'active_commitment' => 'success',
                                                'linked'            => 'info',
                                                'in_management'     => 'warning',
                                                'inactive'          => 'gray',
                                                default             => 'gray',
                                            }),
                                    ])
                                    ->columns(2),
                            ]),

                        // TAB 2 — Ubicación y Cobertura
                        Infolists\Components\Tabs\Tab::make('Ubicación y Cobertura')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Infolists\Components\Section::make('Ubicación Física')
                                    ->icon('heroicon-o-building-office-2')
                                    ->schema([
                                        Infolists\Components\IconEntry::make('has_physical_office')
                                            ->label('¿Tiene oficina física?')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-check-circle')
                                            ->falseIcon('heroicon-o-x-circle')
                                            ->trueColor('success')
                                            ->falseColor('gray'),

                                        Infolists\Components\TextEntry::make('office_address')
                                            ->label('Dirección')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('department.name')
                                            ->label('Departamento')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('city.name')
                                            ->label('Municipio')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('main_location')
                                            ->label('Tipo de Ubicación')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('office_hours')
                                            ->label('Horarios de Atención')
                                            ->default('—'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Cobertura Territorial')
                                    ->icon('heroicon-o-globe-americas')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('territorial_coverage')
                                            ->label('Territorios')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) return '—';
                                                $arr = is_array($state) ? $state : json_decode($state, true);
                                                return collect($arr ?? [])->map(fn($k) => Actor::TERRITORIAL_COVERAGE_OPTIONS[$k] ?? $k)->join(', ');
                                            })
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('attention_modality')
                                            ->label('Modalidad de Atención')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) return '—';
                                                $arr = is_array($state) ? $state : json_decode($state, true);
                                                return collect($arr ?? [])->map(fn($k) => Actor::ATTENTION_MODALITY_OPTIONS[$k] ?? $k)->join(', ');
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        // TAB 3 — Capacidades y Compromisos
                        Infolists\Components\Tabs\Tab::make('Capacidades y Compromisos')
                            ->icon('heroicon-o-hand-raised')
                            ->schema([
                                Infolists\Components\Section::make('Áreas de Aporte')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('contribution_areas')
                                            ->label('Áreas')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) return '—';
                                                $arr = is_array($state) ? $state : json_decode($state, true);
                                                return collect($arr ?? [])->map(fn($k) => Actor::CONTRIBUTION_AREAS_OPTIONS[$k] ?? $k)->join(' · ');
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Compromisos con Ruta D')
                                    ->icon('heroicon-o-document-check')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('commitments_confirmed')
                                            ->label('¿Manifiesta interés en asumir compromisos?')
                                            ->formatStateUsing(fn($state) => Actor::COMMITMENTS_CONFIRMED_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'yes'     => 'success',
                                                'no'      => 'danger',
                                                'pending' => 'warning',
                                                default   => 'gray',
                                            }),

                                        Infolists\Components\TextEntry::make('commitment_types')
                                            ->label('Tipos de Compromisos')
                                            ->formatStateUsing(function ($state) {
                                                if (!$state) return '—';
                                                $arr = is_array($state) ? $state : json_decode($state, true);
                                                return collect($arr ?? [])->map(fn($k) => Actor::COMMITMENT_TYPES_OPTIONS[$k] ?? $k)->join(' · ');
                                            })
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('commitment_description')
                                            ->label('Descripción del Compromiso')
                                            ->columnSpanFull()
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('commitment_responsible_contact')
                                            ->label('Contacto Responsable')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('commitment_modality')
                                            ->label('Modalidad')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('commitment_estimated_count')
                                            ->label('Emprendedores estimados')
                                            ->default('—'),
                                    ])
                                    ->columns(2),
                            ]),

                        // TAB 4 — Utilidad Estratégica
                        Infolists\Components\Tabs\Tab::make('Utilidad Estratégica')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Infolists\Components\Section::make('Resumen de Capacidades')
                                    ->schema([
                                        Infolists\Components\IconEntry::make('market_connection_enabled')
                                            ->label('Conexión con mercados')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('authority_management_enabled')
                                            ->label('Gestiones con autoridades')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('financing_access_enabled')
                                            ->label('Acceso a financiación')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('training_advisory_enabled')
                                            ->label('Capacitación / Mentorías')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('logistic_support_enabled')
                                            ->label('Apoyo logístico')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('organizational_strengthening_enabled')
                                            ->label('Fortalecimiento organizacional')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('innovation_digital_enabled')
                                            ->label('Innovación y digital')
                                            ->boolean()->trueColor('success')->falseColor('gray'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Articulación por Rutas')
                                    ->schema([
                                        Infolists\Components\IconEntry::make('route_1_enabled')
                                            ->label('Ruta 1 — Preemprendimiento y Validación Temprana')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('route_2_enabled')
                                            ->label('Ruta 2 — Consolidación Empresarial')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\IconEntry::make('route_3_enabled')
                                            ->label('Ruta 3 — Escalamiento y Expansión Empresarial')
                                            ->boolean()->trueColor('success')->falseColor('gray'),

                                        Infolists\Components\TextEntry::make('action_scope')
                                            ->label('Ámbito de Acción')
                                            ->formatStateUsing(fn($state) => Actor::ACTION_SCOPE_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color('info')
                                            ->default('—'),
                                    ])
                                    ->columns(2),
                            ]),

                        // TAB 5 — Actores / Contactos
                        Infolists\Components\Tabs\Tab::make('Actores / Contactos')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('contacts')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Nombre')
                                            ->weight('bold'),

                                        Infolists\Components\TextEntry::make('role')
                                            ->label('Cargo')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('contact_type')
                                            ->label('Tipo')
                                            ->formatStateUsing(fn($state) => EntityContact::CONTACT_TYPE_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color('gray')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('decision_level')
                                            ->label('Nivel de Decisión')
                                            ->formatStateUsing(fn($state) => EntityContact::DECISION_LEVEL_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'decision_maker' => 'danger',
                                                'influencer'     => 'warning',
                                                'operational'    => 'gray',
                                                default          => 'gray',
                                            })
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('email')
                                            ->label('Correo')
                                            ->default('—')
                                            ->columnSpan(2),

                                        Infolists\Components\TextEntry::make('phone')
                                            ->label('Teléfono')
                                            ->default('—'),

                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Estado')
                                            ->formatStateUsing(fn($state) => EntityContact::STATUS_OPTIONS[$state] ?? $state)
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'active'          => 'success',
                                                'pending_contact' => 'warning',
                                                'no_response'     => 'danger',
                                                default           => 'gray',
                                            }),

                                        Infolists\Components\IconEntry::make('is_primary_contact')
                                            ->label('Principal')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-star')
                                            ->falseIcon('heroicon-o-minus')
                                            ->trueColor('warning'),

                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('Observaciones')
                                            ->columnSpanFull()
                                            ->default('—'),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // ─── Tabla ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de la Entidad')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => Actor::TYPE_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'government_authority'    => 'success',
                        'financial_entity'        => 'warning',
                        'educational_institution' => 'info',
                        'ngo_foundation'          => 'danger',
                        default                   => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nature')
                    ->label('Naturaleza')
                    ->formatStateUsing(fn($state) => Actor::NATURE_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->default('—'),

                Tables\Columns\TextColumn::make('linkage_status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => Actor::LINKAGE_STATUS_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active_commitment' => 'success',
                        'linked'            => 'info',
                        'in_management'     => 'warning',
                        'inactive'          => 'gray',
                        default             => 'gray',
                    })
                    ->default('—'),

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
                    ->modalWidth('5xl')
                    ->visible(fn() => static::userCanList()),

                Tables\Actions\Action::make('add_contact')
                    ->label('')
                    ->icon('heroicon-o-user-plus')
                    ->tooltip('Agregar actor')
                    ->color('info')
                    ->visible(fn() => auth()->user()?->can('createEntityContact') ?? false)
                    ->modalHeading(fn($record) => 'Agregar actor — ' . $record->name)
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Section::make('Datos del Contacto')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Nombre y apellidos'),

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

                                Forms\Components\Select::make('contact_type')
                                    ->label('Tipo de Contacto')
                                    ->options(\App\Models\EntityContact::CONTACT_TYPE_OPTIONS)
                                    ->placeholder('Seleccione el tipo')
                                    ->native(false),

                                Forms\Components\Select::make('decision_level')
                                    ->label('Nivel de Decisión')
                                    ->options(\App\Models\EntityContact::DECISION_LEVEL_OPTIONS)
                                    ->placeholder('Seleccione el nivel')
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options(\App\Models\EntityContact::STATUS_OPTIONS)
                                    ->default('active')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Toggle::make('is_primary_contact')
                                    ->label('¿Es el contacto principal?')
                                    ->default(false)
                                    ->inline(false)
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Observaciones')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->contacts()->create([
                            ...$data,
                            'manager_id' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Contacto guardado')
                            ->body('El contacto fue registrado exitosamente.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar entidad')
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
                    ->tooltip('Restaurar entidad')
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
                            ->withFilename(fn() => 'entidades-actores-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with(['department', 'city', 'manager']))
                            ->withColumns([
                                Column::make('name')->heading('Nombre de la Entidad'),
                                Column::make('nit')->heading('NIT'),
                                Column::make('type')->heading('Tipo de Entidad')
                                    ->formatStateUsing(fn($state) => Actor::TYPE_OPTIONS[$state] ?? $state),
                                Column::make('nature')->heading('Naturaleza')
                                    ->formatStateUsing(fn($state) => Actor::NATURE_OPTIONS[$state] ?? $state),
                                Column::make('economic_sector')->heading('Sector Económico'),
                                Column::make('institutional_phone')->heading('Teléfono Institucional'),
                                Column::make('institutional_email')->heading('Correo Institucional'),
                                Column::make('website')->heading('Sitio Web'),
                                Column::make('linkage_status')->heading('Estado de Vinculación')
                                    ->formatStateUsing(fn($state) => Actor::LINKAGE_STATUS_OPTIONS[$state] ?? $state),
                                Column::make('action_scope')->heading('Ámbito de Acción')
                                    ->formatStateUsing(fn($state) => Actor::ACTION_SCOPE_OPTIONS[$state] ?? $state),
                                Column::make('has_physical_office')->heading('Tiene Oficina Física')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('office_address')->heading('Dirección'),
                                Column::make('department.name')->heading('Departamento'),
                                Column::make('city.name')->heading('Municipio'),
                                Column::make('territorial_coverage')->heading('Cobertura Territorial')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '';
                                        $arr = is_array($state) ? $state : json_decode($state, true);
                                        return collect($arr ?? [])->map(fn($k) => Actor::TERRITORIAL_COVERAGE_OPTIONS[$k] ?? $k)->join(', ');
                                    }),
                                Column::make('contribution_areas')->heading('Áreas de Aporte')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) return '';
                                        $arr = is_array($state) ? $state : json_decode($state, true);
                                        return collect($arr ?? [])->map(fn($k) => Actor::CONTRIBUTION_AREAS_OPTIONS[$k] ?? $k)->join(', ');
                                    }),
                                Column::make('has_entrepreneurship_experience')->heading('Tiene Experiencia')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('experience_entrepreneurships_count')->heading('Emprendimientos Atendidos'),
                                Column::make('commitments_confirmed')->heading('Compromisos con Ruta D')
                                    ->formatStateUsing(fn($state) => Actor::COMMITMENTS_CONFIRMED_OPTIONS[$state] ?? $state),
                                Column::make('market_connection_enabled')->heading('Conexión con Mercados')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('authority_management_enabled')->heading('Gestiones con Autoridades')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('financing_access_enabled')->heading('Acceso a Financiación')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('training_advisory_enabled')->heading('Capacitación/Mentorías')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('logistic_support_enabled')->heading('Apoyo Logístico')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('organizational_strengthening_enabled')->heading('Fortalecimiento Organizacional')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('innovation_digital_enabled')->heading('Innovación y Digital')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('route_1_enabled')->heading('Articulado Ruta 1')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('route_2_enabled')->heading('Articulado Ruta 2')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('route_3_enabled')->heading('Articulado Ruta 3')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha de Registro')
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
                                ->withFilename(fn() => 'entidades-actores-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with(['department', 'city', 'manager']))
                                ->withColumns([
                                    Column::make('name')->heading('Nombre de la Entidad'),
                                    Column::make('nit')->heading('NIT'),
                                    Column::make('type')->heading('Tipo de Entidad')
                                        ->formatStateUsing(fn($state) => Actor::TYPE_OPTIONS[$state] ?? $state),
                                    Column::make('nature')->heading('Naturaleza')
                                        ->formatStateUsing(fn($state) => Actor::NATURE_OPTIONS[$state] ?? $state),
                                    Column::make('linkage_status')->heading('Estado de Vinculación')
                                        ->formatStateUsing(fn($state) => Actor::LINKAGE_STATUS_OPTIONS[$state] ?? $state),
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha de Registro')
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

    // ─── Query ───────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    // ─── Relaciones y páginas ────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            EntityContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListActors::route('/'),
            'create' => Pages\CreateActor::route('/create'),
            'edit'   => Pages\EditActor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
