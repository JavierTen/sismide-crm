<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentFairParticipationResource\Pages;
use App\Models\Actor;
use App\Models\EducationalInstitution;
use App\Models\Student;
use App\Models\StudentFair;
use App\Models\StudentFairParticipation;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentFairParticipationResource extends Resource
{
    protected static ?string $model = StudentFairParticipation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Ferias Estudiantiles';
    protected static ?string $navigationLabel = 'Participaciones';
    protected static ?string $modelLabel      = 'Participación en Feria';
    protected static ?string $pluralModelLabel = 'Participaciones en Ferias';
    protected static ?int    $navigationSort  = 2;

    /** Encabezado de la columna del adjunto que se vuelve enlace en el Excel. */
    private const ATTENDANCE_HEADING = 'Listado de Asistencia';

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentFairParticipations') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentFairParticipation') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentFairParticipation') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentFairParticipation') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['fair', 'educationalInstitution']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Tab: Feria y Participantes ────────────────────────────────────
            Forms\Components\Tabs::make('Participación')
                ->columnSpanFull()
                ->tabs([

                    Forms\Components\Tabs\Tab::make('Feria y Participantes')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([

                            Forms\Components\Select::make('student_fair_id')
                                ->label('Feria')
                                ->options(fn () => StudentFair::withoutTrashed()
                                    ->orderBy('start_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($f) => [
                                        $f->id => $f->name.' — '.$f->location.' ('.$f->start_date->format('d/m/Y').')',
                                    ])
                                )
                                ->searchable()
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('educational_institution_id')
                                ->label('Institución Educativa')
                                ->options(fn () => EducationalInstitution::withoutTrashed()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($i) => [$i->id => $i->display_name])
                                )
                                ->searchable()
                                ->required()
                                ->live()
                                // No se habilita hasta que haya una feria seleccionada.
                                ->disabled(fn (Get $get): bool => blank($get('student_fair_id')))
                                // Al cambiar de institución se limpian los participantes ya
                                // marcados, que pertenecían a la institución anterior.
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('students', []);
                                    $set('teachers', []);
                                })
                                ->helperText(fn (Get $get): string => blank($get('student_fair_id'))
                                    ? 'Seleccione primero la feria.'
                                    : 'La institución debe estar registrada previamente para aparecer en el listado.')
                                ->rule(static::uniqueFairInstitutionRule()),

                            Forms\Components\DatePicker::make('participation_date')
                                ->label('Fecha de Participación')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                // El calendario se acota al periodo de la feria elegida.
                                ->minDate(fn (Get $get) => static::fairFor($get('student_fair_id'))?->start_date)
                                ->maxDate(fn (Get $get) => static::fairFor($get('student_fair_id'))?->end_date)
                                ->helperText(function (Get $get): ?string {
                                    $fair = static::fairFor($get('student_fair_id'));

                                    return $fair
                                        ? 'La feria va del '.$fair->start_date->format('d/m/Y').' al '.$fair->end_date->format('d/m/Y').'.'
                                        : null;
                                })
                                ->rule(static::participationWithinFairRule()),

                            Forms\Components\CheckboxList::make('students')
                                ->label('Estudiantes Participantes')
                                ->relationship('students', 'name')
                                ->options(fn (Get $get) => blank($get('educational_institution_id'))
                                    ? []
                                    : Student::withoutTrashed()
                                        ->where('educational_institution_id', $get('educational_institution_id'))
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make('students_empty_hint')
                                ->hiddenLabel()
                                ->visible(fn (Get $get): bool => blank($get('educational_institution_id')))
                                ->content(static::noInstitutionHint('Aún no ha seleccionado una institución educativa. Elíjala para ver sus estudiantes.'))
                                ->columnSpanFull(),

                            Forms\Components\CheckboxList::make('teachers')
                                ->label('Docentes Acompañantes')
                                ->relationship('teachers', 'name')
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
                                ->visible(fn (Get $get): bool => blank($get('educational_institution_id')))
                                ->content(static::noInstitutionHint('Aún no ha seleccionado una institución educativa. Elíjala para ver sus docentes.'))
                                ->columnSpanFull(),
                        ]),

                    // ── Tab: Aliados ──────────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Aliados Invitados')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Forms\Components\CheckboxList::make('actors')
                                ->label('Aliados Invitados')
                                ->relationship('actors', 'name')
                                ->options(fn () => Actor::withoutTrashed()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->columnSpanFull()
                                ->helperText('Entidades registradas en la base de actores de Ruta D. Campo opcional.'),
                        ]),

                    // ── Tab: Resultados ───────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Resultados')
                        ->icon('heroicon-o-chart-bar')
                        ->columns(2)
                        ->schema([

                            Forms\Components\Section::make('Articulaciones')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Radio::make('generated_articulations')
                                        ->label('¿Logró generar articulaciones con otros actores durante la feria?')
                                        ->boolean('Sí', 'No')
                                        ->inline()
                                        ->inlineLabel(false)
                                        ->required()
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\CheckboxList::make('articulation_actor_types')
                                        ->label('¿Con qué tipo de actor se articuló?')
                                        ->options(StudentFair::ARTICULATION_ACTOR_TYPE_OPTIONS)
                                        ->columns(2)
                                        ->required()
                                        ->visible(fn (Get $get) => (bool) $get('generated_articulations'))
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('articulations_count')
                                        ->label('¿Cuántas articulaciones concretas se generaron?')
                                        ->options(StudentFair::ARTICULATIONS_COUNT_OPTIONS)
                                        ->required()
                                        ->visible(fn (Get $get) => (bool) $get('generated_articulations')),
                                ]),

                            Forms\Components\Section::make('Encadenamiento Productivo')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Radio::make('identified_chain_opportunity')
                                        ->label('¿Se identificaron oportunidades de encadenamiento productivo?')
                                        ->boolean('Sí', 'No')
                                        ->inline()
                                        ->inlineLabel(false)
                                        ->required()
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('chain_link')
                                        ->label('¿En qué eslabón de la cadena se identificó la oportunidad?')
                                        ->options(StudentFair::CHAIN_LINK_OPTIONS)
                                        ->required()
                                        ->visible(fn (Get $get) => (bool) $get('identified_chain_opportunity'))
                                        ->columnSpanFull(),

                                    Forms\Components\CheckboxList::make('chain_actor_types')
                                        ->label('¿Con qué tipo de actor se generó el encadenamiento?')
                                        ->options(StudentFair::CHAIN_ACTOR_TYPE_OPTIONS)
                                        ->columns(2)
                                        ->required()
                                        ->visible(fn (Get $get) => (bool) $get('identified_chain_opportunity'))
                                        ->columnSpanFull(),
                                ]),

                            Forms\Components\Section::make('Ventas (Total / Balance)')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Radio::make('had_sales')
                                        ->label('¿Se realizaron ventas durante la feria?')
                                        ->boolean('Sí', 'No')
                                        ->inline()
                                        ->inlineLabel(false)
                                        ->required()
                                        ->live()
                                        ->columnSpanFull(),

                                    Forms\Components\Select::make('sales_range')
                                        ->label('Rango de ventas alcanzado')
                                        ->options(StudentFair::SALES_RANGE_OPTIONS)
                                        ->required(),

                                    Forms\Components\TextInput::make('exact_sales_amount')
                                        ->label('Monto exacto de ventas (opcional)')
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('$')
                                        ->visible(fn (Get $get) => (bool) $get('had_sales')),

                                    Forms\Components\Select::make('sales_balance')
                                        ->label('¿El balance de la feria fue positivo para el emprendimiento?')
                                        ->options(StudentFair::SALES_BALANCE_OPTIONS)
                                        ->required()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ── Tab: Experiencia ──────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Experiencia en la Feria')
                        ->icon('heroicon-o-star')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Radio::make('organization_rating')
                                ->label('¿Cómo califica la organización general de la feria?')
                                ->options(StudentFair::ORGANIZATION_RATING_OPTIONS)
                                ->required(),

                            Forms\Components\Radio::make('visitor_flow')
                                ->label('¿Cómo fue el flujo de visitantes en su stand?')
                                ->options(StudentFair::VISITOR_FLOW_OPTIONS)
                                ->required(),

                            Forms\Components\Radio::make('generated_contacts')
                                ->label('¿Pudo generar contactos o alianzas durante la feria?')
                                ->boolean('Sí', 'No')
                                ->inline()
                                ->inlineLabel(false)
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    // ── Tab: Evidencias ───────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Evidencias y Cierre')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción / Observación de la Feria')
                                ->required()
                                ->rows(5)
                                ->helperText('Resumen general de lo ocurrido en la feria')
                                ->columnSpanFull(),

                            Forms\Components\FileUpload::make('attendance_list_path')
                                ->label('Listado de Asistencia')
                                ->required()
                                ->disk('public')
                                ->directory('student-fair-attendance')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->maxSize(10240)
                                ->downloadable()
                                ->openable()
                                ->helperText('PDF o Excel con firmas/nombres de asistentes (máx. 10 MB)'),

                            Forms\Components\FileUpload::make('photos')
                                ->label('Registro Fotográfico')
                                ->required()
                                ->disk('public')
                                ->directory('student-fair-photos')
                                ->image()
                                ->multiple()
                                ->maxFiles(10)
                                ->maxSize(5120)
                                ->reorderable()
                                ->helperText('Máx. 5 MB por imagen. La georreferenciación queda respaldada por la latitud y longitud registradas en la feria; si la foto conserva metadata EXIF, no la elimine.'),
                        ]),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('participation_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fair.name')
                    ->label('Feria')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? '—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('participation_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('students_count')
                    ->label('Estudiantes')
                    ->counts('students')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('organization_rating')
                    ->label('Calificación')
                    ->formatStateUsing(fn ($state) => StudentFair::ORGANIZATION_RATING_OPTIONS[$state] ?? '—')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'excelente' => 'success',
                        'buena'     => 'info',
                        'regular'   => 'warning',
                        'deficiente'=> 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('had_sales')
                    ->label('Ventas')
                    ->getStateUsing(fn ($record) => $record->had_sales ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sí' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('student_fair_id')
                    ->label('Feria')
                    ->options(fn () => StudentFair::withoutTrashed()->orderBy('start_date', 'desc')->pluck('name', 'id')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar')
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
                            ->withFilename(fn () => 'participaciones-ferias-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with(['fair', 'educationalInstitution', 'students', 'teachers', 'actors', 'manager']))
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
                                ->withFilename(fn () => 'participaciones-ferias-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with(['fair', 'educationalInstitution', 'students', 'teachers', 'actors', 'manager']))
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
            'index'  => Pages\ListStudentFairParticipations::route('/'),
            'create' => Pages\CreateStudentFairParticipation::route('/create'),
            'edit'   => Pages\EditStudentFairParticipation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    /**
     * Devuelve la feria seleccionada en el formulario. Se ignoran los global
     * scopes para que al editar una participación antigua se siga resolviendo
     * la feria aunque esté deshabilitada o sea de otro año.
     */
    private static function fairFor(mixed $fairId): ?StudentFair
    {
        if (blank($fairId)) {
            return null;
        }

        return StudentFair::query()
            ->withoutGlobalScopes()
            ->find($fairId);
    }

    /**
     * La fecha de participación tiene que caer dentro del periodo de la feria.
     * Respalda en servidor lo que minDate/maxDate ya limitan en el calendario.
     */
    private static function participationWithinFairRule(): Closure
    {
        return static function (Get $get): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($get): void {
                $fair = static::fairFor($get('student_fair_id'));

                if (! $fair || blank($value)) {
                    return;
                }

                $timestamp = strtotime((string) $value);

                if ($timestamp === false) {
                    return;
                }

                $date  = date('Y-m-d', $timestamp);
                $start = $fair->start_date->toDateString();
                $end   = $fair->end_date->toDateString();

                if ($date < $start || $date > $end) {
                    $fail(sprintf(
                        'La fecha de participación debe estar entre el %s y el %s, que es el periodo de la feria.',
                        $fair->start_date->format('d/m/Y'),
                        $fair->end_date->format('d/m/Y'),
                    ));
                }
            };
        };
    }

    /**
     * Mensaje con icono y texto centrado que ocupa el lugar de la lista de
     * participantes mientras no haya institución educativa seleccionada.
     * Se coloca como Placeholder justo después del CheckboxList, de modo que
     * aparece debajo de su buscador sin tener que sobrescribir la vista de
     * Filament.
     */
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
     * Impide registrar dos participaciones para la misma feria con la misma
     * institución educativa. Replica a nivel de formulario el índice único
     * `unique_fair_institution`, para mostrar un mensaje legible en vez del
     * error SQL 1062. Se ignoran los global scopes porque el índice de BD
     * también cuenta los registros deshabilitados y los de otros años.
     */
    private static function uniqueFairInstitutionRule(): Closure
    {
        return static function (Get $get, ?Model $record): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                $fairId = $get('student_fair_id');

                if (blank($fairId) || blank($value)) {
                    return;
                }

                $exists = StudentFairParticipation::query()
                    ->withoutGlobalScopes()
                    ->where('student_fair_id', $fairId)
                    ->where('educational_institution_id', $value)
                    ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                    ->exists();

                if ($exists) {
                    $fail('Ya existe una participación registrada para esta feria con esta institución educativa.');
                }
            };
        };
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('fair_name')->heading('Feria')
                ->getStateUsing(fn ($record) => $record->fair?->name ?? ''),
            Column::make('fair_location')->heading('Municipio Feria')
                ->getStateUsing(fn ($record) => $record->fair?->location ?? ''),
            Column::make('institution')->heading('Institución Educativa')
                ->getStateUsing(fn ($record) => $record->educationalInstitution?->display_name ?? ''),
            Column::make('participation_date')->heading('Fecha Participación')
                ->getStateUsing(fn ($record) => $record->participation_date?->format('d/m/Y') ?? ''),
            Column::make('students')->heading('Estudiantes')
                ->getStateUsing(fn ($record) => $record->students->pluck('name')->implode(', ')),
            Column::make('teachers')->heading('Docentes')
                ->getStateUsing(fn ($record) => $record->teachers->pluck('name')->implode(', ')),
            Column::make('actors')->heading('Aliados Invitados')
                ->getStateUsing(fn ($record) => $record->actors->pluck('name')->implode(', ')),
            Column::make('generated_articulations')->heading('Articulaciones Generadas')
                ->getStateUsing(fn ($record) => $record->generated_articulations ? 'Sí' : 'No'),
            Column::make('articulation_actor_types')->heading('Tipo de Actor Articulado')
                ->getStateUsing(fn ($record) => collect($record->articulation_actor_types ?? [])
                    ->map(fn ($k) => StudentFair::ARTICULATION_ACTOR_TYPE_OPTIONS[$k] ?? $k)
                    ->implode(', ')),
            Column::make('articulations_count')->heading('N° Articulaciones')
                ->getStateUsing(fn ($record) => $record->articulations_count ? (StudentFair::ARTICULATIONS_COUNT_OPTIONS[$record->articulations_count] ?? $record->articulations_count) : ''),
            Column::make('identified_chain_opportunity')->heading('Oportunidad Encadenamiento')
                ->getStateUsing(fn ($record) => $record->identified_chain_opportunity ? 'Sí' : 'No'),
            Column::make('chain_link')->heading('Eslabón Cadena')
                ->getStateUsing(fn ($record) => $record->chain_link ? (StudentFair::CHAIN_LINK_OPTIONS[$record->chain_link] ?? $record->chain_link) : ''),
            Column::make('chain_actor_types')->heading('Tipo de Actor Encadenamiento')
                ->getStateUsing(fn ($record) => collect($record->chain_actor_types ?? [])
                    ->map(fn ($k) => StudentFair::CHAIN_ACTOR_TYPE_OPTIONS[$k] ?? $k)
                    ->implode(', ')),
            Column::make('had_sales')->heading('Tuvo Ventas')
                ->getStateUsing(fn ($record) => $record->had_sales ? 'Sí' : 'No'),
            Column::make('sales_range')->heading('Rango Ventas')
                ->getStateUsing(fn ($record) => $record->sales_range ? (StudentFair::SALES_RANGE_OPTIONS[$record->sales_range] ?? $record->sales_range) : ''),
            Column::make('exact_sales_amount')->heading('Monto Exacto Ventas'),
            Column::make('sales_balance')->heading('Balance Ventas')
                ->getStateUsing(fn ($record) => $record->sales_balance ? (StudentFair::SALES_BALANCE_OPTIONS[$record->sales_balance] ?? $record->sales_balance) : ''),
            Column::make('organization_rating')->heading('Calificación Organización')
                ->getStateUsing(fn ($record) => $record->organization_rating ? (StudentFair::ORGANIZATION_RATING_OPTIONS[$record->organization_rating] ?? $record->organization_rating) : ''),
            Column::make('visitor_flow')->heading('Flujo Visitantes')
                ->getStateUsing(fn ($record) => $record->visitor_flow ? (StudentFair::VISITOR_FLOW_OPTIONS[$record->visitor_flow] ?? $record->visitor_flow) : ''),
            Column::make('generated_contacts')->heading('Contactos Generados')
                ->getStateUsing(fn ($record) => $record->generated_contacts ? 'Sí' : 'No'),
            Column::make('description')->heading('Descripción'),
            // Rutas absolutas (con dominio) para poder abrir los adjuntos
            // directamente desde el Excel.
            Column::make('attendance_list_path')->heading(self::ATTENDANCE_HEADING)
                ->getStateUsing(fn ($record) => filled($record->attendance_list_path)
                    ? Storage::disk('public')->url($record->attendance_list_path)
                    : ''),
            Column::make('photos')->heading('Registro Fotográfico')
                ->getStateUsing(fn ($record) => collect($record->photos ?? [])
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

            // El listado de asistencia es un único archivo, así que su celda
            // se convierte en un enlace clicable. El registro fotográfico
            // puede traer varias URLs, por eso queda como texto.
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
