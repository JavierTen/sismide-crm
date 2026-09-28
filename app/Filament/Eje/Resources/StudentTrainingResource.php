<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentTrainingResource\Pages;
use App\Models\Actor;
use App\Models\EntityContact;
use App\Models\StudentTraining;
use App\Scopes\YearColumnScope;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class StudentTrainingResource extends Resource
{
    protected static ?string $model = StudentTraining::class;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $navigationLabel = 'Capacitaciones';
    protected static ?string $modelLabel      = 'Capacitación';
    protected static ?string $pluralModelLabel = 'Capacitaciones';
    protected static ?int    $navigationSort  = 1;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentTrainings') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentTraining') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentTraining') ?? false; }
    private static function userCanDelete(): bool { return auth()->user()?->can('deleteStudentTraining') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Información General')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Módulo y Tema')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ej: Orientación emprendedora')
                        ->helperText('Nombre oficial del módulo o capacitación')
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null)
                        ->rule(static::uniqueNameRule())
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('objective')
                        ->label('Objetivo / Descripción')
                        ->required()
                        ->rows(4)
                        ->helperText('Alcance general de la capacitación')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Facilitadores')
                ->icon('heroicon-o-user-group')
                ->description('Entidades y capacitadores tomados de la base de Ruta D.')
                ->schema([
                    Forms\Components\Repeater::make('facilitators')
                        ->hiddenLabel()
                        ->relationship()
                        ->addActionLabel('+ Agregar otro facilitador')
                        ->minItems(1)
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Forms\Components\Select::make('actor_id')
                                ->label('Entidad')
                                ->options(fn () => Actor::query()
                                    ->withoutGlobalScope(YearColumnScope::class)
                                    ->withoutTrashed()
                                    ->orderBy('name')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                // Cambiar de entidad invalida el capacitador ya elegido.
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('entity_contact_id', null)),

                            Forms\Components\Select::make('entity_contact_id')
                                ->label('Capacitador')
                                ->options(fn (Forms\Get $get) => blank($get('actor_id'))
                                    ? []
                                    : EntityContact::query()
                                        ->where('entity_id', $get('actor_id'))
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->helperText(fn (Forms\Get $get): ?string => blank($get('actor_id'))
                                    ? 'Seleccione primero la entidad.'
                                    : null),

                            Forms\Components\Placeholder::make('contact_details')
                                ->label('Datos del capacitador')
                                ->columnSpanFull()
                                ->visible(fn (Forms\Get $get): bool => filled($get('entity_contact_id')))
                                ->content(function (Forms\Get $get): string {
                                    $contact = EntityContact::find($get('entity_contact_id'));

                                    if (! $contact) {
                                        return '—';
                                    }

                                    return collect([$contact->role, $contact->phone, $contact->email])
                                        ->filter()
                                        ->implode('  ·  ') ?: 'Sin datos de contacto registrados.';
                                }),
                        ]),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Módulo y Tema')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('facilitators_count')
                    ->label('Capacitadores')
                    ->counts('facilitators')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Sesiones')
                    ->counts('sessions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('objective')
                    ->label('Objetivo')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->hasRole('Admin'))
                    ->modalHeading('Eliminar capacitación definitivamente')
                    ->modalDescription(fn ($record) => static::forceDeleteWarning($record))
                    ->modalSubmitActionLabel('Sí, eliminar definitivamente'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'capacitaciones-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with(['facilitators.entity', 'facilitators.contact', 'manager']))
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
                                ->withFilename(fn () => 'capacitaciones-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with(['facilitators.entity', 'facilitators.contact', 'manager']))
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
            'index'  => Pages\ListStudentTrainings::route('/'),
            'create' => Pages\CreateStudentTraining::route('/create'),
            'edit'   => Pages\EditStudentTraining::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    /**
     * El módulo y tema no se repite. Ignora mayúsculas y espacios sobrantes y
     * respeta el año en contexto, de modo que una capacitación anual puede
     * volver a registrarse en un año distinto.
     */
    private static function uniqueNameRule(): Closure
    {
        return static function (?StudentTraining $record): Closure {
            return static function (string $attribute, $value, Closure $fail) use ($record): void {
                $name = mb_strtoupper(trim((string) $value));

                if ($name === '') {
                    return;
                }

                $existing = StudentTraining::query()
                    ->withoutGlobalScopes([SoftDeletingScope::class])
                    ->whereRaw('UPPER(TRIM(name)) = ?', [$name])
                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                    ->first();

                if (! $existing) {
                    return;
                }

                $fail($existing->trashed()
                    ? 'Ya existe una capacitación con este módulo y tema, pero está deshabilitada. Restáurela o use otro nombre.'
                    : 'Ya existe una capacitación registrada con este módulo y tema.');
            };
        };
    }

    private static function forceDeleteWarning(StudentTraining $record): string
    {
        $count = $record->sessions()->withoutGlobalScopes()->count();

        if ($count === 0) {
            return 'Esta capacitación no tiene sesiones registradas. La acción no se puede deshacer.';
        }

        $detail = $count === 1
            ? 'Se eliminará también 1 sesión asociada'
            : sprintf('Se eliminarán también %d sesiones asociadas', $count);

        return $detail.' a esta capacitación, junto con su asistencia y evidencias. La acción no se puede deshacer.';
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('name')->heading('Módulo y Tema'),
            Column::make('objective')->heading('Objetivo / Descripción'),
            Column::make('facilitators')->heading('Facilitadores')
                ->getStateUsing(fn ($record) => $record->facilitators
                    ->map(fn ($facilitator) => ($facilitator->contact?->name ?? '—').' ('.($facilitator->entity?->name ?? '—').')')
                    ->implode("\n")),
            Column::make('sessions_count')->heading('N° Sesiones')
                ->getStateUsing(fn ($record) => $record->sessions()->count()),
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

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastCol.'1');
        };
    }
}
