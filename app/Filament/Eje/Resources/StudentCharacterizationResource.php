<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\StudentCharacterizationResource\Pages;
use App\Models\Student;
use App\Models\StudentCharacterization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentCharacterizationResource extends Resource
{
    protected static ?string $model = StudentCharacterization::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Caracterizaciones';

    protected static ?string $navigationLabel = 'Estudiantes';

    protected static ?string $modelLabel = 'Caracterización';

    protected static ?string $pluralModelLabel = 'Caracterizaciones';

    protected static ?int $navigationSort = 1;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentCharacterizations') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentCharacterization') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentCharacterization') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentCharacterization') ?? false; }

    public static function canViewAny(): bool              { return static::userCanList(); }
    public static function canCreate(): bool               { return static::userCanCreate(); }
    public static function canEdit($record): bool          { return static::userCanEdit(); }
    public static function canDelete($record): bool        { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    private static function selectedStudent(Forms\Get $get): ?Student
    {
        $studentId = $get('student_id');

        if (! $studentId) {
            return null;
        }

        return Student::with(['educationalInstitution.city', 'documentType', 'gender', 'teachers'])->find($studentId);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Tabs::make('Registro de Caracterización')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Estudiante')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Select::make('student_id')
                                    ->label('Estudiante')
                                    ->options(fn () => Student::get()->mapWithKeys(
                                        fn (Student $student) => [$student->id => "{$student->name} - {$student->educationalInstitution?->display_name}"]
                                    ))
                                    ->placeholder('Seleccione el estudiante')
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->disabledOn('edit')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('info_institucion')
                                    ->label('Institución Educativa')
                                    ->content(fn (Forms\Get $get) => static::selectedStudent($get)?->educationalInstitution?->display_name ?? '—'),

                                Forms\Components\Placeholder::make('info_municipio')
                                    ->label('Municipio')
                                    ->content(fn (Forms\Get $get) => static::selectedStudent($get)?->educationalInstitution?->city?->name ?? '—'),

                                Forms\Components\Placeholder::make('info_documento')
                                    ->label('Documento')
                                    ->content(function (Forms\Get $get) {
                                        $student = static::selectedStudent($get);

                                        return $student
                                            ? trim("{$student->documentType?->name} {$student->document_number}")
                                            : '—';
                                    }),

                                Forms\Components\Placeholder::make('info_edad')
                                    ->label('Edad')
                                    ->content(fn (Forms\Get $get) => static::selectedStudent($get)?->age ?? '—'),

                                Forms\Components\Placeholder::make('info_genero')
                                    ->label('Género')
                                    ->content(fn (Forms\Get $get) => static::selectedStudent($get)?->gender?->name ?? '—'),

                                Forms\Components\Placeholder::make('info_grado')
                                    ->label('Grado / Curso')
                                    ->content(function (Forms\Get $get) {
                                        $student = static::selectedStudent($get);

                                        if (! $student) {
                                            return '—';
                                        }

                                        return trim((Student::gradeOptions()[$student->grade] ?? $student->grade) . ' ' . $student->course);
                                    }),

                                Forms\Components\Placeholder::make('info_contacto')
                                    ->label('Teléfono / Correo')
                                    ->content(function (Forms\Get $get) {
                                        $student = static::selectedStudent($get);

                                        return $student
                                            ? trim("{$student->phone} {$student->email}")
                                            : '—';
                                    }),

                                Forms\Components\Placeholder::make('info_acudiente')
                                    ->label('Acudiente')
                                    ->content(function (Forms\Get $get) {
                                        $student = static::selectedStudent($get);

                                        return $student
                                            ? trim("{$student->guardian_name} {$student->guardian_phone}")
                                            : '—';
                                    }),

                                Forms\Components\Placeholder::make('info_docentes')
                                    ->label('Docente(s) a cargo')
                                    ->content(fn (Forms\Get $get) => static::selectedStudent($get)?->teachers->pluck('name')->implode(', ') ?: '—'),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make('Caracterización')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Section::make('Caracterización')
                                    ->schema([
                                        Forms\Components\Select::make('zone')
                                            ->label('Zona')
                                            ->options(StudentCharacterization::zoneOptions())
                                            ->placeholder('Seleccione la zona'),

                                        Forms\Components\Select::make('main_interest')
                                            ->label('Interés principal')
                                            ->options(StudentCharacterization::mainInterestOptions())
                                            ->placeholder('Seleccione el interés principal')
                                            ->live(),

                                        Forms\Components\TextInput::make('main_interest_other')
                                            ->label('Especifique el interés')
                                            ->placeholder('Ej: Música')
                                            ->visible(fn (Forms\Get $get) => $get('main_interest') === 'other')
                                            ->required(fn (Forms\Get $get) => $get('main_interest') === 'other'),

                                        Forms\Components\Textarea::make('life_project')
                                            ->label('Proyecto de vida')
                                            ->placeholder('Describa el proyecto de vida del estudiante')
                                            ->rows(4)
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('has_prior_experience')
                                            ->label('¿Tiene experiencia previa?')
                                            ->live()
                                            ->inline(false),

                                        Forms\Components\Select::make('prior_experience_type')
                                            ->label('Tipo de experiencia previa')
                                            ->options(StudentCharacterization::priorExperienceTypeOptions())
                                            ->placeholder('Seleccione el tipo de experiencia')
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => (bool) $get('has_prior_experience'))
                                            ->required(fn (Forms\Get $get) => (bool) $get('has_prior_experience')),

                                        Forms\Components\TextInput::make('prior_experience_other')
                                            ->label('Especifique la experiencia')
                                            ->placeholder('Ej: Ayudante en finca familiar')
                                            ->visible(fn (Forms\Get $get) => (bool) $get('has_prior_experience') && $get('prior_experience_type') === 'other')
                                            ->required(fn (Forms\Get $get) => (bool) $get('has_prior_experience') && $get('prior_experience_type') === 'other'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Estado de participación')
                                    ->schema([
                                        Forms\Components\Select::make('participation_status')
                                            ->label('Estado de participación')
                                            ->options(StudentCharacterization::participationStatusOptions())
                                            ->placeholder('Seleccione el estado')
                                            ->default('active')
                                            ->live()
                                            ->required(),

                                        Forms\Components\DatePicker::make('program_join_date')
                                            ->label('Fecha de ingreso al programa')
                                            ->placeholder('Seleccione la fecha'),

                                        Forms\Components\DatePicker::make('program_exit_date')
                                            ->label('Fecha de salida')
                                            ->placeholder('Seleccione la fecha')
                                            ->visible(fn (Forms\Get $get) => in_array($get('participation_status'), ['withdrawn', 'completed', 'transferred'])),

                                        Forms\Components\Select::make('exit_reason')
                                            ->label('Motivo de salida')
                                            ->options(StudentCharacterization::exitReasonOptions())
                                            ->placeholder('Seleccione el motivo')
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => in_array($get('participation_status'), ['withdrawn', 'completed', 'transferred'])),

                                        Forms\Components\TextInput::make('exit_reason_other')
                                            ->label('Especifique el motivo')
                                            ->placeholder('Ej: Cambio de ciudad')
                                            ->visible(fn (Forms\Get $get) => in_array($get('participation_status'), ['withdrawn', 'completed', 'transferred']) && $get('exit_reason') === 'other')
                                            ->required(fn (Forms\Get $get) => in_array($get('participation_status'), ['withdrawn', 'completed', 'transferred']) && $get('exit_reason') === 'other'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Autorización de tratamiento de datos')
                                    ->schema([
                                        Forms\Components\Toggle::make('data_authorization')
                                            ->label('¿El acudiente autoriza el tratamiento de datos?')
                                            ->inline(false),

                                        Forms\Components\FileUpload::make('data_authorization_file')
                                            ->label('Documento firmado por el acudiente')
                                            ->directory('student-characterizations/authorizations')
                                            ->disk('public')
                                            ->required()
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                            ->helperText('Documento de autorización firmado (máximo 5MB)'),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (StudentCharacterization $record) => $record->student?->educationalInstitution?->display_name),

                Tables\Columns\TextColumn::make('zone')
                    ->label('Zona')
                    ->formatStateUsing(fn (?string $state) => StudentCharacterization::zoneOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('main_interest')
                    ->label('Interés principal')
                    ->formatStateUsing(fn (?string $state) => StudentCharacterization::mainInterestOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('participation_status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => StudentCharacterization::participationStatusOptions()[$state] ?? $state)
                    ->badge(),

                Tables\Columns\IconColumn::make('data_authorization')
                    ->label('Autorización')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('participation_status')
                    ->label('Estado')
                    ->options(StudentCharacterization::participationStatusOptions()),

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
            'index' => Pages\ListStudentCharacterizations::route('/'),
            'create' => Pages\CreateStudentCharacterization::route('/create'),
            'edit' => Pages\EditStudentCharacterization::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
}
