<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessDiagnosisResource\Pages;
use App\Filament\Resources\BusinessDiagnosisResource\RelationManagers;
use App\Models\BusinessDiagnosis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessDiagnosisResource extends Resource
{
    protected static ?string $model = BusinessDiagnosis::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Diagnóstico';
    protected static ?string $pluralModelLabel = 'Diagnósticos';

    protected static ?int $navigationSort = 4;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listBusinessDiagnosis');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createBusinessDiagnosis');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editBusinessDiagnosis');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteBusinessDiagnosis');
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
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Información Básica')
                        ->icon('heroicon-o-identification')
                        ->description('Datos del emprendedor y fecha del diagnóstico')
                        ->schema([
                            Forms\Components\Section::make('Datos del Diagnóstico')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('entrepreneur_id')
                                                ->label('Emprendedor')
                                                ->relationship(
                                                    'entrepreneur',
                                                    'full_name',
                                                    fn($query) => $query->when(
                                                        !auth()->user()->hasRole('Admin'),
                                                        fn($q) => $q->where('manager_id', auth()->id())
                                                    )
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->placeholder('Buscar emprendedor')
                                                ->disabled(fn(string $operation): bool => $operation === 'edit')
                                                ->helperText(
                                                    fn(string $operation): string =>
                                                    $operation === 'edit'
                                                        ? 'El emprendedor asignado no puede ser modificado.'
                                                        : 'Selecciona el emprendedor para realizar el diagnóstico empresarial'
                                                )
                                                ->unique(table: 'business_diagnoses', column: 'entrepreneur_id', ignoreRecord: true),

                                            Forms\Components\DatePicker::make('diagnosis_date')
                                                ->label('Fecha del Diagnóstico')
                                                ->required()
                                                ->default(now())
                                                ->maxDate(now())
                                                ->displayFormat('d/m/Y')
                                                ->native(true),
                                        ]),

                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Placeholder::make('business_name')
                                                ->label('Emprendimiento')
                                                ->content(function ($get) {
                                                    $entrepreneurId = $get('entrepreneur_id');
                                                    if (!$entrepreneurId) return '----';

                                                    $entrepreneur = \App\Models\Entrepreneur::with('business')->find($entrepreneurId);
                                                    return $entrepreneur?->business?->business_name ?? 'Sin emprendimiento';
                                                }),

                                            Forms\Components\Placeholder::make('city_name')
                                                ->label('Municipio')
                                                ->content(function ($get) {
                                                    $entrepreneurId = $get('entrepreneur_id');
                                                    if (!$entrepreneurId) return '----';

                                                    $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);
                                                    return $entrepreneur?->city?->name ?? 'Sin ubicación';
                                                }),

                                            Forms\Components\Placeholder::make('manager_name')
                                                ->label('Gestor')
                                                ->content(function ($get) {
                                                    $entrepreneurId = $get('entrepreneur_id');
                                                    if (!$entrepreneurId) return '----';

                                                    $entrepreneur = \App\Models\Entrepreneur::with('manager')->find($entrepreneurId);
                                                    return $entrepreneur?->manager?->name ?? 'Sin gestor';
                                                }),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Novedades del Emprendimiento')
                                ->schema([
                                    Forms\Components\Toggle::make('has_news')
                                        ->label('¿El Emprendimiento Registra Novedades?')
                                        ->live()
                                        ->onIcon('heroicon-m-check-circle')
                                        ->offIcon('heroicon-m-x-circle')
                                        ->onColor('warning'),

                                    Forms\Components\Select::make('news_type')
                                        ->label('Tipo de Novedad')
                                        ->options([
                                            'reactivation' => 'Reactivación',
                                            'definitive_closure' => 'Cierre de Emprendimiento definitivo',
                                            'temporary_closure' => 'Cierre de Emprendimiento temporal',
                                            'permanent_disability' => 'Incapacidad Permanente',
                                            'temporary_disability' => 'Incapacidad Temporal',
                                            'definitive_retirement' => 'Retiro definitivo',
                                            'temporary_retirement' => 'Retiro temporal',
                                            'address_change' => 'Cambio de domicilio',
                                            'owner_death' => 'Muerte del titular',
                                            'no_news' => 'Sin novedad',
                                        ])
                                        ->visible(fn($get) => $get('has_news'))
                                        ->required(fn($get) => $get('has_news'))
                                        ->placeholder('Seleccione el tipo de novedad'),

                                    Forms\Components\DatePicker::make('news_date')
                                        ->label('Fecha de la Novedad')
                                        ->visible(fn($get) => $get('has_news'))
                                        ->required(fn($get) => $get('has_news'))
                                        ->maxDate(now())
                                        ->displayFormat('d/m/Y')
                                        ->native(true),
                                ]),
                        ]),

                    Forms\Components\Wizard\Step::make('Sección Administrativa')
                        ->icon('heroicon-o-briefcase')
                        ->description('Evaluación de procesos administrativos')
                        ->schema([
                            Forms\Components\Section::make('Preguntas Administrativas')
                                ->schema([
                                    Forms\Components\Radio::make('administrative_section.task_organization')
                                        ->label('1. ¿Cómo organizas las tareas y responsabilidades en tu emprendimiento?')
                                        ->options([
                                            'no_organization' => 'No tengo una organización definida de tareas',
                                            'informal' => 'Organizo tareas de manera informal o verbal',
                                            'basic_list' => 'Tengo una lista básica de tareas y responsabilidades',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('administrative_section.resource_planning')
                                        ->label('2. ¿Cómo planificas las necesidades de recursos para tu negocio?')
                                        ->options([
                                            'no_planning' => 'No realizo planificación de recursos',
                                            'basic_plan' => 'Tengo un plan básico basado en experiencias pasadas',
                                            'planning_tools' => 'Uso herramientas de planificación para prever necesidades futuras',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('administrative_section.communication_channels')
                                        ->label('3. ¿Cómo se manejan los canales de comunicación con clientes y proveedores?')
                                        ->options([
                                            'irregular' => 'La comunicación es irregular y solo cuando es necesario',
                                            'periodic_traditional' => 'Mantengo comunicación periódica a través de métodos tradicionales',
                                            'digital_regular' => 'Utilizo medios digitales para comunicarme regularmente',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('administrative_section.purchase_management')
                                        ->label('4. ¿Cómo se realiza la gestión de compras y adquisiciones?')
                                        ->options([
                                            'basic_tracking' => 'Realizo un seguimiento básico de las necesidades de compra',
                                            'planned_system' => 'Tengo un sistema de gestión de compras planificado',
                                            'advanced_software' => 'Utilizo métodos avanzados y software especializado',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('administrative_section.distribution')
                                        ->label('5. ¿Cómo es la distribución de sus productos o servicios?')
                                        ->options([
                                            'self' => 'Usted mismo',
                                            'outsourced' => 'Subcontrato a un tercero',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                ])
                                ->columns(1),
                        ]),

                    Forms\Components\Wizard\Step::make('Sección Financiera')
                        ->icon('heroicon-o-banknotes')
                        ->description('Evaluación de gestión financiera y contable')
                        ->schema([
                            Forms\Components\Section::make('Preguntas Financieras y Contables')
                                ->schema([
                                    Forms\Components\Radio::make('financial_section.income_expenses_record')
                                        ->label('1. ¿Cómo llevas el registro de tus ingresos y gastos?')
                                        ->options([
                                            'no_record' => 'No llevo registro de ingresos y gastos',
                                            'basic_manual' => 'Llevo un registro básico y manual',
                                            'digital_tools' => 'Uso herramientas digitales básicas',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.last_month_income')
                                        ->label('2. ¿Cuál fue el total de ingresos generados en el último mes?')
                                        ->options([
                                            'lt_500k' => 'Menos de $500.000',
                                            '500k_1m' => 'De $501.000 a $1.000.000',
                                            '1m_2m' => 'De $1.001.000 a $2.000.000',
                                            '2m_5m' => 'De $2.001.000 a $5.000.000',
                                            'gt_5m' => 'Más de $5.000.001'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.quarterly_income')
                                        ->label('3. ¿Cómo han sido los ingresos en el último trimestre?')
                                        ->options([
                                            'positive' => 'Positivo',
                                            'negative' => 'Negativo',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.knows_margin')
                                        ->label('4. ¿Conoces el porcentaje de tu margen de ganancia?')
                                        ->options([
                                            'yes' => 'Sí',
                                            'no' => 'No',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.profit_margin')
                                        ->label('5. ¿Cuál es el margen de ganancia de productos o servicios?')
                                        ->options([
                                            '10_20' => 'Entre el 10% y el 20%',
                                            '20_30' => 'Entre el 20% y 30%',
                                            '30_40' => 'Entre el 30% y el 40%',
                                            '40_50' => 'Entre el 40% y 50%',
                                            'gt_50' => 'Mayor al 50%',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                    Forms\Components\Radio::make('financial_section.external_financing')
                                        ->label('6. ¿Cómo manejas la financiación y búsqueda de recursos externos?')
                                        ->options([
                                            'no_external' => 'No busco financiación externa',
                                            'formal_financing' => 'Busco activamente opciones de financiación formales',
                                            'informal_financing' => 'Busco activamente opciones de financiación informales',
                                            'reinvestment' => 'Reinversión',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.budget_planning')
                                        ->label('7. ¿Cómo planifica su presupuesto financiero?')
                                        ->options([
                                            'irregular_intuitive' => 'Hago presupuestos de manera irregular o intuitiva',
                                            'basic_planning' => 'Realizo una planeación financiera y presupuestación básica',
                                            'established_process' => 'Tengo un proceso establecido de presupuestación y revisión financiera',
                                            'no_planning' => 'No planifico mi presupuesto financiero',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.business_investments')
                                        ->label('8. ¿Cómo manejas las inversiones en tu negocio?')
                                        ->options([
                                            'no_planned' => 'No realizo inversiones planificadas',
                                            'basic_immediate' => 'Realizo inversiones básicas basadas en necesidades inmediatas',
                                            'planned_growth' => 'Planifico y analizo inversiones para el crecimiento del negocio',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.payment_methods')
                                        ->label('9. ¿Qué métodos de pago utiliza frecuentemente?')
                                        ->options([
                                            'digital_transfer' => 'Transferencia digital',
                                            'pos_terminal' => 'Datáfono',
                                            'cash' => 'Efectivo',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.accounts_management')
                                        ->label('10. ¿Cómo se manejan las cuentas por cobrar y por pagar?')
                                        ->options([
                                            'no_specific_record' => 'No llevo un registro específico de cuentas por cobrar/pagar',
                                            'basic_informal' => 'Realizo un seguimiento básico e informal de estas cuentas',
                                            'organized_record' => 'Mantengo un registro organizado de cuentas por cobrar y pagar',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('financial_section.tax_obligations')
                                        ->label('11. ¿Su unidad productiva cumple con sus obligaciones tributarias?')
                                        ->options([
                                            'no_knowledge' => 'No tengo conocimiento claro sobre mis obligaciones tributarias',
                                            'basic_compliance' => 'Cumplo con las obligaciones tributarias básicas',
                                            'planned_review' => 'Planifico y reviso periódicamente mis responsabilidades tributarias',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                ])
                                ->columns(1),
                        ]),

                    Forms\Components\Wizard\Step::make('Sección de Producción')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->description('Evaluación de procesos productivos')
                        ->schema([
                            Forms\Components\Section::make('Preguntas de Producción')
                                ->schema([
                                    Forms\Components\Radio::make('production_section.production_planning')
                                        ->label('1. ¿Cómo planificas y organizas tu proceso de producción?')
                                        ->options([
                                            'no_planning' => 'No tengo una planificación definida',
                                            'basic_demand' => 'Sigo un plan básico basado en la demanda',
                                            'structured_planning' => 'Tengo una planificación estructurada con cierta anticipación',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.quality_control')
                                        ->label('2. ¿Cómo gestionas el control de calidad en tu producción?')
                                        ->options([
                                            'no_quality_control' => 'No tengo procesos de control de calidad',
                                            'sporadic_control' => 'Realizo controles de calidad de manera esporádica y no sistemática',
                                            'documented_standards' => 'Utilizo estándares de control de calidad consistentes y documentados',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.raw_materials_optimization')
                                        ->label('3. ¿Cómo optimizas el uso de materias primas y recursos?')
                                        ->options([
                                            'no_attention' => 'No presto atención específica al uso de materias primas',
                                            'basic_system' => 'Tengo un sistema básico para optimizar el uso de materias primas',
                                            'efficient_management' => 'Realizo una gestión eficiente y planificada de materias primas',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.innovation')
                                        ->label('4. ¿Cómo manejas la innovación en tus procesos productivos?')
                                        ->options([
                                            'has_innovation' => 'Tengo innovación en los productos',
                                            'no_innovation' => 'No tengo innovación en los productos',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.safety_hygiene')
                                        ->label('5. ¿Cómo se gestiona la seguridad e higiene en el proceso productivo?')
                                        ->options([
                                            'no_measures' => 'No tengo medidas específicas de seguridad',
                                            'basic_measures' => 'Aplico medidas de seguridad básicas y generales',
                                            'established_protocols' => 'Tengo protocolos de seguridad establecidos para la producción',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.equipment_maintenance')
                                        ->label('6. ¿Cómo se realiza el mantenimiento de maquinaria y equipos?')
                                        ->options([
                                            'no_plan' => 'No tengo un plan de mantenimiento establecido',
                                            'reactive_maintenance' => 'Realizo mantenimiento sólo cuando surge un problema',
                                            'preventive_calendar' => 'Tengo un calendario básico de mantenimiento preventivo',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('production_section.waste_management')
                                        ->label('7. ¿Cómo se gestionan los residuos y subproductos de la producción?')
                                        ->options([
                                            'no_management' => 'No gestiono de forma específica los residuos',
                                            'basic_compliance' => 'Manejo los residuos de manera básica y conforme a la normativa',
                                            'reduce_recycle' => 'Tengo un sistema para reducir y reciclar algunos residuos',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                ])
                                ->columns(1),
                        ]),

                    Forms\Components\Wizard\Step::make('Sección de Mercado y Comercial')
                        ->icon('heroicon-o-chart-bar')
                        ->description('Evaluación de estrategias comerciales y de mercado')
                        ->schema([
                            Forms\Components\Section::make('Preguntas de Mercado y Comerciales')
                                ->schema([
                                    Forms\Components\Radio::make('market_section.customer_identification')
                                        ->label('1. ¿Cómo identificas a tus clientes potenciales?')
                                        ->options([
                                            'casual_intuitive' => 'Identifico clientes potenciales de manera casual o intuitiva',
                                            'basic_idea' => 'Tengo una idea básica de mi cliente ideal',
                                            'periodic_analysis' => 'Realizo un análisis periódico de mis clientes',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('market_section.market_opportunities')
                                        ->label('2. ¿Cómo identificas nuevas oportunidades de mercado?')
                                        ->options([
                                            'occasional_casual' => 'Identifico oportunidades de manera ocasional y casual',
                                            'basic_research' => 'Realizo una investigación básica del mercado de vez en cuando',
                                            'structured_method' => 'Tengo un método estructurado para identificar oportunidades',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('market_section.market_adaptation')
                                        ->label('3. ¿Cómo adaptas tu oferta a las tendencias del mercado?')
                                        ->options([
                                            'reactive_big_changes' => 'Adapto mi oferta de forma reactiva a cambios grandes del mercado',
                                            'basic_tracking' => 'Mantengo un seguimiento básico de las tendencias del mercado',
                                            'proactive_update' => 'Actualizo proactivamente mi oferta según las tendencias y preferencias del mercado',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('market_section.promotion_advertising')
                                        ->label('4. ¿Cómo manejas la promoción y publicidad de tus productos o servicios?')
                                        ->options([
                                            'no_promotion' => 'No realizo acciones de promoción o publicidad',
                                            'sporadic_traditional' => 'Uso métodos de promoción esporádicos y tradicionales',
                                            'structured_plan' => 'Tengo un plan de promoción y publicidad bien estructurado',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('market_section.competition_analysis')
                                        ->label('5. ¿Cómo analizas la competencia en tu mercado?')
                                        ->options([
                                            'no_analysis' => 'No analizo ni respondo activamente a la competencia',
                                            'basic_awareness' => 'Tengo una conciencia básica de mis competidores',
                                            'regular_tracking' => 'Realizo un seguimiento regular de la competencia y me adapto ocasionalmente',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('market_section.sales_channels')
                                        ->label('6. ¿Cómo exploras y desarrollas nuevos canales de venta?')
                                        ->options([
                                            'no_exploration' => 'No exploro activamente nuevos canales de venta',
                                            'occasional_experiment' => 'Experimento ocasionalmente con diferentes canales de venta',
                                            'strategic_analysis' => 'Analizo y elijo estratégicamente canales de venta adicionales',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                ])
                                ->columns(1),
                        ]),

                    Forms\Components\Wizard\Step::make('Sección Digital y Tecnología')
                        ->icon('heroicon-o-computer-desktop')
                        ->description('Evaluación del uso de tecnología y herramientas digitales')
                        ->schema([
                            Forms\Components\Section::make('Preguntas de Tecnología Digital')
                                ->schema([
                                    Forms\Components\Radio::make('technology_section.technology_usage')
                                        ->label('1. ¿Qué tecnologías empleas en tu producción o gestión?')
                                        ->options([
                                            'no_technology' => 'No utilizo tecnologías específicas',
                                            'basic_traditional' => 'Uso tecnologías muy básicas o tradicionales',
                                            'modern_technology' => 'Empleo algunas tecnologías modernas en mi producción',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('technology_section.data_management')
                                        ->label('2. ¿Cómo gestionas la información y los datos en tu negocio?')
                                        ->options([
                                            'no_management' => 'No gestiono activamente la información y los datos',
                                            'basic_manual' => 'Uso métodos básicos y manuales para manejar datos',
                                            'tech_tools' => 'Utilizo herramientas tecnológicas para la gestión y análisis de datos',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('technology_section.staff_training')
                                        ->label('3. ¿Cómo se realiza la capacitación tecnológica de tu personal?')
                                        ->options([
                                            'no_training' => 'No proporciono capacitación tecnológica a mi personal',
                                            'occasional_unstructured' => 'La capacitación tecnológica es ocasional y no estructurada',
                                            'basic_relevant' => 'Ofrezco capacitación básica en tecnologías relevantes',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('technology_section.office_tools')
                                        ->label('4. ¿Utiliza algunas herramientas ofimáticas para la gestión de su unidad productiva?')
                                        ->options([
                                            'use_office_tools' => 'Aplico herramientas ofimáticas',
                                            'no_office_tools' => 'No aplico herramientas ofimáticas',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),

                                    Forms\Components\Radio::make('technology_section.tech_adaptation')
                                        ->label('5. ¿Cómo se adaptan los procesos de tu negocio a los avances tecnológicos?')
                                        ->options([
                                            'no_adaptation' => 'No adapto mis procesos a los avances tecnológicos',
                                            'occasional_no_strategy' => 'La adaptación a la tecnología es ocasional y sin una estrategia clara',
                                            'basic_adjustments' => 'Hago ajustes básicos en mis procesos para incorporar algunas tecnologías nuevas',
                                            'not_applicable' => 'No lo hago o No Aplica'
                                        ])
                                        ->required(),
                                ])
                                ->columns(1),
                        ]),

                    Forms\Components\Wizard\Step::make('Análisis y Planificación')
                        ->icon('heroicon-o-document-text')
                        ->description('Observaciones y definición del plan de trabajo')
                        ->schema([
                            Forms\Components\Section::make('Observaciones Generales')
                                ->schema([
                                    Forms\Components\Textarea::make('general_observations')
                                        ->label('Observaciones Generales (Descripción detallada, realizar un mini DOFA)')
                                        ->required()
                                        ->rows(6)
                                        ->placeholder('Incluya:\n• Fortalezas identificadas\n• Oportunidades de mejora\n• Debilidades encontradas\n• Amenazas del entorno\n• Recomendaciones específicas')
                                        ->helperText('Desarrolle un análisis DOFA básico del emprendimiento'),
                                ]),

                            Forms\Components\Section::make('Plan de Trabajo')
                                ->schema([
                                    Forms\Components\CheckboxList::make('work_sections')
                                        ->label('Escoger al menos 2 secciones a trabajar con el emprendedor')
                                        ->options([
                                            'administrative' => 'Sección Administrativa',
                                            'financial' => 'Sección Financiera y Contable',
                                            'production' => 'Sección De Producción',
                                            'market' => 'Sección De Mercado y comercial',
                                            'technology' => 'Sección Digital Tecnología',
                                        ])
                                        ->required()
                                        ->minItems(2)
                                        ->maxItems(5)
                                        ->columns(1)
                                        ->helperText('Seleccione mínimo 2 secciones prioritarias para el plan de fortalecimiento'),
                                ]),
                        ]),
                ])
                    ->skippable(false)
                    ->startOnStep(1)
                    ->persistStepInQueryString()
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
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin ubicación'),

                Tables\Columns\TextColumn::make('diagnosis_date')
                    ->label('Fecha Diagnóstico')
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
                    ->visible(fn() => static::userCanList()),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar diagnóstico')
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
                    ->tooltip('Restaurar diagnóstico')
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

        // Ajusta según tu sistema de roles
        if (auth()->user()->hasRole('Admin')) { // o hasRole('admin')
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
            'index' => Pages\ListBusinessDiagnoses::route('/'),
            'create' => Pages\CreateBusinessDiagnosis::route('/create'),
            'edit' => Pages\EditBusinessDiagnosis::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // Si no es admin, filtrar solo sus registros
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
