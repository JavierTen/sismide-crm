<?php

namespace App\Filament\Eje\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Eje\Resources\StudentFairResource\Pages;
use App\Models\StudentFair;
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

class StudentFairResource extends Resource
{
    protected static ?string $model = StudentFair::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Ferias Estudiantiles';
    protected static ?string $navigationLabel = 'Ferias';
    protected static ?string $modelLabel      = 'Feria Estudiantil';
    protected static ?string $pluralModelLabel = 'Ferias Estudiantiles';
    protected static ?int    $navigationSort  = 1;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentFairs') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentFair') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentFair') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentFair') ?? false; }

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
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre de la Feria')
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->dehydrateStateUsing(fn (?string $s) => $s ? mb_strtoupper($s) : null)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('location')
                        ->label('Municipio / Localidad')
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->dehydrateStateUsing(fn (?string $s) => $s ? mb_strtoupper($s) : null),

                    Forms\Components\TextInput::make('address')
                        ->label('Dirección')
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->dehydrateStateUsing(fn (?string $s) => $s ? mb_strtoupper($s) : null),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Fecha de Inicio')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Fecha de Fin')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('start_date'),
                ]),

            Forms\Components\Section::make('Georreferenciación')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('latitude')
                        ->label('Latitud')
                        ->numeric()
                        ->step(0.00000001)
                        ->placeholder('Ej: 10.96854'),

                    Forms\Components\TextInput::make('longitude')
                        ->label('Longitud')
                        ->numeric()
                        ->step(0.00000001)
                        ->placeholder('Ej: -74.80159'),
                ]),

            Forms\Components\Section::make('Organización')
                ->icon('heroicon-o-user-circle')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('organizer_name')
                        ->label('Nombre del Organizador')
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase'])
                        ->dehydrateStateUsing(fn (?string $s) => $s ? mb_strtoupper($s) : null),

                    Forms\Components\TextInput::make('organizer_position')
                        ->label('Cargo')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('organizer_phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('organizer_email')
                        ->label('Correo')
                        ->email()
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Observaciones')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    Forms\Components\Textarea::make('observations')
                        ->label('')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Feria')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Municipio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('participations_count')
                    ->label('Participaciones')
                    ->counts('participations')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('organizer_name')
                    ->label('Organizador')
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
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn () => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'ferias-estudiantiles-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
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
                                ->withFilename(fn () => 'ferias-estudiantiles-'.now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
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
            'index'  => Pages\ListStudentFairs::route('/'),
            'create' => Pages\CreateStudentFair::route('/create'),
            'edit'   => Pages\EditStudentFair::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('name')->heading('Nombre Feria'),
            Column::make('location')->heading('Municipio'),
            Column::make('address')->heading('Dirección'),
            Column::make('start_date')->heading('Fecha Inicio')
                ->getStateUsing(fn ($r) => $r->start_date?->format('d/m/Y') ?? ''),
            Column::make('end_date')->heading('Fecha Fin')
                ->getStateUsing(fn ($r) => $r->end_date?->format('d/m/Y') ?? ''),
            Column::make('organizer_name')->heading('Organizador'),
            Column::make('organizer_position')->heading('Cargo'),
            Column::make('organizer_phone')->heading('Teléfono'),
            Column::make('organizer_email')->heading('Correo'),
            Column::make('observations')->heading('Observaciones'),
            Column::make('manager_name')->heading('Registrado por')
                ->getStateUsing(fn ($r) => $r->manager?->name ?? ''),
            Column::make('created_at')->heading('Fecha Registro')
                ->getStateUsing(fn ($r) => $r->created_at?->format('d/m/Y H:i') ?? ''),
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
