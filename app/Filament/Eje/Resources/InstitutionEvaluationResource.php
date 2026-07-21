<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\InstitutionEvaluationResource\Pages;
use App\Models\EducationalInstitution;
use App\Models\InstitutionEvaluation;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InstitutionEvaluationResource extends Resource
{
    protected static ?string $model = InstitutionEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Evaluaciones';

    protected static ?string $navigationLabel = 'Instituciones Educativas';

    protected static ?string $modelLabel = 'Evaluación';

    protected static ?string $pluralModelLabel = 'Evaluaciones';

    protected static ?int $navigationSort = 1;

    private static function userCanList(): bool   { return auth()->user()?->can('listInstitutionEvaluations') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createInstitutionEvaluation') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editInstitutionEvaluation') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteInstitutionEvaluation') ?? false; }

    public static function canViewAny(): bool              { return static::userCanList(); }
    public static function canCreate(): bool               { return static::userCanCreate(); }
    public static function canEdit($record): bool          { return static::userCanEdit(); }
    public static function canDelete($record): bool        { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    private static function selectedInstitution(Forms\Get $get): ?EducationalInstitution
    {
        $institutionId = $get('educational_institution_id');

        if (! $institutionId) {
            return null;
        }

        return EducationalInstitution::with(['city', 'manager'])->find($institutionId);
    }

    public static function form(Form $form): Form
    {
        $fileUploadDefaults = fn (Forms\Components\FileUpload $field, string $dir) => $field
            ->directory("institution-evaluations/{$dir}")
            ->disk('public')
            ->maxSize(5120)
            ->multiple()
            ->downloadable()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']);

        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Tabs::make('Registro de Evaluación')
                    ->columnSpanFull()
                    ->tabs([

                        // ── Tab 1: Institución ────────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Institución')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\Select::make('educational_institution_id')
                                    ->label('Institución Educativa')
                                    ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                                        fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                                    ))
                                    ->placeholder('Seleccione la institución')
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->disabledOn('edit')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('info_municipio')
                                    ->label('Municipio')
                                    ->content(fn (Forms\Get $get) => static::selectedInstitution($get)?->city?->name ?? '—'),

                                Forms\Components\Placeholder::make('info_rector')
                                    ->label('Rector(a)')
                                    ->content(fn (Forms\Get $get) => static::selectedInstitution($get)?->principal_name ?? '—'),

                                Forms\Components\Placeholder::make('info_docente_propuesto')
                                    ->label('Docente Propuesto')
                                    ->content(fn (Forms\Get $get) => static::selectedInstitution($get)?->proposed_teacher ?? '—'),

                                Forms\Components\Placeholder::make('info_contacto')
                                    ->label('Teléfono / Correo')
                                    ->content(function (Forms\Get $get) {
                                        $institution = static::selectedInstitution($get);
                                        return $institution ? trim("{$institution->phone} {$institution->email}") : '—';
                                    }),

                                Forms\Components\Placeholder::make('info_gestor')
                                    ->label('Gestor Responsable')
                                    ->content(fn (Forms\Get $get) => static::selectedInstitution($get)?->manager?->name ?? '—'),

                                Forms\Components\Placeholder::make('info_fecha_registro')
                                    ->label('Fecha de registro')
                                    ->content(fn (?InstitutionEvaluation $record) => $record?->created_at?->format('d/m/Y H:i') ?? '—'),

                                Forms\Components\Placeholder::make('info_modificado_por')
                                    ->label('Modificado por')
                                    ->content(fn (?InstitutionEvaluation $record) => $record?->updatedBy?->name)
                                    ->visible(fn (?InstitutionEvaluation $record) => filled($record?->updated_by)),

                                Forms\Components\Placeholder::make('info_ultima_modificacion')
                                    ->label('Última modificación')
                                    ->content(fn (?InstitutionEvaluation $record) => $record?->updated_at?->format('d/m/Y H:i'))
                                    ->visible(fn (?InstitutionEvaluation $record) => filled($record?->updated_by)),
                            ])
                            ->columns(2),

                        // ── Tab 2: Fortalecimiento Pedagógico ─────────────────────────────
                        Forms\Components\Tabs\Tab::make('Fortalecimiento Pedagógico')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([

                                // 2.1 Articulación PEI
                                Forms\Components\Section::make('Articulación del emprendimiento en el PEI')
                                    ->schema([
                                        Forms\Components\Radio::make('pedagogical_section.pei_articulation')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full'     => 'Está incorporado en PEI, currículo, plan de estudios y proyectos institucionales',
                                                'partial'  => 'Está incorporado en PEI y algunas áreas académicas',
                                                'isolated' => 'Existe como proyecto transversal aislado',
                                                'none'     => 'No existe evidencia',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('pedagogical_section.pei_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('pedagogical_section.pei_evidence_file')
                                                ->label('Adjuntar soporte')
                                                ->required(fn (Forms\Get $get) => filled($get('pedagogical_section.pei_articulation')) && $get('pedagogical_section.pei_articulation') !== 'none'),
                                            'pedagogical'
                                        ),
                                    ])
                                    ->columns(2),

                                // 2.2 Área de emprendimiento activa
                                Forms\Components\Section::make('Área de emprendimiento activa')
                                    ->schema([
                                        Forms\Components\Radio::make('pedagogical_section.active_area')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full'       => 'Se desarrolla en 10° y 11° con planeación anual',
                                                'one_grade'  => 'Se desarrolla en uno de los grados',
                                                'occasional' => 'Actividades ocasionales',
                                                'none'       => 'No existe',
                                            ])
                                            ->required()
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('pedagogical_section.active_area_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('pedagogical_section.active_area_evidence_file')
                                                ->label('Adjuntar soporte')
                                                ->required(fn (Forms\Get $get) => filled($get('pedagogical_section.active_area')) && $get('pedagogical_section.active_area') !== 'none'),
                                            'pedagogical'
                                        ),
                                    ])
                                    ->columns(2),
                            ]),

                        // ── Tab 3: Sostenibilidad ─────────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Sostenibilidad')
                            ->icon('heroicon-o-shield-check')
                            ->schema([

                                // 3.1 Estrategia de continuidad
                                Forms\Components\Section::make('Estrategia de continuidad')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.continuity_strategy')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'defined'          => 'Plan de sostenibilidad definido',
                                                'concrete_actions' => 'Acciones concretas para continuar',
                                                'intention'        => 'Intención de continuar',
                                                'none'             => 'No existe estrategia',
                                            ])
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('sustainability_section.continuity_observations')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->required(fn (Forms\Get $get) => $get('sustainability_section.continuity_strategy') === 'intention')
                                            ->columnSpanFull(),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('sustainability_section.continuity_support_file')
                                                ->label('Adjuntar plan o soporte de continuidad')
                                                ->required(fn (Forms\Get $get) => in_array($get('sustainability_section.continuity_strategy'), ['defined', 'concrete_actions']))
                                                ->helperText(fn (Forms\Get $get) => in_array($get('sustainability_section.continuity_strategy'), ['defined', 'concrete_actions'])
                                                    ? 'Requerido para la opción seleccionada.' : null)
                                                ->columnSpanFull(),
                                            'sustainability'
                                        ),
                                    ]),

                                // 3.2 Compromiso institucional
                                Forms\Components\Section::make('Compromiso institucional')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.institutional_commitment')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'letter_and_plan' => 'Carta de compromiso institucional suscrita por rectoría y coordinación, junto con plan de acompañamiento',
                                                'letter'          => 'Carta de compromiso institucional',
                                                'verbal'          => 'Intención verbal',
                                                'none'            => 'No existe compromiso',
                                            ])
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('sustainability_section.commitment_observations')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->required(fn (Forms\Get $get) => $get('sustainability_section.institutional_commitment') === 'verbal')
                                            ->helperText(fn (Forms\Get $get) => $get('sustainability_section.institutional_commitment') === 'verbal'
                                                ? 'Indique quién manifestó el compromiso y en qué fecha.' : null)
                                            ->columnSpanFull(),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('sustainability_section.commitment_files')
                                                ->label('Adjuntar carta y/o plan de acompañamiento')
                                                ->required(fn (Forms\Get $get) => in_array($get('sustainability_section.institutional_commitment'), ['letter_and_plan', 'letter']))
                                                ->helperText(fn (Forms\Get $get) => $get('sustainability_section.institutional_commitment') === 'letter_and_plan'
                                                    ? 'Adjunte al menos 2 archivos: la carta y el plan de acompañamiento.' : null)
                                                ->columnSpanFull(),
                                            'sustainability'
                                        ),
                                    ]),

                                // 3.3 Disponibilidad institucional
                                Forms\Components\Section::make('Disponibilidad institucional')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.institutional_availability')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full'    => 'Garantiza espacios, horarios y acompañamiento durante y después del proyecto',
                                                'partial' => 'Garantiza parcialmente espacios, horarios o acompañamiento',
                                                'none'    => 'No garantiza continuidad',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('sustainability_section.availability_observations')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ── Tab 4: Cultura Emprendedora ───────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Cultura Emprendedora')
                            ->icon('heroicon-o-sparkles')
                            ->schema([

                                // 4.1 Ferias empresariales
                                Forms\Components\Section::make('Ferias empresariales')
                                    ->schema([
                                        Forms\Components\Radio::make('entrepreneurial_culture_section.business_fairs')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'annual'   => 'Feria empresarial anual',
                                                'periodic' => 'Muestras periódicas',
                                                'isolated' => 'Actividades aisladas',
                                                'none'     => 'No realiza actividades',
                                            ])
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('entrepreneurial_culture_section.business_fairs_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        Forms\Components\TextInput::make('entrepreneurial_culture_section.business_fairs_name')
                                            ->label('Nombre de la feria o actividad'),

                                        Forms\Components\TextInput::make('entrepreneurial_culture_section.business_fairs_year')
                                            ->label('Año de la última actividad')
                                            ->numeric()
                                            ->minValue(2000)
                                            ->maxValue(2100),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('entrepreneurial_culture_section.business_fairs_evidence')
                                                ->label('Adjuntar soportes')
                                                ->required(fn (Forms\Get $get) => filled($get('entrepreneurial_culture_section.business_fairs')) && $get('entrepreneurial_culture_section.business_fairs') !== 'none')
                                                ->columnSpanFull(),
                                            'culture'
                                        ),
                                    ])
                                    ->columns(2),

                                // 4.2 Participación en ferias externas
                                Forms\Components\Section::make('Participación en ferias externas')
                                    ->schema([
                                        Forms\Components\Placeholder::make('external_fairs_guide')
                                            ->label('Criterios de clasificación')
                                            ->content(new HtmlString(
                                                '<ul class="list-disc pl-4 text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                    <li><strong>Frecuentemente:</strong> dos o más participaciones externas comprobadas en los últimos dos años.</li>
                                                    <li><strong>Ocasionalmente:</strong> una participación externa comprobada en los últimos dos años.</li>
                                                    <li><strong>No participa:</strong> ninguna participación comprobada.</li>
                                                </ul>'
                                            ))
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('entrepreneurial_culture_section.external_fairs_participation')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'frequent'   => 'Participa frecuentemente',
                                                'occasional' => 'Participa ocasionalmente',
                                                'none'       => 'No participa',
                                            ])
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('entrepreneurial_culture_section.external_fairs_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        Forms\Components\TextInput::make('entrepreneurial_culture_section.external_fairs_count')
                                            ->label('Número de participaciones')
                                            ->numeric()
                                            ->minValue(0),

                                        Forms\Components\Textarea::make('entrepreneurial_culture_section.external_fairs_names')
                                            ->label('Nombre de las ferias')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('entrepreneurial_culture_section.external_fairs_period')
                                            ->label('Año o período'),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('entrepreneurial_culture_section.external_fairs_evidence')
                                                ->label('Adjuntar soportes')
                                                ->columnSpanFull(),
                                            'culture'
                                        ),
                                    ])
                                    ->columns(2),
                            ]),

                        // ── Tab 5: Impacto Territorial ────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Impacto Territorial')
                            ->icon('heroicon-o-map')
                            ->schema([

                                // 5.1 Influencia corredor férreo
                                Forms\Components\Section::make('Influencia corredor férreo')
                                    ->schema([
                                        Forms\Components\Radio::make('territorial_impact_section.railway_corridor_influence')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'direct'   => 'Influencia directa',
                                                'indirect' => 'Influencia indirecta',
                                                'low'      => 'Baja influencia',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('territorial_impact_section.railway_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        Forms\Components\TextInput::make('territorial_impact_section.railway_community')
                                            ->label('Comunidad, corregimiento o vereda atendida'),

                                        Forms\Components\Textarea::make('territorial_impact_section.railway_justification')
                                            ->label('Justificación de la clasificación')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        $fileUploadDefaults(
                                            Forms\Components\FileUpload::make('territorial_impact_section.railway_support_file')
                                                ->label('Adjuntar soporte')
                                                ->columnSpanFull(),
                                            'territorial'
                                        ),
                                    ])
                                    ->columns(2),

                                // 5.2 Población vulnerable o rural
                                Forms\Components\Section::make('Población vulnerable o rural')
                                    ->schema([
                                        Forms\Components\Radio::make('territorial_impact_section.vulnerable_population')
                                            ->label('Seleccione el porcentaje de población vulnerable o rural')
                                            ->options([
                                                'high'   => 'Más del 70%',
                                                'medium' => 'Entre 40% y 70%',
                                                'low'    => 'Menos del 40%',
                                            ])
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('territorial_impact_section.vulnerable_population_source')
                                            ->label('Fuente de la información')
                                            ->options([
                                                'simat'                  => 'SIMAT',
                                                'enrollment'             => 'Registro de matrícula institucional',
                                                'socioeconomic'          => 'Caracterización socioeconómica',
                                                'institutional_report'   => 'Informe institucional',
                                                'rectory_certificate'    => 'Certificación de rectoría',
                                                'other'                  => 'Otra',
                                            ])
                                            ->required()
                                            ->live(),

                                        Forms\Components\TextInput::make('territorial_impact_section.vulnerable_population_source_other')
                                            ->label('Especifique la fuente')
                                            ->required()
                                            ->visible(fn (Forms\Get $get) => $get('territorial_impact_section.vulnerable_population_source') === 'other'),

                                        Forms\Components\Textarea::make('territorial_impact_section.vulnerable_population_observations')
                                            ->label('Observaciones')
                                            ->helperText('Explique brevemente cómo se determinó el rango seleccionado.')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        // ── Tab 6: Capacidad Operativa ────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Capacidad Operativa')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\TextInput::make('operational_capacity_section.students_count')
                                    ->label('Número de estudiantes de 10° y 11°')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1)
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('operational_capacity_section.can_link_min_students')
                                    ->label('¿Puede vincular mínimo 30 estudiantes?')
                                    ->options(['yes' => 'Sí', 'no' => 'No'])
                                    ->placeholder('Sin responder')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get) {
                                        if ($state === 'no') {
                                            Notification::make()
                                                ->warning()
                                                ->title('Capacidad insuficiente')
                                                ->body('La institución no cumple con la capacidad operativa mínima establecida en el perfil de institución piloto.')
                                                ->send();
                                        }
                                        if ($state === 'yes' && (int) $get('operational_capacity_section.students_count') < 30) {
                                            Notification::make()
                                                ->danger()
                                                ->title('Selección no permitida')
                                                ->body('El número de estudiantes registrado es inferior a 30. No es posible seleccionar "Sí".')
                                                ->send();
                                            $set('operational_capacity_section.can_link_min_students', null);
                                        }
                                    }),

                                Forms\Components\Select::make('operational_capacity_section.can_link_min_teachers')
                                    ->label('¿Puede vincular mínimo 3 docentes?')
                                    ->options(['yes' => 'Sí', 'no' => 'No'])
                                    ->placeholder('Sin responder')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state) {
                                        if ($state === 'no') {
                                            Notification::make()
                                                ->warning()
                                                ->title('Capacidad insuficiente')
                                                ->body('La institución no cumple con la capacidad operativa mínima establecida en el perfil de institución piloto.')
                                                ->send();
                                        }
                                    }),

                                Forms\Components\CheckboxList::make('operational_capacity_section.available_spaces')
                                    ->label('Espacios disponibles')
                                    ->options([
                                        'classroom'    => 'Aula de clase',
                                        'computer_lab' => 'Sala de sistemas',
                                        'auditorium'   => 'Auditorio',
                                        'library'      => 'Biblioteca',
                                        'other'        => 'Otro',
                                    ])
                                    ->live()
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('operational_capacity_section.available_spaces_other')
                                    ->label('Especifique el otro espacio')
                                    ->visible(fn (Forms\Get $get) => in_array('other', $get('operational_capacity_section.available_spaces') ?? []))
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        // ── Tab 7: Concepto Técnico ───────────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Concepto Técnico')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Radio::make('technical_verdict')
                                    ->label('Concepto técnico')
                                    ->options([
                                        'favorable'                => 'Favorable',
                                        'favorable_with_conditions' => 'Favorable con condiciones',
                                        'not_favorable'            => 'No favorable',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('technical_conditions')
                                    ->label('Condiciones o recomendaciones que debe cumplir la institución')
                                    ->rows(4)
                                    ->visible(fn (Forms\Get $get) => $get('technical_verdict') === 'favorable_with_conditions')
                                    ->required()
                                    ->rule(static function () {
                                        return function (string $attribute, $value, Closure $fail) {
                                            $wordCount = count(array_filter(preg_split('/\s+/', trim((string) $value))));
                                            if ($wordCount < 20) {
                                                $fail('Debe escribir al menos 20 palabras.');
                                            }
                                        };
                                    })
                                    ->helperText('Mínimo 20 palabras')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('technical_concept')
                                    ->label('Observaciones generales del gestor')
                                    ->rows(5)
                                    ->required()
                                    ->rule(static function () {
                                        return function (string $attribute, $value, Closure $fail) {
                                            $wordCount = count(array_filter(preg_split('/\s+/', trim((string) $value))));
                                            if ($wordCount < 10) {
                                                $fail('Debe escribir al menos 10 palabras.');
                                            }
                                        };
                                    })
                                    ->helperText('Mínimo 10 palabras')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ranking_display')
                    ->label('#')
                    ->state(fn (InstitutionEvaluation $record) => $record->getRankingInfo()['display'])
                    ->badge()
                    ->color(fn (InstitutionEvaluation $record) => $record->getRankingInfo()['is_committee'] ? 'warning' : 'gray')
                    ->tooltip(fn (InstitutionEvaluation $record) => $record->getRankingInfo()['is_committee']
                        ? 'Pendiente de decisión del Comité de Selección'
                        : 'Posición ' . $record->getRankingInfo()['rank'])
                    ->sortable(false)
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer'])),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (InstitutionEvaluation $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('Puntaje')
                    ->sortable()
                    ->alignCenter()
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer'])),

                Tables\Columns\TextColumn::make('result_category')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Apta'                 => 'success',
                        'Apta con condiciones' => 'warning',
                        'No apta'              => 'danger',
                        default                => 'gray',
                    })
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer'])),

                Tables\Columns\TextColumn::make('technical_verdict')
                    ->label('Concepto técnico')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'favorable'                 => 'Favorable',
                        'favorable_with_conditions' => 'Con condiciones',
                        'not_favorable'             => 'No favorable',
                        default                     => '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'favorable'                 => 'success',
                        'favorable_with_conditions' => 'warning',
                        'not_favorable'             => 'danger',
                        default                     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => !$record->trashed() && static::userCanEdit() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->trashed() && static::userCanDelete() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutionEvaluations::route('/'),
            'create' => Pages\CreateInstitutionEvaluation::route('/create'),
            'edit' => Pages\EditInstitutionEvaluation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
}
