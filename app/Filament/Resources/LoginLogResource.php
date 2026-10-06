<?php

namespace App\Filament\Resources;

use App\Exports\FormattedExcelExport;
use App\Filament\Resources\LoginLogResource\Pages;
use App\Models\Entrepreneur;
use App\Models\LoginLog;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;

/**
 * Registro de inicios y cierres de sesión de todos los paneles. Solo lectura:
 * los registros los crea el sistema, nadie los edita ni los borra a mano.
 */
class LoginLogResource extends Resource
{
    protected static ?string $model = LoginLog::class;

    protected static ?string $navigationIcon   = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup  = 'Roles y Permisos';
    protected static ?string $navigationLabel  = 'Registro de sesiones';
    protected static ?string $modelLabel       = 'Sesión';
    protected static ?string $pluralModelLabel = 'Registro de sesiones';
    protected static ?int    $navigationSort   = 90;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewLoginLogs') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('login_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('N.º')
                    ->formatStateUsing(fn (int $state): string => '#'.$state)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Usuario')
                    ->description(fn (LoginLog $record): ?string => $record->user_email)
                    ->searchable(['user_name', 'user_email'])
                    ->sortable(),

                // Un usuario puede tener varios roles: cada uno sale como su propio badge.
                Tables\Columns\TextColumn::make('user_roles')
                    ->label('Rol')
                    ->badge()
                    ->placeholder('Sin rol')
                    ->color(fn (string $state): string => match ($state) {
                        'Admin'                       => 'danger',
                        LoginLog::ENTREPRENEUR_ROLE   => 'warning',
                        default                       => 'info',
                    }),

                Tables\Columns\TextColumn::make('panel')
                    ->label('Panel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => LoginLog::PANEL_LABELS[$state] ?? 'Desconocido')
                    ->color(fn (?string $state): string => match ($state) {
                        'dashboard'   => 'primary',
                        'eje'         => 'success',
                        'emprendedor' => 'warning',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->description(fn (LoginLog $record): string => $record->login_at->format('h:i A')
                        .($record->via_remember ? ' · Con "Recordarme"' : ''))
                    ->sortable(),

                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Cierre')
                    ->getStateUsing(fn (LoginLog $record) => $record->ended_at)
                    ->date('d/m/Y')
                    ->placeholder('En curso')
                    ->description(fn (LoginLog $record): ?string => $record->ended_at
                        ? $record->ended_at->format('h:i A')
                            .($record->status === LoginLog::STATUS_EXPIRED ? ' · Última actividad registrada' : '')
                        : null),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duración')
                    ->getStateUsing(fn (LoginLog $record): string => $record->duration_label),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (LoginLog $record): string => $record->status)
                    ->formatStateUsing(fn (string $state): string => LoginLog::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        LoginLog::STATUS_ACTIVE  => 'success',
                        LoginLog::STATUS_MANUAL  => 'gray',
                        LoginLog::STATUS_FORCED  => 'danger',
                        LoginLog::STATUS_EXPIRED => 'warning',
                        default                  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(50)
                    ->tooltip(fn (LoginLog $record): ?string => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('panel')
                    ->label('Panel')
                    ->options(LoginLog::PANEL_LABELS),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->searchable()
                    ->options(fn (): array => static::roleFilterOptions())
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->whereJsonContains('user_roles', $data['value'])),

                Tables\Filters\SelectFilter::make('user')
                    ->label('Usuario')
                    ->searchable()
                    ->options(fn (): array => static::userFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        [$type, $id] = array_pad(explode('|', $data['value'], 2), 2, null);

                        return $query
                            ->where('authenticatable_type', $type)
                            ->where('authenticatable_id', $id);
                    }),

                Tables\Filters\Filter::make('login_date')
                    ->label('Fecha')
                    ->form([
                        Forms\Components\Radio::make('mode')
                            ->label('Fecha de inicio de sesión')
                            ->options([
                                'exact' => 'Fecha exacta',
                                'range' => 'Rango de fechas',
                            ])
                            ->default('exact')
                            ->inline()
                            ->inlineLabel(false)
                            ->live(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn (Get $get): bool => $get('mode') !== 'range'),

                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->visible(fn (Get $get): bool => $get('mode') === 'range'),

                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (Get $get) => $get('from'))
                            ->visible(fn (Get $get): bool => $get('mode') === 'range'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['mode'] ?? 'exact') === 'range') {
                            return $query
                                ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('login_at', '>=', $date))
                                ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('login_at', '<=', $date));
                        }

