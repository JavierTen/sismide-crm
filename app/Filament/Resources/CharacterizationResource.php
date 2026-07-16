<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CharacterizationResource\Pages;
use App\Models\Characterization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CharacterizationResource extends Resource
{
    protected static ?string $model = Characterization::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Caracterización';

    protected static ?string $pluralModelLabel = 'Caracterizaciones';

    protected static ?int $navigationSort = 3;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('listCharacterizations');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('createCharacterization');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('editCharacterization');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('deleteCharacterization');
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
        $entrepreneurData = function ($get, string $relation, string $field, string $default = '----') {
            $id = $get('entrepreneur_id');
            if (! $id) return $default;
            $e = \App\Models\Entrepreneur::with([
                'business.economicActivity', 'business.productiveLine',
                'city', 'manager', 'gender', 'maritalStatus', 'educationLevel', 'project',
            ])->find($id);
            return data_get($e, $field) ?? $default;
        };

        return $form
            ->schema([
                // ─── a. Información del Emprendedor ───────────────────────
                Forms\Components\Section::make('Información del Emprendedor')
                    ->description('Selecciona el emprendedor y visualiza sus datos principales')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('entrepreneur_id')
                            ->label('Emprendedor')
                            ->relationship(
                                'entrepreneur',
                                'full_name',
                                fn ($query) => $query->when(
                                    ! auth()->user()->hasRole('Admin'),
                                    fn ($q) => $q->where('manager_id', auth()->id())
                                )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->placeholder('Buscar emprendedor por nombre')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->helperText(
                                fn (string $operation): string => $operation === 'edit'
                                    ? 'El emprendedor asignado no puede ser modificado.'
                                    : 'Selecciona el emprendedor para autocompletar información relacionada'
                            )
                            ->unique(
                                table: 'characterizations',
                                column: 'entrepreneur_id',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where(
                                    fn ($query) => $query->whereYear('created_at', now()->year)
                                ),
                            ),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('_doc_number')
                                    ->label('Número de Documento')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'document_number')),
                                Forms\Components\Placeholder::make('_full_name')
                                    ->label('Nombre Completo')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'full_name')),
                                Forms\Components\Placeholder::make('_business_name')
                                    ->label('Nombre del Emprendimiento')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'business.business_name')),
                                Forms\Components\Placeholder::make('_business_description')
                                    ->label('Descripción del Emprendimiento')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'business.description')),
                                Forms\Components\Placeholder::make('_gender')
                                    ->label('Género')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'gender.name')),
                                Forms\Components\Placeholder::make('_entrepreneurship_stage')
                                    ->label('Etapa del Emprendimiento')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'business.entrepreneurshipStage.name')),
                                Forms\Components\Placeholder::make('_marital_status')
                                    ->label('Estado Civil')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'maritalStatus.name')),
                                Forms\Components\Placeholder::make('_education_level')
                                    ->label('Nivel Educativo')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'educationLevel.name')),
                                Forms\Components\Placeholder::make('_phone')
                                    ->label('Teléfono')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'phone')),
                                Forms\Components\Placeholder::make('_city')
                                    ->label('Municipio')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'city.name')),
                                Forms\Components\Placeholder::make('_project')
                                    ->label('Ruta')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'project.name')),
                                Forms\Components\Placeholder::make('_admission_date')
                                    ->label('Fecha de Registro')
                                    ->content(function ($get) {
                                        $id = $get('entrepreneur_id');
                                        if (! $id) return '----';
                                        $date = \App\Models\Entrepreneur::find($id)?->created_at;
                                        return $date ? $date->format('d/m/Y') : '----';
                                    }),
                                Forms\Components\Placeholder::make('_manager')
                                    ->label('Gestor Asignado')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'manager.name')),
                            ]),

                        Forms\Components\Placeholder::make('alerta_historial_caracterizacion')
                            ->label('')
                            ->content(function ($get) {
                                $entrepreneurId = $get('entrepreneur_id');
                                if (! $entrepreneurId) return '';
                                $doc = \App\Models\Entrepreneur::find($entrepreneurId)?->document_number;
                                if (! $doc) return '';
                                $years = \App\Models\Entrepreneur::withoutGlobalScope(\App\Scopes\YearColumnScope::class)
                                    ->where('document_number', $doc)
                                    ->whereYear('created_at', '<', now()->year)
                                    ->selectRaw('YEAR(created_at) as year')
                                    ->distinct()->orderBy('year')->pluck('year')->toArray();
                                if (empty($years)) return '';
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300">'
                                    . '<p class="font-semibold">Emprendedor con historial previo</p>'
                                    . '<p class="mt-0.5">Este emprendedor ya participó en vigencia(s) anterior(es): <strong>' . implode(', ', $years) . '</strong>.</p>'
                                    . '</div>'
                                );
                            })
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('characterization_date')
                            ->label('Fecha de Caracterización')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->displayFormat('d/m/Y')
                            ->native(true),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── b. Información Económica ──────────────────────────────
                Forms\Components\Section::make('Información Económica')
                    ->description('Actividad económica, línea productiva y población')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('_economic_activity')
                                    ->label('Actividad Económica')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'business.economicActivity.name')),
                                Forms\Components\Placeholder::make('_productive_line')
                                    ->label('Línea Productiva')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'business.productiveLine.name')),
                                Forms\Components\Placeholder::make('_population')
                                    ->label('Población Vulnerable')
                                    ->content(fn ($get) => $entrepreneurData($get, '', 'population.name')),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── c. Estado del Emprendimiento ─────────────────────────
                Forms\Components\Section::make('Estado del Emprendimiento')
                    ->description('Estado actual y nivel de madurez del emprendimiento')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('business_current_state')
                                    ->label('Estado actual del negocio')
                                    ->required()
                                    ->options([
                                        'idea'           => 'Idea de negocio',
                                        'en_creacion'    => 'Emprendimiento en creación',
                                        'en_marcha'      => 'En marcha',
                                        'formalizado'    => 'Formalizado',
                                    ])
                                    ->placeholder('Seleccione el estado actual'),

                                Forms\Components\Select::make('maturity_level')
                                    ->label('Nivel de madurez del emprendimiento')
                                    ->required()
                                    ->options([
                                        'idea'          => 'Idea',
                                        'validacion'    => 'Validación',
                                        'crecimiento'   => 'Crecimiento',
                                        'consolidacion' => 'Consolidación',
                                        'escalamiento'  => 'Escalamiento',
                                    ])
                                    ->placeholder('Seleccione el nivel de madurez'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── d. Características del Negocio ───────────────────────
                Forms\Components\Section::make('Características del Negocio')
                    ->description('Información específica sobre el emprendimiento')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('business_type')
                                    ->label('Tipo de Negocio')
                                    ->required()
                                    ->options([
                                        'individual'  => 'Individual',
                                        'associative' => 'Asociativo',
                                    ])
                                    ->placeholder('Seleccione el tipo de negocio'),

                                Forms\Components\Select::make('business_age')
                                    ->label('Antigüedad del Negocio')
                                    ->required()
                                    ->options([
                                        'lt_6_months'    => 'Menos de 6 meses',
                                        'over_6_months'  => 'Más de 6 meses',
                                        'over_12_months' => 'Más de 12 meses',
                                        'over_24_months' => 'Más de 24 meses',
                                    ])
                                    ->placeholder('Seleccione la antigüedad'),
                            ]),

                        Forms\Components\CheckboxList::make('clients')
                            ->label('Mercado')
                            ->helperText('Seleccione todos los tipos de clientes a los que actualmente vende.')
                            ->required()
                            ->options([
                                'consumidor_final'   => 'Personas en general (consumidor final)',
                                'empresas_privadas'  => 'Empresas privadas',
                                'entidades_publicas' => 'Entidades públicas',
                                'emprendedores'      => 'Emprendedores',
                                'comercios'          => 'Comercios (tiendas, supermercados, misceláneas, etc.)',
                                'restaurantes'       => 'Restaurantes, hoteles y cafeterías',
                                'instituciones_edu'  => 'Instituciones educativas',
                                'instituciones_salud'=> 'Instituciones de salud',
                                'ong'                => 'Organizaciones sin ánimo de lucro',
                                'asociaciones'       => 'Asociaciones o cooperativas',
                                'productores_agro'   => 'Productores agropecuarios',
                                'turistas'           => 'Turistas',
                                'internacionales'    => 'Clientes internacionales',
                                'otro'               => 'Otro ¿Cuál?',
                            ])
                            ->columns(2)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('clients_other')
                            ->label('Especifique el otro tipo de cliente')
                            ->required(fn ($get) => in_array('otro', (array) $get('clients')))
                            ->visible(fn ($get) => in_array('otro', (array) $get('clients')))
                            ->columnSpanFull(),

                        Forms\Components\CheckboxList::make('promotion_strategies')
                            ->label('Estrategias de promoción y comercialización')
                            ->helperText('Marque todos los medios que utiliza para dar a conocer o vender sus productos o servicios.')
                            ->required()
                            ->options([
                                'voz_a_voz'          => 'Voz a voz',
                                'referidos'          => 'Referidos',
                                'whatsapp'           => 'WhatsApp',
                                'facebook'           => 'Facebook',
                                'instagram'          => 'Instagram',
                                'tiktok'             => 'TikTok',
                                'youtube'            => 'YouTube',
                                'pagina_web'         => 'Página web',
                                'google_business'    => 'Google Business Profile',
                                'marketplace'        => 'Marketplace',
                                'ecommerce'          => 'Plataformas de comercio electrónico',
                                'ferias'             => 'Ferias y eventos comerciales',
                                'volantes'           => 'Volantes o material impreso',
                                'avisos'             => 'Avisos o letreros',
                                'radio'              => 'Radio',
                                'television'         => 'Televisión',
                                'correo'             => 'Correo electrónico',
                                'llamadas'           => 'Llamadas telefónicas',
                                'alianzas'           => 'Alianzas comerciales',
                                'ninguna'            => 'Ninguna',
                                'otra'               => 'Otra ¿Cuál?',
                            ])
                            ->columns(2)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('promotion_strategies_other')
                            ->label('Especifique la otra estrategia')
                            ->required(fn ($get) => in_array('otra', (array) $get('promotion_strategies')))
                            ->visible(fn ($get) => in_array('otra', (array) $get('promotion_strategies')))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('market_coverage')
                            ->label('Cobertura del mercado')
                            ->helperText('¿Cuál es el alcance actual de su mercado?')
                            ->required()
                            ->options([
                                'local'          => 'Local',
                                'municipal'      => 'Municipal',
                                'regional'       => 'Regional',
                                'nacional'       => 'Nacional',
                                'internacional'  => 'Internacional',
                            ])
                            ->placeholder('Seleccione la cobertura'),

                        Forms\Components\Select::make('average_monthly_sales')
                            ->label('Ventas Mensuales Promedio')
                            ->helperText('Ingrese el promedio aproximado de ventas mensuales del emprendimiento.')
                            ->required()
                            ->options([
                                'lt_500000' => 'Menos de $500.000',
                                '500k_1m'   => '$500.001 — $1.000.000',
                                '1m_2m'     => '$1.001.000 — $2.000.000',
                                '2m_5m'     => '$2.001.000 — $5.000.000',
                                'gt_5m'     => 'Más de $5.001.000',
                            ])
                            ->placeholder('Seleccione rango de ventas'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('direct_jobs')
                                    ->label('Empleos directos generados')
                                    ->helperText('Personas que trabajan de manera permanente y reciben remuneración. Inclúyase si trabaja de forma permanente en el negocio.')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->integer()
                                    ->placeholder('0'),

                                Forms\Components\TextInput::make('indirect_jobs')
                                    ->label('Empleos indirectos generados')
                                    ->helperText('Personas que obtienen ingresos de forma ocasional (transportadores, proveedores, contratistas o personal temporal).')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->integer()
                                    ->placeholder('0'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── e. Formalización y Registros ─────────────────────────
                Forms\Components\Section::make('Formalización y Registros')
                    ->description('Estado de formalización del emprendimiento')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Forms\Components\Toggle::make('has_commercial_registration')
                            ->label('¿Cuenta con Registro Mercantil?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('mercantile_registration_number')
                                    ->label('Número de matrícula mercantil')
                                    ->required(fn ($get) => (bool) $get('has_commercial_registration'))
                                    ->visible(fn ($get) => (bool) $get('has_commercial_registration')),

                                Forms\Components\DatePicker::make('mercantile_registration_expiry')
                                    ->label('Fecha de vencimiento')
                                    ->required(fn ($get) => (bool) $get('has_commercial_registration'))
                                    ->visible(fn ($get) => (bool) $get('has_commercial_registration'))
                                    ->displayFormat('d/m/Y')
                                    ->native(true),
                            ]),

                        Forms\Components\Toggle::make('has_accounting_records')
                            ->label('¿Lleva registros contables?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\Select::make('accounting_method')
                            ->label('¿Cómo lleva los registros contables?')
                            ->required(fn ($get) => (bool) $get('has_accounting_records'))
                            ->visible(fn ($get) => (bool) $get('has_accounting_records'))
                            ->live()
                            ->options([
                                'cuaderno'          => 'Cuaderno',
                                'excel'             => 'Excel',
                                'software_contable' => 'Software contable',
                                'app_movil'         => 'Aplicación móvil',
                                'otro'              => 'Otro ¿Cuál?',
                            ])
                            ->placeholder('Seleccione el método'),

                        Forms\Components\TextInput::make('accounting_method_other')
                            ->label('Especifique el método')
                            ->required(fn ($get) => $get('accounting_method') === 'otro')
                            ->visible(fn ($get) => $get('accounting_method') === 'otro'),

                        Forms\Components\Toggle::make('has_business_bank_account')
                            ->label('¿Cuenta con una cuenta bancaria empresarial?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nombre de la entidad financiera')
                            ->required(fn ($get) => (bool) $get('has_business_bank_account'))
                            ->visible(fn ($get) => (bool) $get('has_business_bank_account')),

                        Forms\Components\Select::make('has_operation_licenses')
                            ->label('¿Cuenta con los permisos o licencias requeridas para operar?')
                            ->required()
                            ->live()
                            ->options([
                                'si'       => 'Sí',
                                'no'       => 'No',
                                'no_aplica'=> 'No aplica',
                            ])
                            ->placeholder('Seleccione una opción'),

                        Forms\Components\Textarea::make('licenses_description')
                            ->label('¿Cuáles permisos o licencias poseen?')
                            ->required(fn ($get) => $get('has_operation_licenses') === 'si')
                            ->visible(fn ($get) => $get('has_operation_licenses') === 'si')
                            ->rows(3),

                        Forms\Components\Toggle::make('family_in_drummond')
                            ->label('¿Tiene familiares trabajando en Drummond?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\Select::make('drummond_family_relationship')
                            ->label('Parentesco')
                            ->required(fn ($get) => (bool) $get('family_in_drummond'))
                            ->visible(fn ($get) => (bool) $get('family_in_drummond'))
                            ->options([
                                'padre'    => 'Padre',
                                'madre'    => 'Madre',
                                'hermano'  => 'Hermano(a)',
                                'hijo'     => 'Hijo(a)',
                                'conyuge'  => 'Cónyuge',
                                'otro'     => 'Otro',
                            ])
                            ->placeholder('Seleccione el parentesco'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── f. Infraestructura y Georreferenciación ──────────────
                Forms\Components\Section::make('Infraestructura y Georreferenciación')
                    ->description('Lugar de operación y ubicación exacta del emprendimiento')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Select::make('activity_location')
                            ->label('Lugar donde desarrolla su actividad económica')
                            ->required()
                            ->options([
                                'en_mi_vivienda'         => 'En mi vivienda',
                                'local_propio'           => 'Local propio',
                                'local_arrendado'        => 'Local arrendado',
                                'espacio_compartido'     => 'Espacio compartido',
                                'sin_establecimiento'    => 'Sin establecimiento fijo',
                                'otro'                   => 'Otro',
                            ])
                            ->placeholder('Seleccione el lugar'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitud')
                                    ->inputMode('decimal')
                                    ->step(0.00000001)
                                    ->required()
                                    ->placeholder('Ej: 7.1193')
                                    ->helperText('Coordenada de latitud GPS')
                                    ->rules(['numeric', 'between:-90,90']),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitud')
                                    ->inputMode('decimal')
                                    ->required()
                                    ->step(0.00000001)
                                    ->prefix('-')
                                    ->placeholder('73.1227')
                                    ->helperText('Coordenada de longitud GPS (negativa en Colombia)')
                                    ->afterStateHydrated(fn ($component, $state) =>
                                        $component->state($state !== null ? abs((float) $state) : null)
                                    )
                                    ->dehydrateStateUsing(fn ($state) =>
                                        $state !== null && $state !== '' ? -abs((float) $state) : null
                                    )
                                    ->rules(['numeric', 'between:0,180']),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── g. Impacto Social ────────────────────────────────────
                Forms\Components\Section::make('Impacto Social')
                    ->description('Alcance social del emprendimiento')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('economic_dependents')
                                    ->label('¿Cuántas personas dependen económicamente de este emprendimiento?')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->integer()
                                    ->placeholder('0'),

                                Forms\Components\TextInput::make('benefited_families')
                                    ->label('¿Cuántas familias se benefician directamente del emprendimiento?')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->integer()
                                    ->placeholder('0'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── h. Información Financiera ────────────────────────────
                Forms\Components\Section::make('Información Financiera')
                    ->description('Situación financiera del emprendimiento')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('monthly_costs')
                                    ->label('Costos mensuales estimados')
                                    ->required()
                                    ->inputMode('numeric')
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0')
                                    ->afterStateHydrated(fn ($component, $state) => $component->state($state !== null ? (int) $state : null))
                                    ->extraInputAttributes(['oninput' => "this.value=this.value.replace(/[^0-9]/g,'')"])
                                    ->rules(['integer', 'min:0']),

                                Forms\Components\TextInput::make('monthly_expenses')
                                    ->label('Gastos mensuales estimados')
                                    ->required()
                                    ->inputMode('numeric')
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->placeholder('0')
                                    ->afterStateHydrated(fn ($component, $state) => $component->state($state !== null ? (int) $state : null))
                                    ->extraInputAttributes(['oninput' => "this.value=this.value.replace(/[^0-9]/g,'')"])
                                    ->rules(['integer', 'min:0']),

                                Forms\Components\TextInput::make('monthly_profit')
                                    ->label('Utilidad mensual estimada')
                                    ->required()
                                    ->inputMode('numeric')
                                    ->prefix('$')
                                    ->placeholder('0')
                                    ->afterStateHydrated(fn ($component, $state) => $component->state($state !== null ? (int) $state : null))
                                    ->extraInputAttributes(['oninput' => "this.value=this.value.replace(/[^0-9]/g,'')"])
                                    ->rules(['integer', 'min:0']),
                            ]),

                        Forms\Components\Toggle::make('has_active_credits')
                            ->label('¿Tiene créditos vigentes?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('credit_entity')
                                    ->label('Entidad financiera')
                                    ->required(fn ($get) => (bool) $get('has_active_credits'))
                                    ->visible(fn ($get) => (bool) $get('has_active_credits')),

                                Forms\Components\TextInput::make('credit_amount')
                                    ->label('Valor del crédito aprobado')
                                    ->required(fn ($get) => (bool) $get('has_active_credits'))
                                    ->visible(fn ($get) => (bool) $get('has_active_credits'))
                                    ->inputMode('numeric')
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->afterStateHydrated(fn ($component, $state) => $component->state($state !== null ? (int) $state : null))
                                    ->extraInputAttributes(['oninput' => "this.value=this.value.replace(/[^0-9]/g,'')"])
                                    ->rules(['integer', 'min:0']),
                            ]),

                        Forms\Components\Toggle::make('has_family_employees')
                            ->label('¿Tiene familiares contratados?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\TextInput::make('family_employees_count')
                            ->label('¿Cuántos?')
                            ->required(fn ($get) => (bool) $get('has_family_employees'))
                            ->visible(fn ($get) => (bool) $get('has_family_employees'))
                            ->numeric()
                            ->minValue(0)
                            ->integer(),

                        Forms\Components\Toggle::make('hires_women')
                            ->label('¿Contrata mujeres?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\TextInput::make('women_employees_count')
                            ->label('¿Cuántas mujeres trabajan actualmente?')
                            ->required(fn ($get) => (bool) $get('hires_women'))
                            ->visible(fn ($get) => (bool) $get('hires_women'))
                            ->numeric()
                            ->minValue(0)
                            ->integer(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── j. Producción y Operación ────────────────────────────
                Forms\Components\Section::make('Producción y Operación')
                    ->description('Capacidad operativa del emprendimiento')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Forms\Components\TextInput::make('monthly_production_capacity')
                            ->label('Capacidad de producción mensual')
                            ->required()
                            ->placeholder('Ej: 500 unidades, 200 kg, 50 servicios...'),

                        Forms\Components\Textarea::make('equipment_and_tools')
                            ->label('Equipos y herramientas disponibles')
                            ->required()
                            ->rows(3)
                            ->placeholder('Liste los principales equipos y herramientas con que cuenta el emprendimiento.'),

                        Forms\Components\Textarea::make('main_suppliers')
                            ->label('Principales proveedores')
                            ->required()
                            ->rows(3)
                            ->placeholder('Mencione los principales proveedores de materias primas o insumos.'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── k. Innovación y Tecnología ───────────────────────────
                Forms\Components\Section::make('Innovación y Tecnología')
                    ->description('Capacidad tecnológica e innovación del emprendimiento')
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Forms\Components\Select::make('tech_capacity_level')
                            ->label('Nivel de capacidad tecnológica')
                            ->required()
                            ->options([
                                'baja'  => 'Baja',
                                'media' => 'Media',
                                'alta'  => 'Alta',
                            ])
                            ->placeholder('Seleccione el nivel'),

                        Forms\Components\Toggle::make('has_innovation')
                            ->label('¿Ha implementado componentes de innovación?')
                            ->live()
                            ->inline(false)
                            ->onColor('success')->offColor('gray'),

                        Forms\Components\Textarea::make('innovation_description')
                            ->label('Describa cuáles')
                            ->required(fn ($get) => (bool) $get('has_innovation'))
                            ->visible(fn ($get) => (bool) $get('has_innovation'))
                            ->rows(3),

                        Forms\Components\CheckboxList::make('digital_tools')
                            ->label('¿Ha implementado herramientas de transformación digital?')
                            ->helperText('Seleccione todas las que apliquen.')
                            ->options([
                                'redes_sociales'     => 'Redes sociales',
                                'ecommerce'          => 'Comercio electrónico',
                                'facturacion_elec'   => 'Facturación electrónica',
                                'software_admin'     => 'Software administrativo',
                                'crm'                => 'CRM',
                                'ia'                 => 'Inteligencia Artificial',
                                'otro'               => 'Otro',
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── l. Diagnóstico del Emprendimiento ────────────────────
                Forms\Components\Section::make('Diagnóstico del Emprendimiento')
                    ->description('Dificultades y necesidades de fortalecimiento identificadas')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Forms\Components\Textarea::make('main_difficulties')
                            ->label('Principales dificultades identificadas')
                            ->required()
                            ->rows(4)
                            ->placeholder('Describa las principales dificultades que enfrenta el emprendimiento.'),

                        Forms\Components\CheckboxList::make('strengthening_needs')
                            ->label('Necesidades de fortalecimiento')
                            ->helperText('Seleccione todas las que apliquen.')
                            ->required()
                            ->options([
                                'comercial'            => 'Comercial',
                                'financiero'           => 'Financiero',
                                'administrativo'       => 'Administrativo',
                                'contable'             => 'Contable',
                                'produccion'           => 'Producción',
                                'tecnologia'           => 'Tecnología',
                                'marketing'            => 'Marketing',
                                'formalizacion'        => 'Formalización',
                                'innovacion'           => 'Innovación',
                                'acceso_financiacion'  => 'Acceso a financiación',
                                'talento_humano'       => 'Talento humano',
                                'otro'                 => 'Otro',
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── Evidencias Fotográficas ──────────────────────────────
                Forms\Components\Section::make('Evidencias Fotográficas')
                    ->description('Documentos y fotografías de soporte')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Forms\Components\FileUpload::make('commerce_evidence_path')
                            ->label('Evidencia del Comercio')
                            ->directory('characterizations/commerce')
                            ->disk('public')
                            ->multiple()
                            ->maxSize(5120)
                            ->downloadable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->helperText('Fotografías del establecimiento o documento (máximo 5MB)'),

                        Forms\Components\FileUpload::make('population_evidence_path')
                            ->label('Evidencia de Población Vulnerable')
                            ->directory('characterizations/population')
                            ->disk('public')
                            ->multiple()
                            ->maxSize(5120)
                            ->downloadable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                            ->helperText('Documentos que certifican la condición de población vulnerable (máximo 5MB)'),

                        Forms\Components\FileUpload::make('photo_evidence_path')
                            ->label('Fotografía Georeferenciación')
                            ->directory('characterizations/georeference')
                            ->disk('public')
                            ->multiple()
                            ->required()
                            ->maxSize(5120)
                            ->downloadable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                            ->helperText('Foto de la ubicación exacta del emprendimiento (máximo 5MB)'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // ─── m. Habeas Data ───────────────────────────────────────
                Forms\Components\Section::make('Habeas Data')
                    ->description('Autorización para el tratamiento de datos personales')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Checkbox::make('habeas_data_accepted')
                            ->label('Autorizo el tratamiento de mis datos personales de conformidad con la Ley 1581 de 2012 y demás normas que la modifiquen o sustituyan. Declaro que la información suministrada es veraz y autorizo su recolección, almacenamiento, uso, actualización y tratamiento por parte de la entidad ejecutora del proyecto y sus aliados, exclusivamente para fines relacionados con la ejecución, seguimiento, evaluación, generación de indicadores, elaboración de informes y demás actividades propias del programa de emprendimiento, de acuerdo con la Política de Tratamiento de Datos Personales vigente.')
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe aceptar la autorización de tratamiento de datos para continuar.',
                                'accepted' => 'Debe aceptar la autorización de tratamiento de datos para continuar.',
                            ])
                            ->rules(['accepted']),
                    ])
                    ->collapsible(false),

                Forms\Components\Hidden::make('manager_id')
                    ->default(auth()->id()),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                return $query->withTrashed();
            })
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

                Tables\Columns\TextColumn::make('characterization_date')
                    ->label('Fecha Caracterización')
                    ->date('d/m/Y')
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
                    ->visible(fn () => static::userCanList()),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar emprendedor')
                    ->visible(
                        fn ($record) => ! $record->trashed() &&
                            static::userCanEdit() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(
                        fn ($record) => ! $record->trashed() &&
                            static::userCanDelete() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar caracterización')
                    ->visible(fn ($record) => $record->trashed() && static::userCanDelete()),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Eliminar permanentemente')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar permanentemente?')
                    ->modalDescription('Esta acción NO se puede deshacer.')
                    ->visible(fn () => auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        ExcelExport::make()
                            ->withFilename(fn () => 'caracterizaciones-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'entrepreneur.business.economicActivity',
                                'entrepreneur.population',
                                'entrepreneur.city',
                                'entrepreneur.manager',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN DEL EMPRENDEDOR ===
                                Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                Column::make('entrepreneur.city.name')->heading('Municipio'),
                                Column::make('entrepreneur.manager.name')->heading('Gestor'),
                                Column::make('characterization_date')->heading('Fecha Caracterización'),

                                // === INFORMACIÓN ECONÓMICA ===
                                Column::make('entrepreneur.business.economicActivity.name')->heading('Actividad Económica'),
                                Column::make('entrepreneur.population.name')->heading('Población Vulnerable'),

                                // === CARACTERÍSTICAS DEL NEGOCIO ===
                                Column::make('business_type')->heading('Tipo de Negocio')->formatStateUsing(fn ($state) => match ($state) {
                                    'individual' => 'Individual',
                                    'associative' => 'Asociativo',
                                    default => $state,
                                }),
                                Column::make('business_age')->heading('Antigüedad del Negocio')->formatStateUsing(fn ($state) => match ($state) {
                                    'over_6_months' => 'Más de 6 meses',
                                    'over_12_months' => 'Más de 12 meses',
                                    'over_24_months' => 'Más de 24 meses',
                                    default => $state,
                                }),
                                Column::make('clients')->heading('Clientela')->formatStateUsing(function ($state) {
                                    if (! $state) {
                                        return '';
                                    }
                                    $options = [
                                        'community' => 'Comunidad en general',
                                        'public_entities' => 'Entidades públicas',
                                        'private_entities' => 'Entidades privadas',
                                        'schools' => 'Colegios',
                                        'hospitals' => 'Hospitales',
                                    ];

                                    return collect($state)->map(fn ($key) => $options[$key] ?? $key)->join(', ');
                                }),
                                Column::make('promotion_strategies')->heading('Estrategias de Promoción')->formatStateUsing(function ($state) {
                                    if (! $state) {
                                        return '';
                                    }
                                    $options = [
                                        'word_of_mouth' => 'Voz a voz',
                                        'whatsapp' => 'WhatsApp',
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                    ];

                                    return collect($state)->map(fn ($key) => $options[$key] ?? $key)->join(', ');
                                }),
                                Column::make('average_monthly_sales')->heading('Ventas Mensuales Promedio')->formatStateUsing(fn ($state) => match ($state) {
                                    'lt_500000' => 'Menos de $500.000',
                                    '500k_1m' => '$500.001 — $1.000.000',
                                    '1m_2m' => '$1.001.000 — $2.000.000',
                                    '2m_5m' => '$2.001.000 — $5.000.000',
                                    'gt_5m' => 'Más de $5.001.000',
                                    default => $state,
                                }),
                                Column::make('employees_generated')->heading('Empleos Generados')->formatStateUsing(fn ($state) => match ($state) {
                                    'up_to_2' => 'Hasta 2 empleados',
                                    '3_to_4' => '3 a 4 empleados',
                                    'more_than_5' => 'Más de 5 empleados',
                                    default => $state,
                                }),

                                // === FORMALIZACIÓN Y REGISTROS ===
                                Column::make('has_accounting_records')->heading('Registros Contables')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                Column::make('has_commercial_registration')->heading('Registro Mercantil')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                Column::make('family_in_drummond')->heading('Familiar en Drummond')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),

                                // === GEORREFERENCIACIÓN ===
                                Column::make('latitude')->heading('Latitud'),
                                Column::make('longitude')->heading('Longitud'),

                                // === EVIDENCIAS FOTOGRÁFICAS ===
                                Column::make('commerce_evidence_path')->heading('Evidencia del Comercio')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('population_evidence_path')->heading('Evidencia de Población')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                Column::make('photo_evidence_path')->heading('Foto Georeferenciación')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
                            ]),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),

                Tables\Actions\Action::make('download_evidences')
                    ->label('Descargar Evidencias')
                    ->icon('heroicon-o-photo')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Descargar Evidencias Fotográficas')
                    ->modalDescription('Se descargará un archivo ZIP con todas las evidencias organizadas por emprendedor. Este proceso puede tardar varios minutos dependiendo de la cantidad de archivos.')
                    ->modalSubmitActionLabel('Descargar ZIP')
                    ->action(function () {
                        return static::downloadAllEvidences();
                    })
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(fn () => 'caracterizaciones-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with([
                                    'entrepreneur.business.economicActivity',
                                    'entrepreneur.population',
                                    'entrepreneur.city',
                                    'entrepreneur.manager',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === INFORMACIÓN DEL EMPRENDEDOR ===
                                    Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                    Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                    Column::make('entrepreneur.city.name')->heading('Municipio'),
                                    Column::make('entrepreneur.manager.name')->heading('Gestor'),
                                    Column::make('characterization_date')->heading('Fecha Caracterización'),

                                    // === INFORMACIÓN ECONÓMICA ===
                                    Column::make('entrepreneur.business.economicActivity.name')->heading('Actividad Económica'),
                                    Column::make('entrepreneur.population.name')->heading('Población Vulnerable'),

                                    // === CARACTERÍSTICAS DEL NEGOCIO ===
                                    Column::make('business_type')->heading('Tipo de Negocio')->formatStateUsing(fn ($state) => match ($state) {
                                        'individual' => 'Individual',
                                        'associative' => 'Asociativo',
                                        default => $state,
                                    }),
                                    Column::make('business_age')->heading('Antigüedad del Negocio')->formatStateUsing(fn ($state) => match ($state) {
                                        'over_6_months' => 'Más de 6 meses',
                                        'over_12_months' => 'Más de 12 meses',
                                        'over_24_months' => 'Más de 24 meses',
                                        default => $state,
                                    }),
                                    Column::make('clients')->heading('Clientela')->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return '';
                                        }
                                        $options = [
                                            'community' => 'Comunidad en general',
                                            'public_entities' => 'Entidades públicas',
                                            'private_entities' => 'Entidades privadas',
                                            'schools' => 'Colegios',
                                            'hospitals' => 'Hospitales',
                                        ];

                                        return collect($state)->map(fn ($key) => $options[$key] ?? $key)->join(', ');
                                    }),
                                    Column::make('promotion_strategies')->heading('Estrategias de Promoción')->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return '';
                                        }
                                        $options = [
                                            'word_of_mouth' => 'Voz a voz',
                                            'whatsapp' => 'WhatsApp',
                                            'facebook' => 'Facebook',
                                            'instagram' => 'Instagram',
                                        ];

                                        return collect($state)->map(fn ($key) => $options[$key] ?? $key)->join(', ');
                                    }),
                                    Column::make('average_monthly_sales')->heading('Ventas Mensuales Promedio')->formatStateUsing(fn ($state) => match ($state) {
                                        'lt_500000' => 'Menos de $500.000',
                                        '500k_1m' => '$500.001 — $1.000.000',
                                        '1m_2m' => '$1.001.000 — $2.000.000',
                                        '2m_5m' => '$2.001.000 — $5.000.000',
                                        'gt_5m' => 'Más de $5.001.000',
                                        default => $state,
                                    }),
                                    Column::make('employees_generated')->heading('Empleos Generados')->formatStateUsing(fn ($state) => match ($state) {
                                        'up_to_2' => 'Hasta 2 empleados',
                                        '3_to_4' => '3 a 4 empleados',
                                        'more_than_5' => 'Más de 5 empleados',
                                        default => $state,
                                    }),

                                    // === FORMALIZACIÓN Y REGISTROS ===
                                    Column::make('has_accounting_records')->heading('Registros Contables')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                    Column::make('has_commercial_registration')->heading('Registro Mercantil')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                    Column::make('family_in_drummond')->heading('Familiar en Drummond')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),

                                    // === GEORREFERENCIACIÓN ===
                                    Column::make('latitude')->heading('Latitud'),
                                    Column::make('longitude')->heading('Longitud'),

                                    // === EVIDENCIAS FOTOGRÁFICAS ===
                                    Column::make('commerce_evidence_path')->heading('Evidencia del Comercio')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('population_evidence_path')->heading('Evidencia de Población')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),
                                    Column::make('photo_evidence_path')->heading('Foto Georeferenciación')->formatStateUsing(fn ($state) => ! empty($state) ? 'Sí' : 'No'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Admin')),
                ]),
            ])
            // Modificar query para incluir registros eliminados cuando sea necesario
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
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
            'index' => Pages\ListCharacterizations::route('/'),
            'create' => Pages\CreateCharacterization::route('/create'),
            'edit' => Pages\EditCharacterization::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Si no es admin, filtrar solo sus registros
        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }

    /**
     * Descargar todas las evidencias en un archivo ZIP
     */
    public static function downloadAllEvidences()
    {
        try {
            // Configuración de memoria y tiempo
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', '600'); // 10 minutos
            set_time_limit(600);

            $zipFileName = 'evidencias_caracterizaciones_'.now()->format('Y-m-d_His').'.zip';
            $zipFilePath = storage_path('app/temp/'.$zipFileName);

            // Crear directorio temporal
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Crear archivo ZIP
            $zip = new \ZipArchive;

            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }

            $filesAdded = 0;

            // ✅ PROCESAR EN CHUNKS MUY PEQUEÑOS (5 registros)
            Characterization::query()
                ->whereNotNull('entrepreneur_id')
                ->select(['id', 'entrepreneur_id', 'commerce_evidence_path', 'population_evidence_path', 'photo_evidence_path'])
                ->chunk(5, function ($characterizations) use ($zip, &$filesAdded) {

                    foreach ($characterizations as $characterization) {
                        // Cargar entrepreneur solo cuando se necesite
                        $entrepreneur = \App\Models\Entrepreneur::select(['id', 'full_name'])
                            ->with(['business:id,entrepreneur_id,business_name'])
                            ->find($characterization->entrepreneur_id);

                        if (! $entrepreneur) {
                            continue;
                        }

                        // Nombre de la carpeta
                        $folderName = self::sanitizeFileName(
                            $entrepreneur->full_name.'_'.($entrepreneur->business->business_name ?? 'Sin_Emprendimiento')
                        );

                        // Array con los campos de archivos
                        $fileFields = [
                            'commerce_evidence_path' => 'Evidencia_Comercio',
                            'population_evidence_path' => 'Evidencia_Poblacion',
                            'photo_evidence_path' => 'Foto_Georeferenciacion',
                        ];

                        foreach ($fileFields as $field => $prefix) {
                            $filePaths = $characterization->$field;

                            if (empty($filePaths)) {
                                continue;
                            }

                            // Convertir a array si es string
                            if (is_string($filePaths)) {
                                $filePaths = json_decode($filePaths, true) ?? [$filePaths];
                            }

                            if (! is_array($filePaths)) {
                                continue;
                            }

                            foreach ($filePaths as $index => $filePath) {
                                if (empty($filePath)) {
                                    continue;
                                }

                                $fullPath = storage_path('app/public/'.$filePath);

                                if (! file_exists($fullPath)) {
                                    continue;
                                }

                                // Verificar tamaño del archivo (skip si es mayor a 10MB)
                                $fileSize = filesize($fullPath);
                                if ($fileSize === false || $fileSize > 10485760) { // 10MB
                                    \Log::warning("Archivo muy grande o inaccesible: {$filePath}");

                                    continue;
                                }

                                $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                                $fileNumber = count($filePaths) > 1 ? '_'.($index + 1) : '';
                                $zipPath = $folderName.'/'.$prefix.$fileNumber.'.'.$extension;

                                if ($zip->addFile($fullPath, $zipPath)) {
                                    $filesAdded++;
                                }
                            }
                        }
                    }

                    // Liberar memoria agresivamente
                    unset($characterizations);
                    gc_collect_cycles();
                });

            $zip->close();

            if ($filesAdded === 0) {
                @unlink($zipFilePath);
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin evidencias')
                    ->body('No se encontraron evidencias fotográficas para descargar.')
                    ->send();

                return null;
            }

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Descarga iniciada')
                ->body("Se han empaquetado {$filesAdded} archivos en el ZIP.")
                ->send();

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar ZIP')
                ->body('Ocurrió un error: '.$e->getMessage())
                ->send();

            \Log::error('Error descargando evidencias: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Sanitizar nombre de archivo/carpeta
     */
    private static function sanitizeFileName(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        return substr($name, 0, 100);
    }
}
