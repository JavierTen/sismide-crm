<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessPlanResource\Pages;
use App\Models\BusinessPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class BusinessPlanResource extends Resource
{
    protected static ?string $model = BusinessPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Plan de Negocio';

    protected static ?string $pluralModelLabel = 'Planes de Negocio';

    protected static ?int $navigationSort = 5;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('listBusinessPlans');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('createBusinessPlan');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('editBusinessPlan');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->can('deleteBusinessPlan');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->unique(table: 'business_plans', column: 'entrepreneur_id', ignoreRecord: true),

                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('business_name')
                                    ->label('Emprendimiento')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (! $entrepreneurId) {
                                            return '----';
                                        }

                                        $entrepreneur = \App\Models\Entrepreneur::with('business')->find($entrepreneurId);

                                        return $entrepreneur?->business?->business_name ?? 'Sin emprendimiento';
                                    }),

                                Forms\Components\Placeholder::make('city_name')
                                    ->label('Municipio')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (! $entrepreneurId) {
                                            return '----';
                                        }

                                        $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);

                                        return $entrepreneur?->city?->name ?? 'Sin ubicación';
                                    }),

                                Forms\Components\Placeholder::make('manager_name')
                                    ->label('Gestor Asignado')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (! $entrepreneurId) {
                                            return '----';
                                        }

                                        $entrepreneur = \App\Models\Entrepreneur::with('manager')->find($entrepreneurId);

                                        return $entrepreneur?->manager?->name ?? 'Sin gestor asignado';
                                    }),

                                Forms\Components\Placeholder::make('productive_line_name')
                                    ->label('Línea Productiva')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (! $entrepreneurId) {
                                            return '----';
                                        }

                                        $entrepreneur = \App\Models\Entrepreneur::with('business.productiveLine')->find($entrepreneurId);

                                        return $entrepreneur?->business?->productiveLine?->name ?? 'Sin línea productiva';
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('creation_date')
                                    ->label('Fecha de Creación del Plan')
                                    ->required()
                                    ->maxDate(now())
                                    ->displayFormat('d/m/Y')
                                    ->native(true)
                                    ->helperText('Fecha en la que se elaboró el plan de negocio'),


                                Forms\Components\Toggle::make('is_prioritized')
                                    ->label('Priorizado')
                                    ->helperText('¿Este emprendedor está priorizado para sustentación?')
                                    ->required()
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray')
                                    ->default(false),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Definición del Negocio')
                    ->description('Información estratégica del emprendimiento')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Textarea::make('business_definition')
                            ->label('Definición del Negocio')
                            ->rows(4)
                            ->placeholder('Describe brevemente el negocio, su actividad principal y propósito...')
                            ->helperText('Descripción clara y concisa del negocio')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('problems_to_solve')
                            ->label('Problemas que Propone Resolver')
                            ->rows(4)
                            ->placeholder('¿Qué problemas o necesidades del mercado resuelve este emprendimiento?')
                            ->helperText('Identifica los problemas específicos que aborda tu negocio')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('mission')
                                    ->label('Misión')
                                    ->rows(5)
                                    ->placeholder('¿Cuál es la razón de ser del negocio?')
                                    ->helperText('Propósito fundamental del emprendimiento'),

                                Forms\Components\Textarea::make('vision')
                                    ->label('Visión')
                                    ->rows(5)
                                    ->placeholder('¿Dónde quiere estar el negocio en el futuro?')
                                    ->helperText('Proyección a largo plazo del emprendimiento'),
                            ]),

                        Forms\Components\Textarea::make('value_proposition')
                            ->label('Propuesta de Valor')
                            ->rows(4)
                            ->placeholder('¿Qué hace único y valioso a este emprendimiento?')
                            ->helperText('Beneficios diferenciadores que ofrece el negocio')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Capitalización del Emprendimiento')
                    ->description('Estado de capitalización del negocio')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_capitalized')
                                    ->label('¿Emprendimiento Capitalizado?')
                                    ->helperText('¿Ha recibido inversión o capital de terceros?')
                                    ->required()
                                    ->live()
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Select::make('capitalization_year')
                                    ->label('Año de Capitalización')
                                    ->options(\App\Models\BusinessPlan::capitalizationYearOptions())
                                    ->placeholder('Seleccione el año')
                                    ->helperText('Año en el que recibió la capitalización')
                                    ->visible(fn ($get) => $get('is_capitalized') === true)
                                    ->required(fn ($get) => $get('is_capitalized') === true),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Requerimientos y Necesidades')
                    ->description('Necesidades identificadas para el desarrollo del negocio')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Textarea::make('requirements_needs')
                            ->label('Requerimientos / Necesidades')
                            ->rows(6)
                            ->placeholder('Detalla las necesidades de recursos, capacitación, infraestructura, tecnología, etc.')
                            ->helperText('Describe todos los requerimientos para el crecimiento del negocio')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Ventas y Producción')
                    ->description('Información sobre volumen de ventas y producción')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('monthly_sales_cop')
                                    ->label('Volumen de Ventas Mensual (COP)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->helperText('Ventas mensuales en pesos colombianos')
                                    ->maxValue(999999999999.99)
                                    ->rules(['numeric', 'min:0', 'max:999999999999.99']),

                                Forms\Components\TextInput::make('monthly_sales_units')
                                    ->label('Volumen de Ventas Mensual (Unidades)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->helperText('Cantidad de unidades vendidas por mes')
                                    ->maxValue(2147483647)
                                    ->rules(['integer', 'min:0', 'max:2147483647']),

                                Forms\Components\Select::make('production_frequency')
                                    ->label('Frecuencia de Producción')
                                    ->options(\App\Models\BusinessPlan::productionFrequencyOptions())
                                    ->placeholder('Seleccione la frecuencia')
                                    ->helperText('¿Con qué frecuencia se produce?'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Indicadores Financieros')
                    ->description('Métricas de rentabilidad y proyecciones')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('gross_profitability_rate')
                                    ->label('Tasa de Rentabilidad Bruta (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->placeholder('0.00')
                                    ->helperText('Rentabilidad bruta en porcentaje')
                                    ->maxLength(5)
                                    ->rules(['numeric', 'between:0,100']),

                                Forms\Components\TextInput::make('cash_flow_growth_rate')
                                    ->label('Tasa de Crecimiento del Flujo de Caja (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->placeholder('0.00')
                                    ->helperText('Proyección de crecimiento del flujo de caja')
                                    ->maxLength(5)
                                    ->rules(['numeric', 'between:0,100']),

                                Forms\Components\TextInput::make('internal_return_rate')
                                    ->label('Tasa Interna de Retorno - TIR (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->placeholder('0.00')
                                    ->helperText('TIR del proyecto')
                                    ->maxLength(5)
                                    ->rules(['numeric', 'between:0,100']),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Punto de Equilibrio e Inversión')
                    ->description('Análisis del punto de equilibrio y valor de inversión')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('break_even_units')
                                    ->label('Punto de Equilibrio (Unidades)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->helperText('Cantidad de unidades para alcanzar el equilibrio (máx: 2,147,483,647)')
                                    ->maxValue(2147483647)
                                    ->rules(['integer', 'min:0', 'max:2147483647'])
                                    ->validationMessages([
                                        'max' => 'El valor no puede superar 2,147,483,647 unidades.',
                                    ]),

                                Forms\Components\TextInput::make('break_even_cop')
                                    ->label('Punto de Equilibrio (COP)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->helperText('Valor en pesos para alcanzar el equilibrio')
                                    ->maxValue(999999999999.99)
                                    ->rules(['numeric', 'min:0', 'max:999999999999.99'])
                                    ->validationMessages([
                                        'max' => 'El valor no puede superar $999,999,999,999.99',
                                    ]),

                                Forms\Components\TextInput::make('current_investment_value')
                                    ->label('Valor de la Inversión Actual (COP)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->helperText('Capital invertido actualmente en el negocio')
                                    ->maxValue(999999999999.99)
                                    ->rules(['numeric', 'min:0', 'max:999999999999.99'])
                                    ->validationMessages([
                                        'max' => 'El valor no puede superar $999,999,999,999.99',
                                    ]),

                                Forms\Components\TextInput::make('jobs_generated')
                                    ->label('Número de Empleos Generados')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->helperText('Cantidad de empleos directos creados (máx: 2,147,483,647)')
                                    ->maxValue(2147483647)
                                    ->rules(['integer', 'min:0', 'max:2147483647'])
                                    ->validationMessages([
                                        'max' => 'El valor no puede superar 2,147,483,647 empleos.',
                                    ]),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Análisis de Mercado')
                    ->description('Competencia y mercado objetivo')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Textarea::make('direct_competitors')
                            ->label('Competidores Directos')
                            ->rows(4)
                            ->placeholder('Identifica los principales competidores del negocio...')
                            ->helperText('Lista los competidores directos y sus características')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('target_market')
                            ->label('Mercado Objetivo')
                            ->rows(4)
                            ->placeholder('Describe el mercado al que se dirige el emprendimiento...')
                            ->helperText('Define el público objetivo y características del mercado')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Observaciones Adicionales')
                    ->description('Comentarios o información adicional relevante')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->rows(4)
                            ->placeholder('Agrega cualquier observación o comentario adicional...')
                            ->helperText('Información complementaria que consideres relevante')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),


                Forms\Components\Section::make('Documentos y Archivos del Plan de Negocio')
                    ->description('Adjunta todos los documentos requeridos del plan de negocio')
                    ->icon('heroicon-o-document-text')
                    ->schema([

                        Forms\Components\FileUpload::make('acquisition_matrix_path')
                            ->label('Matriz de Adquisición (PDF o XLSX)')
                            ->directory('acquisition-matrices')
                            ->disk('public')
                            ->maxSize(10240) // 10 MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                            ->downloadable()
                            ->openable()
                            ->required()
                            ->helperText('Sube la matriz de adquisición en formato PDF o XLSX (máximo 10MB)')
                            ->validationMessages([
                                'required' => 'La matriz de adquisición es obligatoria.',
                                'max' => 'El archivo no puede superar los 10MB.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('business_model_path')
                            ->label('Modelo de Negocio (PDF)')
                            ->directory('business-models')
                            ->disk('public')
                            ->maxSize(10240) // 10 MB
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable()
                            ->required()
                            ->helperText('Sube el modelo de negocio en formato PDF (máximo 10MB)')
                            ->validationMessages([
                                'required' => 'El modelo de negocio es obligatorio.',
                                'max' => 'El archivo no puede superar los 10MB.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo del Emprendimiento (PNG)')
                            ->directory('logos')
                            ->disk('public')
                            ->image()
                            ->maxSize(5120) // 5 MB
                            ->acceptedFileTypes(['image/png'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '16:9',
                                '4:3',
                            ])
                            ->downloadable()
                            ->openable()
                            ->required()
                            ->helperText('Sube el logo del emprendimiento en formato PNG (máximo 5MB)')
                            ->validationMessages([
                                'required' => 'El logo del emprendimiento es obligatorio.',
                                'max' => 'El archivo no puede superar los 5MB.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('fire_pitch_video_url')
                            ->label('Video de Fire Pitch (Link de YouTube)')
                            ->url()
                            ->required()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Ingresa el enlace del video de Fire Pitch en YouTube')
                            ->rules([
                                'required',
                                'url',
                                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/'
                            ])
                            ->validationMessages([
                                'required' => 'El video de Fire Pitch es obligatorio.',
                                'url' => 'Debe ser una URL válida.',
                                'regex' => 'Debe ser un enlace válido de YouTube.',
                            ])
                            ->columnSpanFull(),

                        // ✅ NUEVO: Video del Ciclo Productivo (Link de YouTube)
                        Forms\Components\TextInput::make('production_cycle_video_url')
                            ->label('Video del Ciclo Productivo (Link de YouTube)')
                            ->url()
                            ->required()
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->helperText('Ingresa el enlace del video del ciclo productivo en YouTube')
                            ->rules([
                                'required',
                                'url',
                                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/'
                            ])
                            ->validationMessages([
                                'required' => 'El video del ciclo productivo es obligatorio.',
                                'url' => 'Debe ser una URL válida.',
                                'regex' => 'Debe ser un enlace válido de YouTube.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('file_info')
                            ->label('Recomendación')
                            ->content('Para reducir el tamaño de archivos PDF, puedes usar herramientas en línea como SmallPDF, ILovePDF o Adobe Acrobat para comprimir documentos.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Campo oculto para manager_id
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
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('Sin emprendimiento'),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin ubicación')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creation_date')
                    ->label('Fecha del Plan')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Sin fecha')
                    ->toggleable(),

                // ✅ NUEVO: Priorizado
                Tables\Columns\IconColumn::make('is_prioritized')
                    ->label('Priorizado')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_capitalized')
                    ->label('Capitalizado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Registrado por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                // ✅ NUEVO: Filtro de priorizados
                Tables\Filters\SelectFilter::make('is_prioritized')
                    ->label('Priorización')
                    ->options([
                        1 => 'Sí priorizado',
                        0 => 'No priorizado',
                    ])
                    ->placeholder('Todos'),

                Tables\Filters\SelectFilter::make('is_capitalized')
                    ->label('Capitalización')
                    ->options([
                        1 => 'Sí capitalizado',
                        0 => 'No capitalizado',
                    ])
                    ->placeholder('Todos'),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->relationship('entrepreneur.city', 'name', function ($query) {
                        return $query->where('status', true)
                            ->whereHas('department', function ($q) {
                                $q->where('status', true);
                            })
                            ->orderBy('name', 'asc');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_business_plan')
                    ->label('Con Plan Adjunto')
                    ->query(fn ($query) => $query->whereNotNull('business_plan_path')),
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
                    ->tooltip('Editar plan de negocio')
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
                    ->tooltip('Restaurar plan de negocio')
                    ->visible(fn ($record) => $record->trashed() && static::userCanDelete()),

                Tables\Actions\ForceDeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Eliminar permanentemente')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar permanentemente?')
                    ->modalDescription('Esta acción NO se puede deshacer y eliminará todos los archivos adjuntos.')
                    ->visible(fn () => auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        ExcelExport::make()
                            ->withFilename(fn () => 'planes-negocio-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'entrepreneur.business.productiveLine',
                                'entrepreneur.city',
                                'entrepreneur.manager',
                                'manager',
                            ]))
                            ->withColumns([
                                // === INFORMACIÓN BÁSICA ===
                                Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                Column::make('entrepreneur.city.name')->heading('Municipio'),
                                Column::make('entrepreneur.business.productiveLine.name')->heading('Línea Productiva'),
                                Column::make('creation_date')->heading('Fecha del Plan')->formatStateUsing(fn ($state) => $state?->format('d/m/Y')),

                                // ✅ NUEVO CAMPO
                                Column::make('is_prioritized')->heading('Priorizado para Sustentar')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),

                                // === DEFINICIÓN DEL NEGOCIO ===
                                Column::make('business_definition')->heading('Definición del Negocio'),
                                Column::make('problems_to_solve')->heading('Problemas a Resolver'),
                                Column::make('mission')->heading('Misión'),
                                Column::make('vision')->heading('Visión'),
                                Column::make('value_proposition')->heading('Propuesta de Valor'),

                                // === CAPITALIZACIÓN ===
                                Column::make('is_capitalized')->heading('Capitalizado')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                Column::make('capitalization_year')->heading('Año de Capitalización')->formatStateUsing(fn ($state) => $state ?? 'N/A'),

                                // === REQUERIMIENTOS ===
                                Column::make('requirements_needs')->heading('Requerimientos/Necesidades'),

                                // === VENTAS Y PRODUCCIÓN ===
                                Column::make('monthly_sales_cop')->heading('Ventas Mensuales (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),
                                Column::make('monthly_sales_units')->heading('Ventas Mensuales (Unidades)'),
                                Column::make('production_frequency')->heading('Frecuencia de Producción')->formatStateUsing(fn ($state) => match ($state) {
                                    'daily' => 'Diaria',
                                    'weekly' => 'Semanal',
                                    'biweekly' => 'Quincenal',
                                    'monthly' => 'Mensual',
                                    'quarterly' => 'Trimestral',
                                    'biannual' => 'Semestral',
                                    'annual' => 'Anual',
                                    default => $state ?? 'N/A',
                                }),

                                // === INDICADORES FINANCIEROS ===
                                Column::make('gross_profitability_rate')->heading('Tasa Rentabilidad Bruta (%)'),
                                Column::make('cash_flow_growth_rate')->heading('Tasa Crecimiento Flujo Caja (%)'),
                                Column::make('internal_return_rate')->heading('TIR (%)'),

                                // === PUNTO DE EQUILIBRIO ===
                                Column::make('break_even_units')->heading('Punto Equilibrio (Unidades)'),
                                Column::make('break_even_cop')->heading('Punto Equilibrio (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),

                                // === INVERSIÓN Y EMPLEOS ===
                                Column::make('current_investment_value')->heading('Inversión Actual (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),
                                Column::make('jobs_generated')->heading('Empleos Generados'),

                                // === MERCADO ===
                                Column::make('direct_competitors')->heading('Competidores Directos'),
                                Column::make('target_market')->heading('Mercado Objetivo'),

                                // === OTROS ===
                                Column::make('observations')->heading('Observaciones'),

                                // ✅ ARCHIVOS ADJUNTOS
                                Column::make('business_plan_path')->heading('Plan de Negocio')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('acquisition_matrix_path')->heading('Matriz de Adquisición')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('business_model_path')->heading('Modelo de Negocio')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('logo_path')->heading('Logo')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('fire_pitch_video_url')->heading('Video Fire Pitch')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                Column::make('production_cycle_video_url')->heading('Video Ciclo Productivo')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),
                                Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
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
                                ->withFilename(fn () => 'planes-negocio-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with([
                                    'entrepreneur.business.productiveLine',
                                    'entrepreneur.city',
                                    'entrepreneur.manager',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // Mismas columnas que el export action
                                    Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                    Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                    Column::make('entrepreneur.city.name')->heading('Municipio'),
                                    Column::make('entrepreneur.business.productiveLine.name')->heading('Línea Productiva'),
                                    Column::make('creation_date')->heading('Fecha del Plan')->formatStateUsing(fn ($state) => $state?->format('d/m/Y')),
                                    Column::make('is_prioritized')->heading('Priorizado para Sustentar')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                    Column::make('business_definition')->heading('Definición del Negocio'),
                                    Column::make('problems_to_solve')->heading('Problemas a Resolver'),
                                    Column::make('mission')->heading('Misión'),
                                    Column::make('vision')->heading('Visión'),
                                    Column::make('value_proposition')->heading('Propuesta de Valor'),
                                    Column::make('is_capitalized')->heading('Capitalizado')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                                    Column::make('capitalization_year')->heading('Año de Capitalización')->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                                    Column::make('requirements_needs')->heading('Requerimientos/Necesidades'),
                                    Column::make('monthly_sales_cop')->heading('Ventas Mensuales (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),
                                    Column::make('monthly_sales_units')->heading('Ventas Mensuales (Unidades)'),
                                    Column::make('production_frequency')->heading('Frecuencia de Producción')->formatStateUsing(fn ($state) => match ($state) {
                                        'daily' => 'Diaria',
                                        'weekly' => 'Semanal',
                                        'biweekly' => 'Quincenal',
                                        'monthly' => 'Mensual',
                                        'quarterly' => 'Trimestral',
                                        'biannual' => 'Semestral',
                                        'annual' => 'Anual',
                                        default => $state ?? 'N/A',
                                    }),
                                    Column::make('gross_profitability_rate')->heading('Tasa Rentabilidad Bruta (%)'),
                                    Column::make('cash_flow_growth_rate')->heading('Tasa Crecimiento Flujo Caja (%)'),
                                    Column::make('internal_return_rate')->heading('TIR (%)'),
                                    Column::make('break_even_units')->heading('Punto Equilibrio (Unidades)'),
                                    Column::make('break_even_cop')->heading('Punto Equilibrio (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),
                                    Column::make('current_investment_value')->heading('Inversión Actual (COP)')->formatStateUsing(fn ($state) => $state ? '$'.number_format($state, 2) : 'N/A'),
                                    Column::make('jobs_generated')->heading('Empleos Generados'),
                                    Column::make('direct_competitors')->heading('Competidores Directos'),
                                    Column::make('target_market')->heading('Mercado Objetivo'),
                                    Column::make('observations')->heading('Observaciones'),
                                    Column::make('business_plan_path')->heading('Plan de Negocio')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('acquisition_matrix_path')->heading('Matriz de Adquisición')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('business_model_path')->heading('Modelo de Negocio')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('logo_path')->heading('Logo')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('fire_pitch_video_url')->heading('Video Fire Pitch')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('production_cycle_video_url')->heading('Video Ciclo Productivo')->formatStateUsing(fn ($state) => !empty($state) ? 'Sí' : 'No'),
                                    Column::make('manager.name')->heading('Registrado por'),
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Admin')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),
                ]),
            ])
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScopes([
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
            'index' => Pages\ListBusinessPlans::route('/'),
            'create' => Pages\CreateBusinessPlan::route('/create'),
            'edit' => Pages\EditBusinessPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->count();
    }
}
