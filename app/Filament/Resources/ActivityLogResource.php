<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Activity;
use App\Models\LoginLog;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Historial de acciones: qué hizo cada usuario, cuándo y desde qué sesión.
 * Solo lectura: los registros los genera el sistema.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon   = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup  = 'Roles y Permisos';
    protected static ?string $navigationLabel  = 'Historial de acciones';
    protected static ?string $modelLabel       = 'Acción';
    protected static ?string $pluralModelLabel = 'Historial de acciones';
    protected static ?int    $navigationSort   = 91;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewActivityLogs') ?? false;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['causer', 'loginLog']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('causer_name')
                    ->label('Usuario')
                    ->getStateUsing(fn (Activity $record): string => $record->causer_name)
                    // Busca por el nombre del usuario y por el texto de la acción
                    // ("Exportó Estudiantes", "Actualizó docentes"...).
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $q) => $q
                            ->where('description', 'like', "%{$search}%")
                            ->orWhereHas('loginLog', fn (Builder $log) => $log->where('user_name', 'like', "%{$search}%"))
                    ))
                    ->description(fn (Activity $record): ?string => $record->loginLog?->roles_label),

                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Activity::EVENT_LABELS[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => Activity::EVENT_COLORS[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('module')
                    ->label('Módulo')
                    ->getStateUsing(fn (Activity $record): string => static::moduleLabel($record)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('panel')
                    ->label('Panel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => LoginLog::PANEL_LABELS[$state] ?? '—')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('login_log_id')
                    ->label('Sesión')
                    ->formatStateUsing(fn (?int $state): string => '#'.$state)
                    ->placeholder('—')
                    ->color('primary')
                    ->tooltip('Ver todas las acciones de esta sesión')
                    ->url(fn (Activity $record): ?string => $record->login_log_id
                        ? static::urlForSession($record->login_log_id)
                        : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('causer')
                    ->label('Usuario')
                    ->searchable()
                    ->options(fn (): array => static::causerFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        [$type, $id] = array_pad(explode('|', $data['value'], 2), 2, null);

                        return $query->where('causer_type', $type)->where('causer_id', $id);
                    }),

                // Todo lo que se le hizo a un emprendedor: su ficha y sus
                // visitas, caracterizaciones, planes, etc.
                Tables\Filters\SelectFilter::make('entrepreneur_id')
                    ->label('Emprendedor')
                    ->searchable()
                    ->options(fn (): array => static::entrepreneurFilterOptions()),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Módulo')
                    ->searchable()
                    ->options(fn (): array => static::moduleFilterOptions()),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options(Activity::EVENT_LABELS),

                Tables\Filters\SelectFilter::make('panel')
                    ->label('Panel')
                    ->options(LoginLog::PANEL_LABELS),

                // Lo fija el botón "Ver acciones" del registro de sesiones.
                Tables\Filters\Filter::make('login_log')
                    ->label('Sesión')
                    ->form([
                        Forms\Components\TextInput::make('login_log_id')
                            ->label('N.º de sesión')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['login_log_id'] ?? null)
                        ? $query->where('login_log_id', $data['login_log_id'])
                        : $query)
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['login_log_id'] ?? null)) {
                            return null;
                        }

                        $log = LoginLog::find($data['login_log_id']);

                        return $log
                            ? 'Sesión de '.$log->user_name.' del '.$log->login_at->format('d/m/Y h:i A')
                            : 'Sesión #'.$data['login_log_id'];
                    }),

                Tables\Filters\Filter::make('created_date')
                    ->label('Fecha')
                    ->form([
                        Forms\Components\Radio::make('mode')
                            ->label('Fecha de la acción')
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
                                ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                                ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                        }

                        return $query->when($data['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', $date));
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
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('Ver detalle')
                    ->modalHeading(fn (Activity $record): string => $record->event_label.' · '.static::moduleLabel($record))
                    ->modalContent(fn (Activity $record) => view('filament.resources.activity-log.detail', [
                        'activity' => $record,
                        'module'   => static::moduleLabel($record),
                    ]))
                    ->form([])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    /** Enlace al historial filtrado por una sesión del registro de sesiones. */
    public static function urlForSession(int $loginLogId): string
    {
        return static::getUrl('index', [
            'tableFilters' => ['login_log' => ['login_log_id' => $loginLogId]],
        ]);
    }

    /**
     * Nombre del módulo en lenguaje de usuario, tomado de la etiqueta del
     * recurso Filament que gestiona el modelo en cualquiera de los paneles.
     */
    public static function moduleLabel(Activity $record): string
    {
        if ($record->event === 'exported') {
            return 'Exportación';
        }

        if (! $record->subject_type) {
            return '—';
        }

        return \App\Support\ModelLabels::for($record->subject_type) ?? Str::headline(class_basename($record->subject_type));
    }

    /**
     * @return array<string, string>
     */
    private static function causerFilterOptions(): array
    {
        return Activity::query()
            ->with('causer', 'loginLog')
            ->whereNotNull('causer_id')
            ->selectRaw('causer_type, causer_id, MAX(login_log_id) as login_log_id')
            ->groupBy('causer_type', 'causer_id')
            ->get()
            ->mapWithKeys(fn (Activity $row) => [
                $row->causer_type.'|'.$row->causer_id => $row->causer_name,
            ])
            ->sort()
            ->all();
    }

    /**
     * Solo los emprendedores que aparecen en el historial, no los cientos que
     * hay registrados.
     *
     * @return array<int, string>
     */
    private static function entrepreneurFilterOptions(): array
    {
        $ids = Activity::query()
            ->whereNotNull('entrepreneur_id')
            ->distinct()
            ->pluck('entrepreneur_id');

        return \App\Models\Entrepreneur::withoutGlobalScopes()
            ->whereKey($ids)
            ->get()
            ->mapWithKeys(fn ($entrepreneur) => [$entrepreneur->getKey() => $entrepreneur->getFilamentName()])
            ->sort()
            ->all();
    }

    /**
     * Solo los módulos que aparecen en el historial.
     *
     * @return array<string, string>
     */
    private static function moduleFilterOptions(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $type) => [
                $type => \App\Support\ModelLabels::for($type) ?? Str::headline(class_basename($type)),
            ])
            ->sort()
            ->all();
    }
}
