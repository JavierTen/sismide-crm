<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentTrainingSessionResource\Pages;
use App\Models\EducationalInstitution;
use App\Models\Student;
use App\Models\StudentTraining;
use App\Models\StudentTrainingSession;
use App\Models\Teacher;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentTrainingSessionResource extends Resource
{
    protected static ?string $model = StudentTrainingSession::class;

    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $navigationLabel = 'Sesiones';
    protected static ?string $modelLabel      = 'Sesión';
    protected static ?string $pluralModelLabel = 'Sesiones';
    protected static ?int    $navigationSort  = 2;

    /** Encabezado de la columna del adjunto que se vuelve enlace en el Excel. */
    private const ATTENDANCE_HEADING = 'Lista de Asistencia';

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentTrainingSessions') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentTrainingSession') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentTrainingSession') ?? false; }
    private static function userCanDelete(): bool { return auth()->user()?->can('deleteStudentTrainingSession') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['training', 'educationalInstitution.city'])
            ->withCount([
                'students as summoned_count',
                'students as attendees_count' => fn (Builder $query) => $query
                    ->where('student_training_session_student.attended', true),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Tabs::make('Sesión')
                ->columnSpanFull()
                ->tabs([

                    // ── Datos de la Sesión ────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Datos de la Sesión')
                        ->icon('heroicon-o-calendar-days')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('student_training_id')
                                ->label('Capacitación')
                                ->options(fn () => StudentTraining::withoutTrashed()
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('training_summary')
                                ->label('Capacitadores')
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => filled($get('student_training_id')))
                                ->content(fn (Get $get) => static::trainingFacilitators($get('student_training_id'))),

                            Forms\Components\Select::make('educational_institution_id')
                                ->label('Institución')
                                ->options(fn () => EducationalInstitution::withoutTrashed()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($institution) => [$institution->id => $institution->display_name]))
                                ->searchable()
                                ->required()
                                ->live()
                                // No se habilita hasta que haya una capacitación elegida.
                                ->disabled(fn (Get $get): bool => blank($get('student_training_id')))
                                // Cambiar de institución invalida los participantes marcados.
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('attending_students', []);
                                    $set('teachers', []);
                                })
                                ->helperText(fn (Get $get): ?string => blank($get('student_training_id'))
                                    ? 'Seleccione primero la capacitación.'
                                    : null)
                                ->rule(static::uniqueTrainingInstitutionRule()),

                            Forms\Components\Placeholder::make('municipality')
                                ->label('Municipio')
                                ->content(fn (Get $get): string => EducationalInstitution::with('city')
                                    ->find($get('educational_institution_id'))?->city?->name ?? '—'),

                            Forms\Components\DatePicker::make('session_date')
                                ->label('Fecha de Realización')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TimePicker::make('start_time')
                                ->label('Hora de Inicio')
                                ->required()
                                ->seconds(false)
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateIntensity($get, $set)),

                            Forms\Components\TimePicker::make('end_time')
                                ->label('Hora de Finalización')
                                ->required()
                                ->seconds(false)
                                ->after('start_time')
                                ->validationMessages([
                                    'after' => 'La hora de finalización debe ser posterior a la de inicio.',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateIntensity($get, $set)),

                            Forms\Components\TextInput::make('intensity_hours')
                                ->label('Duración / Intensidad Horaria')
                                ->readOnly()
                                ->numeric()
                                ->suffix('horas')
                                ->helperText('Se calcula entre la hora de inicio y la de finalización.'),
                        ]),

                    // ── Asistencia ────────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Asistencia')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Forms\Components\Placeholder::make('attendance_counters')
                                ->label('Resumen')
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => filled($get('educational_institution_id')))
                                ->content(fn (Get $get) => static::attendanceCounters($get)),

                            Forms\Components\CheckboxList::make('attending_students')
                                ->label('Estudiantes')
                                ->options(fn (Get $get) => static::institutionStudents($get('educational_institution_id')))
                                // No es una columna: la asistencia se guarda en la pivote
                                // desde las páginas, conservando también a los no asistentes.
                                ->dehydrated(false)
                                ->required()
                                ->searchable()
                                ->bulkToggleable()
                                ->live()
                                ->columns(2)
                                ->columnSpanFull()
                                ->helperText('Se listan todos los estudiantes registrados de la institución. Marque quienes asistieron; los no marcados quedan como convocados que no asistieron.'),

                            Forms\Components\Placeholder::make('students_empty_hint')
                                ->hiddenLabel()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => blank($get('educational_institution_id')))
                                ->content(static::noInstitutionHint('Aún no ha seleccionado una institución. Elíjala para cargar sus estudiantes.')),

                            Forms\Components\CheckboxList::make('teachers')
                                ->label('Docentes Participantes')
                                ->relationship('teachers', 'name')
                                ->auditRelationship('docentes')
                                ->options(fn (Get $get) => blank($get('educational_institution_id'))
                                    ? []
                                    : Teacher::withoutTrashed()
                                        ->where('educational_institution_id', $get('educational_institution_id'))
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('teachers_empty_hint')
                                ->hiddenLabel()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => blank($get('educational_institution_id')))
                                ->content(static::noInstitutionHint('Aún no ha seleccionado una institución. Elíjala para cargar sus docentes.')),
                        ]),

                    // ── Desarrollo de la Sesión ───────────────────────────────
                    Forms\Components\Tabs\Tab::make('Desarrollo de la Sesión')
                        ->icon('heroicon-o-light-bulb')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('methodology')
                                ->label('Metodología Utilizada')
                                ->options(StudentTraining::METHODOLOGY_OPTIONS)
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('methodology_other')
                                ->label('¿Cuál?')
                                ->required()
                                ->maxLength(255)
                                ->visible(fn (Get $get): bool => $get('methodology') === 'otro'),

                            Forms\Components\Textarea::make('activity')
                                ->label('Actividad Desarrollada')
                                ->required()
                                ->rows(4)
                                ->helperText('Descripción de lo realizado en la sesión')
                                ->columnSpanFull(),

                            Forms\Components\Radio::make('result_rating')
                                ->label('Resultado de la Sesión')
                                ->options(StudentTraining::RESULT_RATING_OPTIONS)
                                ->required()
                                ->inline()
                                ->inlineLabel(false)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('result_detail')
                                ->label('Detalle del resultado')
                                ->required()
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('observations')
                                ->label('Observaciones')
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\Repeater::make('commitments')
                                ->label('Compromisos')
                                ->addActionLabel('+ Agregar compromiso')
                                ->defaultItems(0)
                                ->columns(3)
                                ->columnSpanFull()
                                ->schema([
                                    Forms\Components\TextInput::make('commitment')
                                        ->label('Compromiso')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('responsible')
                                        ->label('Responsable')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\DatePicker::make('due_date')
                                        ->label('Fecha límite')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d/m/Y'),
                                ]),
                        ]),

                    // ── Evidencias ────────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Evidencias')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Forms\Components\FileUpload::make('attendance_list_path')
                                ->label('Lista de Asistencia (firmada)')
                                ->required()
                                ->disk('public')
                                ->directory('student-training-attendance')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->maxSize(10240)
                                ->downloadable()
                                ->openable()
                                ->helperText('PDF o imagen con las firmas de los asistentes (máx. 10 MB)'),

                            Forms\Components\FileUpload::make('additional_material')
                                ->label('Material Adicional')
                                ->disk('public')
                                ->directory('student-training-material')
                                ->multiple()
                                ->maxFiles(10)
                                ->maxSize(10240)
                                ->reorderable()
                                ->downloadable()
                                ->openable()
                                ->helperText('Presentaciones, fotos u otro material de apoyo. Campo opcional.'),
                        ]),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('session_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('training.name')
                    ->label('Capacitación')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? '—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('session_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('intensity_hours')
                    ->label('Horas')
                    ->numeric(decimalPlaces: 1),

                Tables\Columns\TextColumn::make('attendees_count')
                    ->label('Asistencia')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->summoned_count === 0    => 'gray',
                        $record->attendees_count / max($record->summoned_count, 1) >= 0.8 => 'success',
                        $record->attendees_count / max($record->summoned_count, 1) >= 0.5 => 'warning',
                        default                          => 'danger',
                    })
                    ->formatStateUsing(fn ($record) => static::attendanceLabel(
                        (int) $record->attendees_count,
                        (int) $record->summoned_count,
                    )),

                Tables\Columns\TextColumn::make('result_rating')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => StudentTraining::RESULT_RATING_OPTIONS[$state] ?? '—')
                    ->color(fn ($state) => match ($state) {
                        'excelente'  => 'success',
                        'buena'      => 'info',
                        'regular'    => 'warning',
                        'deficiente' => 'danger',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('student_training_id')
                    ->label('Capacitación')
                    ->options(fn () => StudentTraining::withoutTrashed()->orderBy('name')->pluck('name', 'id')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver')
                    ->mutateRecordDataUsing(fn (array $data, $record) => static::fillAttendanceData($data, $record)),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar')
                    ->mutateRecordDataUsing(fn (array $data, $record) => static::fillAttendanceData($data, $record))
                    ->after(fn ($record, array $data) => $record->syncAttendance($data['attending_students'] ?? []))
                    ->visible(fn ($record) => ! $record->trashed() && static::userCanEdit() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Deshabilitar')
                    ->visible(fn ($record) => ! $record->trashed() && static::userCanDelete() && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())),
                Tables\Actions\RestoreAction::make()->label('')->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),
                Tables\Actions\ForceDeleteAction::make()->label('')->tooltip('Eliminar definitivamente')
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'sesiones-capacitacion-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'training.facilitators.entity',
                                'training.facilitators.contact',
                                'educationalInstitution.city',
                                'students',
                                'teachers',
                                'manager',
                            ]))
                            ->withColumns(self::exportColumns())
                            ->afterSheet(self::afterSheetCallback()),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Excel')
                        ->exports([
                            FormattedExcelExport::make()
                                ->withFilename(fn () => 'sesiones-capacitacion-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with([
                                    'training.facilitators.entity',
                                    'training.facilitators.contact',
                                    'educationalInstitution.city',
                                    'students',
                                    'teachers',
                                    'manager',
                                ]))
                                ->withColumns(self::exportColumns())
                                ->afterSheet(self::afterSheetCallback()),
                        ]),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => static::userCanDelete()),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudentTrainingSessions::route('/'),
            'create' => Pages\CreateStudentTrainingSession::route('/create'),
            'edit'   => Pages\EditStudentTrainingSession::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    /**
     * Precarga el checklist con quienes constan como asistentes. Los modales de
     * Ver y Editar de la tabla llenan el formulario desde el registro, y la
     * asistencia no es una columna: hay que inyectarla a mano.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function fillAttendanceData(array $data, StudentTrainingSession $record): array
    {
        $data['attending_students'] = $record->attendees()->pluck('students.id')->all();

        return $data;
    }

    /** Estudiantes registrados de la institución, para el checklist. */
    public static function institutionStudents(mixed $institutionId): array
    {
        if (blank($institutionId)) {
            return [];
        }

        return Student::withoutTrashed()
            ->where('educational_institution_id', $institutionId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function recalculateIntensity(Get $get, Set $set): void
    {
        $start = $get('start_time');
        $end   = $get('end_time');

        if (blank($start) || blank($end)) {
            $set('intensity_hours', null);

            return;
        }

        $startAt = Carbon::parse($start);
        $endAt   = Carbon::parse($end);

        $set('intensity_hours', $endAt->greaterThan($startAt)
            ? round($startAt->diffInMinutes($endAt) / 60, 2)
            : null);
    }

    private static function attendanceLabel(int $attendees, int $summoned): string
    {
        $rate = $summoned === 0 ? 0 : round($attendees * 100 / $summoned);

        return "{$attendees} / {$summoned}  ({$rate}%)";
    }

    private static function attendanceCounters(Get $get): HtmlString
    {
        $summoned  = count(static::institutionStudents($get('educational_institution_id')));
        $attendees = count($get('attending_students') ?? []);
        $rate      = $summoned === 0 ? 0 : round($attendees * 100 / $summoned, 1);

        $cards = [
            ['Convocados', $summoned],
            ['Asistentes', $attendees],
            ['% Asistencia', $rate.'%'],
        ];

        // Estilos en linea, no utilidades de Tailwind: el CSS compilado de
        // Filament no incluye clases que solo aparecen en PHP, y `grid-cols-3`
        // se perdia dejando las tarjetas apiladas.
        $card = '<div style="flex:1 1 0;min-width:110px;text-align:center;border-radius:0.5rem;'
            .'padding:0.75rem 1rem;background:rgba(128,128,128,0.10);">'
            .'<div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;opacity:0.65;">%s</div>'
            .'<div style="font-size:1.375rem;font-weight:600;line-height:1.2;margin-top:0.15rem;">%s</div>'
            .'</div>';

        $html = collect($cards)
            ->map(fn ($item) => sprintf($card, e($item[0]), e($item[1])))
            ->implode('');

        return new HtmlString(
            '<div style="display:flex;flex-wrap:wrap;gap:0.75rem;">'.$html.'</div>'
        );
    }

    /**
     * Capacitadores de la capacitación elegida. No se repite el módulo y tema:
     * ya está visible en el select de arriba.
     */
    private static function trainingFacilitators(mixed $trainingId): HtmlString
    {
        $training = StudentTraining::with(['facilitators.entity', 'facilitators.contact'])
            ->withoutGlobalScopes()
            ->find($trainingId);

        if (! $training) {
            return new HtmlString('—');
        }

        $rows = $training->facilitators
            ->map(fn ($facilitator) => e($facilitator->contact?->name ?? '—')
                .' — '
                .e($facilitator->entity?->name ?? '—'))
            ->implode('<br>');

        return new HtmlString($rows ?: 'Sin capacitadores registrados.');
    }

    private static function noInstitutionHint(string $message): HtmlString
    {
        return new HtmlString(Blade::render(
            <<<'BLADE'
            <div class="flex flex-col items-center justify-center gap-2 py-6 text-center">
                <x-filament::icon
                    icon="heroicon-o-building-library"
                    class="h-8 w-8 text-gray-400 dark:text-gray-500"
                />

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $message }}
                </p>
            </div>
            BLADE,
            ['message' => $message],
        ));
    }

    /**
     * Una capacitación se dicta una sola vez por institución. Replica en el
     * formulario el índice único `sts_unique_training_institution` para mostrar
     * un mensaje legible en lugar del error SQL 1062.
     */
    private static function uniqueTrainingInstitutionRule(): Closure
    {
        return static function (Get $get, ?Model $record): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                $trainingId = $get('student_training_id');

                if (blank($trainingId) || blank($value)) {
                    return;
                }

                $exists = StudentTrainingSession::query()
                    ->withoutGlobalScopes()
                    ->where('student_training_id', $trainingId)
                    ->where('educational_institution_id', $value)
                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                    ->exists();

                if ($exists) {
                    $fail('Ya existe una sesión registrada de esta capacitación para esta institución.');
                }
            };
        };
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('training')->heading('Capacitación')
                ->getStateUsing(fn ($record) => $record->training?->name ?? ''),
            Column::make('facilitators')->heading('Facilitadores')
                ->getStateUsing(fn ($record) => ($record->training?->facilitators ?? collect())
                    ->map(fn ($facilitator) => ($facilitator->contact?->name ?? '—').' ('.($facilitator->entity?->name ?? '—').')')
                    ->implode("\n")),
            Column::make('institution')->heading('Institución')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? ''),
            Column::make('municipality')->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->city?->name ?? ''),
            Column::make('session_date')->heading('Fecha de Realización')
                ->getStateUsing(fn ($record) => $record->session_date?->format('d/m/Y') ?? ''),
            Column::make('start_time')->heading('Hora Inicio'),
            Column::make('end_time')->heading('Hora Fin'),
            Column::make('intensity_hours')->heading('Intensidad Horaria'),
            Column::make('summoned')->heading('Convocados')
                ->getStateUsing(fn ($record) => $record->students->count()),
            Column::make('attendees')->heading('Asistentes')
                ->getStateUsing(fn ($record) => $record->students->where('pivot.attended', true)->count()),
            Column::make('attendance_rate')->heading('% Asistencia')
                ->getStateUsing(function ($record) {
                    $summoned = $record->students->count();

                    return $summoned === 0
                        ? '0%'
                        : round($record->students->where('pivot.attended', true)->count() * 100 / $summoned, 1).'%';
                }),
            Column::make('attendees_list')->heading('Estudiantes Asistentes')
                ->getStateUsing(fn ($record) => $record->students->where('pivot.attended', true)->pluck('name')->implode(', ')),
            Column::make('absentees_list')->heading('Estudiantes No Asistentes')
                ->getStateUsing(fn ($record) => $record->students->where('pivot.attended', false)->pluck('name')->implode(', ')),
            Column::make('teachers')->heading('Docentes Participantes')
                ->getStateUsing(fn ($record) => $record->teachers->pluck('name')->implode(', ')),
            Column::make('methodology')->heading('Metodología')
                ->getStateUsing(fn ($record) => $record->methodology === 'otro'
                    ? 'Otro: '.$record->methodology_other
                    : (StudentTraining::METHODOLOGY_OPTIONS[$record->methodology] ?? $record->methodology)),
            Column::make('activity')->heading('Actividad Desarrollada'),
            Column::make('result_rating')->heading('Resultado')
                ->getStateUsing(fn ($record) => StudentTraining::RESULT_RATING_OPTIONS[$record->result_rating] ?? $record->result_rating),
            Column::make('result_detail')->heading('Detalle del Resultado'),
            Column::make('observations')->heading('Observaciones'),
            Column::make('commitments')->heading('Compromisos')
                ->getStateUsing(fn ($record) => collect($record->commitments ?? [])
                    ->map(fn ($item) => sprintf(
                        '%s — %s (%s)',
                        $item['commitment'] ?? '',
                        $item['responsible'] ?? '',
                        $item['due_date'] ?? '',
                    ))
                    ->implode("\n")),
            Column::make('attendance_list_path')->heading(self::ATTENDANCE_HEADING)
                ->getStateUsing(fn ($record) => filled($record->attendance_list_path)
                    ? Storage::disk('public')->url($record->attendance_list_path)
                    : ''),
            Column::make('additional_material')->heading('Material Adicional')
                ->getStateUsing(fn ($record) => collect($record->additional_material ?? [])
                    ->map(fn ($path) => Storage::disk('public')->url($path))
                    ->implode("\n")),
            Column::make('manager_name')->heading('Registrado por')
                ->getStateUsing(fn ($record) => $record->manager?->name ?? ''),
            Column::make('created_at')->heading('Fecha Registro')
                ->getStateUsing(fn ($record) => $record->created_at?->format('d/m/Y H:i') ?? ''),
        ];
    }

    private static function afterSheetCallback(): \Closure
    {
        return function (\Maatwebsite\Excel\Events\AfterSheet $event) {
            $sheet        = $event->sheet->getDelegate();
            $highest      = $sheet->getHighestRowAndColumn();
            $lastCol      = $highest['column'];
            $lastRow      = $highest['row'];
            $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);

            $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E40AF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);

            if ($lastRow > 1) {
                $sheet->getStyle('A2:'.$lastCol.$lastRow)->getAlignment()
                    ->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            }

            for ($i = 1; $i <= $lastColIndex; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }

            // La lista de asistencia es un único archivo: su celda se vuelve
            // enlace clicable. El material adicional puede traer varias URLs.
            $attendanceColumn = null;

            for ($i = 1; $i <= $lastColIndex; $i++) {
                if ($sheet->getCellByColumnAndRow($i, 1)->getValue() === self::ATTENDANCE_HEADING) {
                    $attendanceColumn = $i;

                    break;
                }
            }

            if ($attendanceColumn !== null) {
                for ($row = 2; $row <= $lastRow; $row++) {
                    $cell = $sheet->getCellByColumnAndRow($attendanceColumn, $row);
                    $url  = trim((string) $cell->getValue());

                    if ($url === '') {
                        continue;
                    }

                    $cell->getHyperlink()->setUrl($url);

                    $sheet->getStyleByColumnAndRow($attendanceColumn, $row)
                        ->getFont()
                        ->setUnderline(true)
                        ->getColor()
                        ->setARGB('FF1E40AF');
                }
            }

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastCol.'1');
        };
    }
}