                        return $query->when($data['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('login_at', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $format = fn ($date): string => Carbon::parse($date)->format('d/m/Y');

                        if (($data['mode'] ?? 'exact') === 'range') {
                            return array_values(array_filter([
                                filled($data['from'] ?? null) ? Indicator::make('Desde '.$format($data['from']))->removeField('from') : null,
                                filled($data['until'] ?? null) ? Indicator::make('Hasta '.$format($data['until']))->removeField('until') : null,
                            ]));
                        }

                        return filled($data['date'] ?? null)
                            ? [Indicator::make('Fecha: '.$format($data['date']))->removeField('date')]
                            : [];
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('activity')
                    ->label('')
                    ->tooltip('Ver acciones de esta sesión')
                    ->icon('heroicon-o-queue-list')
                    ->color('gray')
                    ->visible(fn (): bool => ActivityLogResource::canViewAny())
                    ->url(fn (LoginLog $record): string => ActivityLogResource::urlForSession($record->getKey())),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->exports([
                        FormattedExcelExport::make()
                            // Exporta lo que se ve: con los filtros y el orden aplicados.
                            ->useTableQuery()
                            ->withFilename(fn () => 'registro-sesiones-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns(self::exportColumns())
                            ->afterSheet(self::afterSheetCallback()),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->label('Exportar Excel')
                    ->exports([
                        FormattedExcelExport::make()
                            ->withFilename(fn () => 'registro-sesiones-'.now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns(self::exportColumns())
                            ->afterSheet(self::afterSheetCallback()),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginLogs::route('/'),
        ];
    }

    /**
     * Solo quienes han iniciado sesión alguna vez, agrupados por usuario real:
     * si alguien cambió de nombre, sigue apareciendo una sola vez.
     *
     * @return array<string, string>
     */
    private static function userFilterOptions(): array
    {
        return LoginLog::query()
            ->selectRaw('authenticatable_type, authenticatable_id, MAX(user_name) as user_name')
            ->whereNotNull('authenticatable_id')
            ->groupBy('authenticatable_type', 'authenticatable_id')
            ->orderBy('user_name')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->authenticatable_type.'|'.$row->authenticatable_id => sprintf(
                    '%s (%s)',
                    $row->user_name ?? 'Sin nombre',
                    $row->authenticatable_type === Entrepreneur::class ? 'Emprendedor' : 'Usuario',
                ),
            ])
            ->all();
    }

    /**
     * Roles que aparecen en el historial, no todos los del sistema: así el
     * filtro solo ofrece opciones que devuelven resultados.
     *
     * @return array<string, string>
     */
    private static function roleFilterOptions(): array
    {
        return LoginLog::query()
            ->whereNotNull('user_roles')
            ->pluck('user_roles')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $role) => [$role => $role])
            ->all();
    }

    private static function exportColumns(): array
    {
        return [
            Column::make('user_name')->heading('Usuario'),
            Column::make('user_email')->heading('Correo'),
            Column::make('user_roles')->heading('Rol')
                ->getStateUsing(fn ($record) => $record->roles_label),
            Column::make('panel')->heading('Panel')
                ->getStateUsing(fn ($record) => LoginLog::PANEL_LABELS[$record->panel] ?? 'Desconocido'),
            Column::make('login_at')->heading('Inicio de sesión')
                ->getStateUsing(fn ($record) => $record->login_at?->format('d/m/Y h:i A') ?? ''),
            Column::make('ended_at')->heading('Cierre de sesión')
                ->getStateUsing(fn ($record) => $record->ended_at?->format('d/m/Y h:i A') ?? 'En curso'),
            Column::make('duration')->heading('Duración')
                ->getStateUsing(fn ($record) => $record->duration_label),
            Column::make('status')->heading('Estado')
                ->getStateUsing(fn ($record) => LoginLog::STATUS_LABELS[$record->status] ?? $record->status),
            Column::make('via_remember')->heading('Con "Recordarme"')
                ->getStateUsing(fn ($record) => $record->via_remember ? 'Sí' : 'No'),
            Column::make('ip_address')->heading('IP'),
            Column::make('user_agent')->heading('Navegador'),
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
