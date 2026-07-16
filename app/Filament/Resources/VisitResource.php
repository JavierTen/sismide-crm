<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

//Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas';

    protected static ?int $navigationSort = 2;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listVisits');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createVisit');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editVisit');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteVisit');
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
                Forms\Components\Section::make('Agendamiento de Visitas')
                    ->description('Registra una nueva visita, fecha, hora y tipo. Elige el emprendedor para autocompletar datos relacionados.')
                    ->icon('heroicon-o-calendar')
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
                                    ) // ajusta el campo mostrable si usas otro
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull()
                                    ->reactive()
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->helperText(
                                        fn(string $operation): string =>
                                        $operation === 'edit'
                                            ? 'El emprendedor no puede ser modificado después de crear la visita.'
                                            : 'Selecciona el emprendedor al que se le agendará la visita.'
                                    ),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Placeholder::make('business_name')
                                            ->label('Nombre del Emprendimiento')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with('business')->find($get('entrepreneur_id'))
                                                )?->business?->business_name ?? '----'
                                            )
                                            ->reactive(),
                                        Forms\Components\Placeholder::make('city_name')
                                            ->label('Municipio')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with(['city', 'manager', 'business'])
                                                        ->find($get('entrepreneur_id'))
                                                )?->city?->name ?? '----'
                                            )
                                            ->reactive(),
                                        Forms\Components\Placeholder::make('user_name')
                                            ->label('Gestor')
                                            ->content(
                                                fn($get) =>
                                                optional(
                                                    \App\Models\Entrepreneur::with(['city', 'manager', 'business'])
                                                        ->find($get('entrepreneur_id'))
                                                )?->manager?->name ?? '----'
                                            )
                                            ->reactive(),
                                    ]),

                                Forms\Components\Placeholder::make('alerta_historial_visita')
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
                                            ->distinct()
                                            ->orderBy('year')
                                            ->pluck('year')
                                            ->toArray();

                                        if (empty($years)) return '';

                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300">'
                                            . '<p class="font-semibold">Emprendedor con historial previo</p>'
                                            . '<p class="mt-0.5">Este emprendedor ya participó en vigencia(s) anterior(es): <strong>' . implode(', ', $years) . '</strong>.</p>'
                                            . '</div>'
                                        );
                                    })
                                    ->reactive()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('visit_type')
                                    ->label('Tipo de visita')
                                    ->required()
                                    ->options([
                                        'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                        'caracterizacion'    => 'Visita de Caracterización',
                                        'diagnostico'        => 'Visita levantamiento de Diagnóstico',
                                        'seguimiento'        => 'Visita de Seguimiento',
                                    ])
                                    ->placeholder('Seleccione el tipo de visita')
                                    ->columnSpanFull()
                                    ->helperText('Elige el propósito principal de la visita.'),

                                Forms\Components\DatePicker::make('visit_date')
                                    ->label('Fecha de la visita')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Selecciona la fecha programada.'),

                                Forms\Components\TimePicker::make('visit_time')
                                    ->label('Hora de la visita')
                                    ->required()
                                    ->placeholder('HH:MM')
                                    ->helperText('Hora prevista para la visita.'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Resultado y Reagendamiento')
                    ->description('Indica si la visita fortaleció al emprendedor y maneja reagendamientos.')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('strengthened')
                                    ->label('Se ha fortalecido')
                                    ->helperText('Indica si la visita logró fortalecer al emprendedor.')
                                    ->default(false)
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Toggle::make('rescheduled')
                                    ->label('Reagendamiento')
                                    ->helperText('Marcar si la visita debe ser reagendada (ej. por ausencia del emprendedor).')
                                    ->default(false)
                                    ->reactive() // <- importante: permite que los campos dependientes se actualicen
                                    ->inline(false)
                                    ->hiddenOn('create')
                                    ->onIcon('heroicon-m-arrow-path')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Si se desmarca reagendamiento, limpiar el motivo
                                        if (! $state) {
                                            $set('reschedule_reason', null);
                                        }
                                    }),
                            ]),

                        // Motivo solo visible y obligatorio si rescheduled = true
                        Forms\Components\Textarea::make('reschedule_reason')
                            ->label('Motivo de Reagendamiento')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn($get) => (bool) $get('rescheduled'))
                            ->required(fn($get) => (bool) $get('rescheduled'))
                            ->placeholder('Especifique el motivo por el cual se reagenda la visita (por ejemplo: emprendedor ausente, clima, etc.)'),

                        Forms\Components\Select::make('new_visit_type')
                            ->label('Tipo de visita (reagendada)')
                            ->options([
                                'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                'caracterizacion'    => 'Visita de Caracterización',
                                'diagnostico'        => 'Visita levantamiento de Diagnóstico',
                                'seguimiento'        => 'Visita de Seguimiento',
                            ])
                            ->visible(fn($get) => (bool) $get('rescheduled'))
                            ->required(fn($get) => (bool) $get('rescheduled')),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('new_visit_date')
                                    ->label('Nueva Fecha (reagendada)')
                                    ->displayFormat('d/m/Y')
                                    ->visible(fn($get) => (bool) $get('rescheduled'))
                                    ->required(fn($get) => (bool) $get('rescheduled')),

                                Forms\Components\TimePicker::make('new_visit_time')
                                    ->label('Nueva Hora (reagendada)')
                                    ->visible(fn($get) => (bool) $get('rescheduled'))
                                    ->required(fn($get) => (bool) $get('rescheduled')),
                            ]),




                    ])
                    ->collapsible()
                    ->persistCollapsed(),

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

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Fecha visita')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),

                Tables\Columns\TextColumn::make('visit_time')
                    ->label('Hora visita')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),

                Tables\Columns\BadgeColumn::make('visit_result')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aceptada'    => 'Aceptada',
                        'no_aceptada' => 'No aceptada',
                        'sin_persona' => 'Sin persona',
                        null, ''      => 'Programada',
                        default       => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'aceptada'    => 'success',
                        'no_aceptada' => 'danger',
                        'sin_persona' => 'warning',
                        default       => 'gray',
                    })
                    ->action(
                        Tables\Actions\Action::make('ver_resultado_visita')
                            ->modalHeading('Resultado de la visita')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->form(fn ($record) => [
                                Forms\Components\Placeholder::make('sin_resultado_info')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString(
                                        '<div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">'
                                        . 'Esta visita aún no tiene resultado registrado.'
                                        . '</div>'
                                    ))
                                    ->visible(! $record->visit_result),

                                Forms\Components\Placeholder::make('visit_result_label')
                                    ->label('Resultado de la visita')
                                    ->content(match ($record->visit_result) {
                                        'aceptada'    => 'Visita aceptada',
                                        'no_aceptada' => 'Visita no aceptada',
                                        'sin_persona' => 'No había nadie en la unidad productiva',
                                        default       => '—',
                                    })
                                    ->visible((bool) $record->visit_result),

                                Forms\Components\Placeholder::make('topics_and_commitment_label')
                                    ->label('Temas tratados y compromisos')
                                    ->content($record->topics_and_commitment ?? '—')
                                    ->visible((bool) $record->visit_result),

                                Forms\Components\FileUpload::make('evidence_path')
                                    ->label('Evidencias adjuntas')
                                    ->disk('public')
                                    ->multiple()
                                    ->downloadable()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default($record->evidence_path ?? [])
                                    ->visible($record->visit_result === 'aceptada' && ! empty($record->evidence_path)),
                            ])
                    ),
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
                    ->tooltip('Editar emprendedor')
                    ->visible(
                        fn($record) =>
                        !$record->trashed() &&
                            static::userCanEdit() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\Action::make('confirmar_visita')
                    ->label('')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip('Confirmar resultado de visita')
                    ->visible(
                        fn ($record) =>
                            ! $record->trashed() &&
                            ! $record->visit_result &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    )
                    ->modalHeading('Confirmar resultado de visita')
                    ->modalSubmitActionLabel('Guardar resultado')
                    ->form([
                        Forms\Components\Select::make('visit_result')
                            ->label('Resultado de la visita')
                            ->required()
                            ->live()
                            ->options([
                                'aceptada'    => 'Visita aceptada',
                                'no_aceptada' => 'Visita no aceptada',
                                'sin_persona' => 'No había nadie en la unidad productiva',
                            ])
                            ->placeholder('Seleccione el resultado'),

                        Forms\Components\Textarea::make('topics_and_commitment')
                            ->label('Temas tratados y compromisos')
                            ->required()
                            ->rows(5)
                            ->helperText('Escriba mínimo 50 palabras describiendo los temas tratados y compromisos adquiridos.')
                            ->placeholder('Describa detalladamente los temas abordados durante la visita y los compromisos acordados con el emprendedor...')
                            ->rules([
                                fn () => function ($attribute, $value, $fail) {
                                    $count = str_word_count(strip_tags($value ?? ''));
                                    if ($count < 50) {
                                        $fail("Debe escribir mínimo 50 palabras. Actualmente tiene {$count}.");
                                    }
                                },
                            ]),

                        Forms\Components\FileUpload::make('evidence_path')
                            ->label('Evidencia de la visita')
                            ->multiple()
                            ->acceptedFileTypes(['image/*', 'application/pdf', '.doc', '.docx'])
                            ->maxSize(10240)
                            ->directory('visitas/evidencias')
                            ->visible(fn ($get) => $get('visit_result') === 'aceptada')
                            ->helperText('Adjunte el acta de compromiso u otros soportes de la visita (PDF, imágenes, Word).'),
                    ])
                    ->action(function (Visit $record, array $data): void {
                        $record->update([
                            'visit_result'          => $data['visit_result'],
                            'topics_and_commitment' => $data['topics_and_commitment'],
                            'evidence_path'         => $data['evidence_path'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Visita confirmada')
                            ->body('El resultado de la visita quedó registrado correctamente.')
                            ->success()
                            ->send();
                    }),

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
                    ->tooltip('Restaurar visita')
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
                            ->withFilename(fn() => 'visitas-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'entrepreneur.business',
                                'entrepreneur.city',
                                'manager',
                                'originalVisit',
                            ]))
                            ->withColumns([
                                // === AGENDAMIENTO DE VISITAS ===
                                Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                Column::make('entrepreneur.city.name')->heading('Municipio'),
                                Column::make('manager.name')->heading('Gestor'),

                                Column::make('visit_type')->heading('Tipo de Visita')->formatStateUsing(fn($state) => match ($state) {
                                    'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                    'caracterizacion' => 'Visita de Caracterización',
                                    'diagnostico' => 'Visita levantamiento de Diagnóstico',
                                    'seguimiento' => 'Visita de Seguimiento',
                                    default => $state,
                                }),
                                Column::make('visit_date')->heading('Fecha de Visita')->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
                                Column::make('visit_time')->heading('Hora de Visita'),

                                // === RESULTADO Y REAGENDAMIENTO ===
                                Column::make('visit_result')->heading('Resultado')->formatStateUsing(fn($state) => match ($state) {
                                    'aceptada'    => 'Visita aceptada',
                                    'no_aceptada' => 'Visita no aceptada',
                                    'sin_persona' => 'No había nadie en la unidad productiva',
                                    null, ''      => 'Programada',
                                    default       => $state,
                                }),
                                Column::make('topics_and_commitment')->heading('Temas tratados y compromisos'),
                                Column::make('strengthened')->heading('Se ha fortalecido')->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('rescheduled')->heading('Reagendada')->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                Column::make('reschedule_reason')->heading('Motivo de Reagendamiento'),

                                // === DATOS DE REAGENDAMIENTO (si aplica) ===
                                Column::make('originalVisit.visit_type')->heading('Tipo Visita Original')->formatStateUsing(fn($state) => $state ? match ($state) {
                                    'asistencia_tecnica' => 'Asistencia técnica',
                                    'caracterizacion' => 'Caracterización',
                                    'diagnostico' => 'Diagnóstico',
                                    'seguimiento' => 'Seguimiento',
                                    default => $state,
                                } : ''),
                                Column::make('originalVisit.visit_date')->heading('Fecha Original')->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
                                Column::make('originalVisit.visit_time')->heading('Hora Original'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                Column::make('updated_at')->heading('Última Actualización')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
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
                                ->withFilename(fn() => 'visitas-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'entrepreneur.business',
                                    'entrepreneur.city',
                                    'manager',
                                    'originalVisit',
                                ]))
                                ->withColumns([
                                    // === AGENDAMIENTO DE VISITAS ===
                                    Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                    Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                    Column::make('entrepreneur.city.name')->heading('Municipio'),
                                    Column::make('manager.name')->heading('Gestor'),

                                    Column::make('visit_type')->heading('Tipo de Visita')->formatStateUsing(fn($state) => match ($state) {
                                        'asistencia_tecnica' => 'Visita de Asistencia técnica',
                                        'caracterizacion' => 'Visita de Caracterización',
                                        'diagnostico' => 'Visita levantamiento de Diagnóstico',
                                        'seguimiento' => 'Visita de Seguimiento',
                                        default => $state,
                                    }),
                                    Column::make('visit_date')->heading('Fecha de Visita')->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
                                    Column::make('visit_time')->heading('Hora de Visita'),

                                    // === RESULTADO Y REAGENDAMIENTO ===
                                    Column::make('visit_result')->heading('Resultado')->formatStateUsing(fn($state) => match ($state) {
                                        'aceptada'    => 'Visita aceptada',
                                        'no_aceptada' => 'Visita no aceptada',
                                        'sin_persona' => 'No había nadie en la unidad productiva',
                                        null, ''      => 'Programada',
                                        default       => $state,
                                    }),
                                    Column::make('topics_and_commitment')->heading('Temas tratados y compromisos'),
                                    Column::make('strengthened')->heading('Se ha fortalecido')->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                    Column::make('rescheduled')->heading('Reagendada')->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),
                                    Column::make('reschedule_reason')->heading('Motivo de Reagendamiento'),

                                    // === DATOS DE REAGENDAMIENTO (si aplica) ===
                                    Column::make('originalVisit.visit_type')->heading('Tipo Visita Original')->formatStateUsing(fn($state) => $state ? match ($state) {
                                        'asistencia_tecnica' => 'Asistencia técnica',
                                        'caracterizacion' => 'Caracterización',
                                        'diagnostico' => 'Diagnóstico',
                                        'seguimiento' => 'Seguimiento',
                                        default => $state,
                                    } : ''),
                                    Column::make('originalVisit.visit_date')->heading('Fecha Original')->formatStateUsing(fn($state) => $state?->format('d/m/Y')),
                                    Column::make('originalVisit.visit_time')->heading('Hora Original'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('created_at')->heading('Fecha Registro')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                    Column::make('updated_at')->heading('Última Actualización')->formatStateUsing(fn($state) => $state->format('d/m/Y H:i')),
                                ]),
                        ]),
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
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
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
