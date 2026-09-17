<?php

namespace App\Filament\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Resources\BusinessCanvasResource\Pages;
use App\Models\BusinessCanvas;
use App\Models\Entrepreneur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

class BusinessCanvasResource extends Resource
{
    protected static ?string $model = BusinessCanvas::class;

    protected static ?string $navigationIcon   = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup  = 'Información general';
    protected static ?string $navigationLabel  = 'Canvas (Ruta 1)';
    protected static ?string $modelLabel       = 'Canvas';
    protected static ?string $pluralModelLabel = 'Canvas';
    protected static ?int    $navigationSort   = 6;

    public static function canViewAny(): bool   { return auth()->user()?->can('listBusinessCanvases') ?? false; }
    public static function canCreate(): bool    { return auth()->user()?->can('createBusinessCanvas') ?? false; }
    public static function canEdit($r): bool    { return auth()->user()?->can('editBusinessCanvas') ?? false; }
    public static function canDelete($r): bool  { return auth()->user()?->can('deleteBusinessCanvas') ?? false; }
    public static function canRestore($r): bool      { return auth()->user()?->can('deleteBusinessCanvas') ?? false; }
    public static function canForceDelete($r): bool   { return auth()->user()?->hasRole('Admin') ?? false; }
    // Canvas no aparece en el nav propio — se accede vía tab en Planes de Negocio
    public static function shouldRegisterNavigation(): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['entrepreneur.business', 'entrepreneur.city']);

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query;
    }

    // ── FORMULARIO ─────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Emprendedor (Ruta 1)')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('entrepreneur_id')
                        ->label('Emprendedor')
                        ->columnSpan(2)
                        ->options(fn () => static::getRoute1EntrepreneurOptions())
                        ->searchable()
                        ->optionsLimit(500)
                        ->required()
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated(true)
                        ->live()
                        ->rules(fn ($record) => [
                            \Illuminate\Validation\Rule::unique('business_canvases', 'entrepreneur_id')
                                ->where(fn ($q) => $q->whereYear('created_at', now()->year))
                                ->ignore($record?->id),
                        ])
                        ->validationMessages(['unique' => 'Este emprendedor ya tiene un Canvas registrado para el año en curso.']),

                    Forms\Components\Placeholder::make('business_name')
                        ->label('Emprendimiento')
                        ->content(fn (Get $get) => static::getEntrepreneurField($get('entrepreneur_id'), 'business')),

                    Forms\Components\Placeholder::make('city_name')
                        ->label('Municipio')
                        ->content(fn (Get $get) => static::getEntrepreneurField($get('entrepreneur_id'), 'city')),

                    Forms\Components\Placeholder::make('route_label')
                        ->label('Ruta')
                        ->content(fn (Get $get) => static::getEntrepreneurField($get('entrepreneur_id'), 'route')),

                    Forms\Components\Placeholder::make('maturity_label')
                        ->label('Nivel de madurez')
                        ->content(fn (Get $get) => static::getEntrepreneurField($get('entrepreneur_id'), 'maturity')),

                    Forms\Components\Placeholder::make('manager_label')
                        ->label('Gestor responsable')
                        ->content(fn (Get $get) => static::getEntrepreneurField($get('entrepreneur_id'), 'manager')),
                ]),

            Forms\Components\Section::make('Modelo de Negocios / Canvas')
                ->icon('heroicon-o-squares-2x2')
                ->description('Documenta las 6 dimensiones del modelo de negocio')
                ->columns(2)
                ->schema([
                    Forms\Components\Textarea::make('problem_identification')
                        ->label('El problema — ¿Qué quieres solucionar?')
                        ->helperText('¿Qué problema o necesidad has identificado en tu entorno?')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('business_idea')
                        ->label('Tu idea — Tu solución')
                        ->helperText('¿Cuál es tu idea de negocio y cómo busca solucionar el problema?')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('differentiator')
                        ->label('¿Qué te hace diferente?')
                        ->helperText('¿Qué hace diferente a tu producto o servicio frente a otras alternativas?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('achievements')
                        ->label('Tus resultados — Lo que has logrado')
                        ->helperText('¿Qué has logrado hasta ahora con tu emprendimiento?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('business_model_description')
                        ->label('¿Cómo funciona?')
                        ->helperText('¿Cómo funciona tu negocio y cómo generas ingresos?')
                        ->rows(4)
                        ->required(),

                    Forms\Components\Textarea::make('next_steps')
                        ->label('Tu próximo paso — ¿Qué necesitas ahora?')
                        ->helperText('¿Qué necesitas actualmente para fortalecer o hacer crecer tu negocio?')
                        ->rows(4)
                        ->required(),
                ]),

            Forms\Components\Section::make('Documentos')
                ->icon('heroicon-o-document-arrow-up')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('canvas_file_path')
                        ->label('Documento Canvas *')
                        ->disk('public')
                        ->directory('business-canvas')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->required()
                        ->downloadable()
                        ->openable()
                        ->helperText('Obligatorio — PDF, JPG o PNG (máx. 10 MB)'),

                    Forms\Components\TextInput::make('fire_pitch_video_url')
                        ->label('Video Fire Pitch (URL)')
                        ->url()
                        ->placeholder('https://youtube.com/...')
                        ->helperText('Opcional. Se solicitará obligatoriamente si el emprendimiento es marcado como potencial.'),
                ]),

            // Campo oculto para leer is_potential de forma reactiva
            Forms\Components\Hidden::make('is_potential'),

            Forms\Components\Section::make('Criterios de Evaluación')
                ->icon('heroicon-o-star')
                ->description('El emprendimiento ha sido marcado con potencial. Verifica que el video Fire Pitch esté registrado y completa los criterios de evaluación.')
                ->visible(fn (Get $get) => (string) $get('is_potential') === '1')
                ->schema([
                    Forms\Components\Placeholder::make('criterios_info')
                        ->label('')
                        ->content('Para continuar con el proceso de evaluación asegúrate de que el video Fire Pitch esté registrado en la sección Documentos. El emprendedor podrá ser marcado como priorizado para el Comité Evaluativo una vez verificados los criterios.'),
                ]),

        ]);
    }

    // ── TABLA ───────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('entrepreneur.full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->default('—'),

                Tables\Columns\TextColumn::make('canvas_file_path')
                    ->label('Canvas')
                    ->badge()
                    ->getStateUsing(fn ($record) => ! empty($record->canvas_file_path) ? 'Cargado' : 'Pendiente')
                    ->color(fn ($state) => $state === 'Cargado' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('fire_pitch_video_url')
                    ->label('Fire Pitch')
                    ->badge()
                    ->getStateUsing(fn ($record) => ! empty($record->fire_pitch_video_url) ? 'Cargado' : 'Pendiente')
                    ->color(fn ($state) => $state === 'Cargado' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('is_potential')
                    ->label('Potencial')
                    ->badge()
                    ->getStateUsing(fn ($record) => match (true) {
                        $record->is_potential === null => 'Pendiente',
                        (bool) $record->is_potential   => 'Sí',
                        default                        => 'No',
                    })
                    ->color(fn ($state) => match ($state) {
                        'Sí'      => 'success',
                        'No'      => 'danger',
                        default   => 'warning',
                    }),

                Tables\Columns\TextColumn::make('is_prioritized')
                    ->label('Priorizado')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->is_prioritized ? 'Sí' : 'No')
                    ->color(fn ($state) => $state === 'Sí' ? 'warning' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label('Municipio')
                    ->query(fn (Builder $query, array $data) =>
                        $data['value']
                            ? $query->whereHas('entrepreneur', fn ($q) => $q->where('city_id', $data['value']))
                            : $query
                    )
                    ->options(fn () => \App\Models\City::orderBy('name')->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('is_potential')
                    ->label('Potencial')
                    ->options(['1' => 'Sí', '0' => 'No'])
                    ->query(fn (Builder $query, array $data) =>
                        $data['value'] !== null && $data['value'] !== ''
                            ? $query->where('is_potential', (bool) $data['value'])
                            : $query
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->visible(fn () => auth()->user()->can('listBusinessCanvases')),

                Tables\Actions\Action::make('evaluar_potencial')
                    ->label('')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->tooltip('Evaluar Potencial')
                    ->visible(fn ($record) => $record->is_potential === null
                        && ! $record->trashed()
                        && auth()->user()->can('evaluarPotencialBusinessCanvas')
                    )
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-star')
                    ->modalIconColor('warning')
                    ->modalWidth('md')
                    ->modalHeading('Evaluar Potencial')
                    ->modalDescription('Determina si el emprendedor tiene potencial de crecimiento para ser priorizado en la siguiente etapa.')
                    ->modalSubmitActionLabel('Guardar evaluación')
                    ->form([
                        Forms\Components\Radio::make('is_potential')
                            ->label('¿El emprendedor tiene potencial?')
                            ->options([
                                '1' => 'Sí, tiene potencial',
                                '0' => 'No, no tiene el potencial requerido',
                            ])
                            ->required()
                            ->inline(false),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update(['is_potential' => (bool) $data['is_potential']]);
                        $label = (bool) $data['is_potential'] ? 'marcado como potencial' : 'marcado sin potencial';
                        Notification::make()
                            ->success()
                            ->title('Evaluación guardada')
                            ->body("El emprendimiento fue {$label} correctamente.")
                            ->send();
                    }),

                Tables\Actions\Action::make('marcar_priorizado')
                    ->label('')
                    ->icon('heroicon-o-bookmark')
                    ->color(fn ($record) => $record->is_prioritized ? 'gray' : 'warning')
                    ->tooltip(fn ($record) => $record->is_prioritized ? 'Quitar prioridad' : 'Marcar priorizado')
                    ->visible(fn ($record) => ($record->is_potential === true || $record->is_potential == 1)
                        && ! $record->trashed()
                        && auth()->user()->can('marcarPriorizadoBusinessCanvas')
                    )
                    ->form(function ($record): array {
                        if ($record->is_prioritized || ! empty($record->fire_pitch_video_url)) {
                            return [];
                        }
                        return [
                            Forms\Components\TextInput::make('fire_pitch_video_url')
                                ->label('Video Fire Pitch (URL)')
                                ->helperText('Es obligatorio para marcar el emprendimiento como priorizado.')
                                ->url()
                                ->required()
                                ->maxLength(500),
                        ];
                    })
                    ->requiresConfirmation()
                    ->modalIcon(fn ($record) => $record->is_prioritized ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                    ->modalIconColor(fn ($record) => $record->is_prioritized ? 'gray' : 'warning')
                    ->modalWidth('md')
                    ->modalHeading(fn ($record) => $record->is_prioritized ? 'Quitar prioridad' : 'Marcar como priorizado')
                    ->modalDescription(fn ($record) => $record->is_prioritized
                        ? '¿Estás seguro de que deseas quitar la prioridad a este emprendimiento?'
                        : (empty($record->fire_pitch_video_url)
                            ? 'Ingresa la URL del video Fire Pitch para continuar. Este campo es obligatorio para priorizar el emprendimiento.'
                            : '¿Estás seguro de que deseas marcar este emprendimiento como priorizado?'
                        )
                    )
                    ->modalSubmitActionLabel(fn ($record) => $record->is_prioritized ? 'Sí, quitar prioridad' : 'Sí, priorizar')
                    ->action(function ($record, array $data): void {
                        $newState = ! $record->is_prioritized;
                        $updateData = ['is_prioritized' => $newState];
                        if (! empty($data['fire_pitch_video_url'])) {
                            $updateData['fire_pitch_video_url'] = $data['fire_pitch_video_url'];
                        }
                        $record->update($updateData);

                        Notification::make()
                            ->success()
                            ->title($newState ? 'Emprendedor priorizado' : 'Prioridad removida')
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar canvas')
                    ->visible(fn ($record) => ! $record->trashed()
                        && auth()->user()->can('editBusinessCanvas')
                        && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(fn ($record) => ! $record->trashed()
                        && auth()->user()->can('deleteBusinessCanvas')
                        && (auth()->user()->hasRole('Admin') || $record->manager_id === auth()->id())
                    ),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar canvas')
                    ->visible(fn ($record) => $record->trashed() && auth()->user()->can('deleteBusinessCanvas')),

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
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'canvas-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with(self::exportWith()))
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
                                ->withFilename(fn () => 'canvas-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn ($query) => $query->with(self::exportWith()))
                                ->withColumns(self::exportColumns())
                                ->afterSheet(self::afterSheetCallback()),
                        ]),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('deleteBusinessCanvas')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Admin')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()->can('deleteBusinessCanvas')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBusinessCanvases::route('/'),
            'create' => Pages\CreateBusinessCanvas::route('/create'),
            'edit'   => Pages\EditBusinessCanvas::route('/{record}/edit'),
        ];
    }

    private static function exportWith(): array
    {
        return [
            'entrepreneur.business',
            'entrepreneur.city',
            'manager',
        ];
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('entrepreneur_name')
                ->heading('Emprendedor')
                ->getStateUsing(fn ($record) => $record->entrepreneur?->full_name ?? ''),

            Column::make('business_name')
                ->heading('Emprendimiento')
                ->getStateUsing(fn ($record) => $record->entrepreneur?->business?->business_name ?? ''),

            Column::make('city_name')
                ->heading('Municipio')
                ->getStateUsing(fn ($record) => $record->entrepreneur?->city?->name ?? ''),

            Column::make('problem_identification')
                ->heading('El problema'),

            Column::make('business_idea')
                ->heading('Tu idea / Solución'),

            Column::make('differentiator')
                ->heading('¿Qué te hace diferente?'),

            Column::make('achievements')
                ->heading('Resultados logrados'),

            Column::make('business_model_description')
                ->heading('¿Cómo funciona?'),

            Column::make('next_steps')
                ->heading('Próximo paso / Necesidades'),

            Column::make('canvas_file_path')
                ->heading('Documento Canvas')
                ->getStateUsing(fn ($record) => ! empty($record->canvas_file_path) ? 'Sí' : 'No'),

            Column::make('fire_pitch_video_url')
                ->heading('Video Fire Pitch'),

            Column::make('is_potential')
                ->heading('Potencial')
                ->getStateUsing(fn ($record) => match (true) {
                    $record->is_potential === null => 'Pendiente',
                    (bool) $record->is_potential   => 'Sí',
                    default                        => 'No',
                }),

            Column::make('is_prioritized')
                ->heading('Priorizado')
                ->getStateUsing(fn ($record) => $record->is_prioritized ? 'Sí' : 'No'),

            Column::make('manager_name')
                ->heading('Registrado por')
                ->getStateUsing(fn ($record) => $record->manager?->name ?? ''),

            Column::make('created_at')
                ->heading('Fecha Registro')
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
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E40AF'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ]);

            if ($lastRow > 1) {
                $sheet->getStyle('A2:'.$lastCol.$lastRow)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            }

            for ($i = 1; $i <= $lastColIndex; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }

            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:'.$lastCol.'1');
        };
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return (string) $query->count();
    }

    // ── HELPERS ─────────────────────────────────────────────────────────────────

    public static function getRoute1EntrepreneurOptions(): array
    {
        $route1Ids = Entrepreneur::getIdsByRoute(['route_1']);

        return Entrepreneur::whereIn('id', $route1Ids)
            ->when(
                ! auth()->user()->hasRole(['Admin', 'Viewer']),
                fn ($q) => $q->where('manager_id', auth()->id())
            )
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->toArray();
    }

    private static function getEntrepreneurField(?int $id, string $field): string
    {
        if (! $id) return '—';
        $e = Entrepreneur::withoutGlobalScopes()
            ->with([
                'business'  => fn ($q) => $q->withoutGlobalScopes(),
                'city',
                'manager',
            ])
            ->find($id);
        if (! $e) return '—';

        return match ($field) {
            'business' => $e->business?->business_name ?? '—',
            'city'     => $e->city?->name ?? '—',
            'manager'  => $e->manager?->name ?? '—',
            'route'    => match ($e->getRoute()) {
                'route_1' => 'Ruta 1',
                'route_2' => 'Ruta 2',
                'route_3' => 'Ruta 3',
                default   => 'Sin diagnóstico',
            },
            'maturity' => static::getMaturityLabel($e),
            default    => '—',
        };
    }

    private static function getMaturityLabel(Entrepreneur $e): string
    {
        $diagnosis = $e->businessDiagnoses()->latest()->first();
        if (! $diagnosis || $diagnosis->total_score === null) {
            return 'Sin diagnóstico';
        }
        $year  = $diagnosis->created_at?->year ?? now()->year;
        $level = \App\Support\MaturityScale::getLevelForScore((int) $diagnosis->total_score, $year);
        return $level['label'] ?? '—';
    }
}
