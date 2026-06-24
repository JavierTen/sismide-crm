<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\InstitutionEvaluationResource\Pages;
use App\Models\EducationalInstitution;
use App\Models\InstitutionEvaluation;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstitutionEvaluationResource extends Resource
{
    protected static ?string $model = InstitutionEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Evaluaciones';

    protected static ?string $navigationLabel = 'Instituciones Educativas';

    protected static ?string $modelLabel = 'Evaluación';

    protected static ?string $pluralModelLabel = 'Evaluaciones';

    protected static ?int $navigationSort = 1;

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
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Tabs::make('Registro de Evaluación')
                    ->columnSpanFull()
                    ->tabs([
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

                                        return $institution
                                            ? trim("{$institution->phone} {$institution->email}")
                                            : '—';
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

                        Forms\Components\Tabs\Tab::make('Fortalecimiento Pedagógico')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Forms\Components\Section::make('Articulación del emprendimiento en el PEI')
                                    ->schema([
                                        Forms\Components\Radio::make('pedagogical_section.pei_articulation')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full' => 'Está incorporado en PEI, currículo, plan de estudios y proyectos institucionales',
                                                'partial' => 'Está incorporado en PEI y algunas áreas académicas',
                                                'isolated' => 'Existe como proyecto transversal aislado',
                                                'none' => 'No existe evidencia',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('pedagogical_section.pei_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        Forms\Components\FileUpload::make('pedagogical_section.pei_evidence_file')
                                            ->label('Adjuntar soporte')
                                            ->directory('institution-evaluations/pedagogical')
                                            ->disk('public')
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Área de emprendimiento activa')
                                    ->schema([
                                        Forms\Components\Radio::make('pedagogical_section.active_area')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full' => 'Se desarrolla en 10° y 11° con planeación anual',
                                                'one_grade' => 'Se desarrolla en uno de los grados',
                                                'occasional' => 'Actividades ocasionales',
                                                'none' => 'No existe',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('pedagogical_section.active_area_observations')
                                            ->label('Observaciones')
                                            ->rows(3),

                                        Forms\Components\FileUpload::make('pedagogical_section.active_area_evidence_file')
                                            ->label('Adjuntar soporte')
                                            ->directory('institution-evaluations/pedagogical')
                                            ->disk('public')
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Sostenibilidad')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Estrategia de continuidad')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.continuity_strategy')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'defined' => 'Plan de sostenibilidad definido',
                                                'concrete_actions' => 'Acciones concretas para continuar',
                                                'intention' => 'Intención de continuar',
                                                'none' => 'No existe estrategia',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('sustainability_section.continuity_observations')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Compromiso institucional')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.institutional_commitment')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'letter_and_plan' => 'Carta de compromiso y plan de acompañamiento',
                                                'letter' => 'Carta de compromiso',
                                                'verbal' => 'Intención verbal',
                                                'none' => 'No existe compromiso',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\FileUpload::make('sustainability_section.commitment_letter_file')
                                            ->label('Adjuntar carta')
                                            ->directory('institution-evaluations/sustainability')
                                            ->disk('public')
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']),
                                    ]),

                                Forms\Components\Section::make('Disponibilidad institucional')
                                    ->schema([
                                        Forms\Components\Radio::make('sustainability_section.institutional_availability')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'full' => 'Garantiza espacios, horarios y acompañamiento',
                                                'partial' => 'Garantiza parcialmente',
                                                'none' => 'No garantiza continuidad',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('sustainability_section.availability_observations')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Cultura Emprendedora')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Section::make('Ferias empresariales')
                                    ->schema([
                                        Forms\Components\Radio::make('entrepreneurial_culture_section.business_fairs')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'annual' => 'Feria empresarial anual',
                                                'periodic' => 'Muestras periódicas',
                                                'isolated' => 'Actividades aisladas',
                                                'none' => 'No realiza actividades',
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Participación en ferias externas')
                                    ->schema([
                                        Forms\Components\Radio::make('entrepreneurial_culture_section.external_fairs_participation')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'frequent' => 'Participa frecuentemente',
                                                'occasional' => 'Participa ocasionalmente',
                                                'none' => 'No participa',
                                            ]),
                                    ]),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Impacto Territorial')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Forms\Components\Section::make('Influencia corredor férreo')
                                    ->schema([
                                        Forms\Components\Radio::make('territorial_impact_section.railway_corridor_influence')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'direct' => 'Influencia directa',
                                                'indirect' => 'Influencia indirecta',
                                                'low' => 'Baja influencia',
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Población vulnerable o rural')
                                    ->schema([
                                        Forms\Components\Radio::make('territorial_impact_section.vulnerable_population')
                                            ->label('Seleccione la situación que mejor describa a la institución')
                                            ->options([
                                                'high' => 'Más del 70%',
                                                'medium' => 'Entre 40% y 70%',
                                                'low' => 'Menos del 40%',
                                            ]),
                                    ]),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Capacidad Operativa')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Forms\Components\TextInput::make('operational_capacity_section.students_count')
                                    ->label('Número de estudiantes de 10° y 11°')
                                    ->numeric()
                                    ->minValue(0),

                                Forms\Components\Toggle::make('operational_capacity_section.can_link_min_students')
                                    ->label('¿Puede vincular mínimo 30 estudiantes?')
                                    ->inline(false),

                                Forms\Components\Toggle::make('operational_capacity_section.can_link_min_teachers')
                                    ->label('¿Puede vincular mínimo 3 docentes?')
                                    ->inline(false),

                                Forms\Components\CheckboxList::make('operational_capacity_section.available_spaces')
                                    ->label('Espacios disponibles')
                                    ->options([
                                        'classroom' => 'Aula de clase',
                                        'computer_lab' => 'Sala de sistemas',
                                        'auditorium' => 'Auditorio',
                                        'library' => 'Biblioteca',
                                        'other' => 'Otro',
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

                        Forms\Components\Tabs\Tab::make('Concepto Técnico')
                            ->icon('heroicon-o-document-text')
                            ->schema([
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
                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (InstitutionEvaluation $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updatedBy.name')
                    ->label('Modificado por')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
