<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrepreneurPathResource\Pages;
use App\Models\Entrepreneur;
use App\Support\EntrepreneurPath;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntrepreneurPathResource extends Resource
{
    protected static ?string $model = Entrepreneur::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Emprendedores';

    protected static ?string $modelLabel = 'Ruta del Emprendedor';

    protected static ?string $pluralModelLabel = 'Ruta del Emprendedor';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('status', true)
            // Solo lo que muestra la tabla; la ficha carga el resto al abrirse (EntrepreneurPath).
            ->with(['business', 'city', 'manager']);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('listEntrepreneurPaths');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('listEntrepreneurPaths');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('full_name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('Sin emprendimiento'),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin gestor asignado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->relationship('city', 'name', function ($query) {
                        return $query->where('status', true)
                            ->whereHas('department', function ($q) {
                                $q->where('status', true);
                            })
                            ->orderBy('name', 'asc');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('manager_id')
                    ->label('Gestor')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver la ruta del emprendedor')
                    ->modalHeading('Ruta del emprendedor')
                    ->modalWidth('6xl')
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist(static::pathInfolist()),
            ])
            ->bulkActions([]);
    }

    /**
     * Ficha del emprendedor: cabecera con su recorrido y pestañas con el
     * detalle. Los datos se arman una sola vez en EntrepreneurPath.
     *
     * @return array<int, Infolists\Components\Component>
     */
    private static function pathInfolist(): array
    {
        $path  = fn (Entrepreneur $record): array => EntrepreneurPath::for($record);
        $count = fn (string $key) => fn (Entrepreneur $record) => count(EntrepreneurPath::for($record)[$key]) ?: null;
        $entry = fn (string $name, string $view, \Closure $state) => Infolists\Components\ViewEntry::make($name)
            ->hiddenLabel()
            ->view("filament.entrepreneur-path.{$view}")
            ->getStateUsing($state)
            ->columnSpanFull();

        return [
            $entry('path_header', 'header', fn (Entrepreneur $record) => $path($record)),

            Infolists\Components\Tabs::make('Detalle')
                ->columnSpanFull()
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('Emprendimiento')
                        ->icon('heroicon-o-building-storefront')
                        ->schema([
                            $entry('path_business', 'details', fn (Entrepreneur $record) => [
                                'details' => $path($record)['details'],
                                'text'    => ['label' => 'Descripción del emprendimiento', 'value' => $path($record)['description']],
                            ]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Caracterización')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            $entry('path_characterization', 'details', fn (Entrepreneur $record) => ($c = $path($record)['characterization'])
                                ? ['intro' => "Caracterizado el {$c['date']}" . ($c['manager'] ? " por {$c['manager']}" : '') . '.', 'details' => $c['details'], 'lists' => $c['lists']]
                                : null),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Diagnósticos')
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            $entry('path_diagnoses', 'diagnoses', fn (Entrepreneur $record) => ['entry' => $path($record)['entry'], 'exit' => $path($record)['exit']]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Visitas')
                        ->icon('heroicon-o-map-pin')
                        ->badge($count('visits'))
                        ->schema([
                            $entry('path_visits', 'table', fn (Entrepreneur $record) => [
                                'columns' => [['label' => 'Fecha'], ['label' => 'Hora'], ['label' => 'Tipo de visita'], ['label' => 'Gestor'], ['label' => 'Confirmación'], ['label' => '¿Se fortaleció?']],
                                'rows'    => array_map(fn (array $v) => [
                                    $v['date'], $v['time'], $v['type'] . ($v['rescheduled'] ? ' (reprogramada)' : ''), $v['manager'],
                                    ['badge' => $v['confirmed'] ? 'Confirmada' : 'Pendiente', 'color' => $v['confirmed'] ? 'success' : 'warning'],
                                    ['badge' => $v['strengthened'] ? 'Sí' : 'No', 'color' => $v['strengthened'] ? 'success' : 'gray'],
                                ], $path($record)['visits']),
                                'empty'   => 'El emprendedor aún no tiene visitas registradas.',
                            ]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Capacitaciones')
                        ->icon('heroicon-o-academic-cap')
                        ->badge($count('trainings'))
                        ->schema([
                            $entry('path_trainings', 'table', fn (Entrepreneur $record) => [
                                'columns' => [['label' => 'Capacitación'], ['label' => 'Fecha'], ['label' => 'Modalidad'], ['label' => 'Lugar'], ['label' => 'Horas', 'numeric' => true], ['label' => 'Instructor']],
                                'rows'    => array_map(fn (array $t) => [
                                    $t['name'], $t['date'],
                                    $t['modality'] ? ['badge' => $t['modality'], 'color' => match ($t['modality']) { 'Presencial' => 'success', 'Virtual' => 'info', default => 'warning' }] : null,
                                    $t['city'], $t['hours'], $t['organizer'],
                                ], $path($record)['trainings']),
                                'empty'   => 'El emprendedor aún no ha participado en capacitaciones.',
                            ]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Ferias')
                        ->icon('heroicon-o-building-storefront')
                        ->badge($count('fairs'))
                        ->visible(fn (Entrepreneur $record) => $path($record)['fairs'] !== [])
                        ->schema([
                            $entry('path_fairs', 'table', fn (Entrepreneur $record) => [
                                'columns' => [['label' => 'Feria'], ['label' => 'Fecha'], ['label' => 'Ventas', 'numeric' => true], ['label' => 'Contactos generados', 'numeric' => true]],
                                'rows'    => array_map(fn (array $f) => [$f['name'], $f['date'], $f['sales'], $f['contacts']], $path($record)['fairs']),
                                'empty'   => 'Sin ferias.',
                            ]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('PQRF')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->badge($count('pqrfs'))
                        ->visible(fn (Entrepreneur $record) => $path($record)['pqrfs'] !== [])
                        ->schema([
                            $entry('path_pqrfs', 'table', fn (Entrepreneur $record) => [
                                'columns' => [['label' => 'Tipo'], ['label' => 'Fecha'], ['label' => 'Estado'], ['label' => 'Descripción']],
                                'rows'    => array_map(fn (array $q) => [$q['type'], $q['date'], $q['status'] ? ['badge' => $q['status']] : null, \Illuminate\Support\Str::limit((string) $q['description'], 140)], $path($record)['pqrfs']),
                                'empty'   => 'Sin PQRF.',
                            ]),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Documentos')
                        ->icon('heroicon-o-folder-open')
                        ->badge(fn (Entrepreneur $record) => collect($path($record)['documents'])->flatten(1)->count() ?: null)
                        ->schema([
                            $entry('path_documents', 'documents', fn (Entrepreneur $record) => $path($record)['documents']),
                        ]),
                ]),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntrepreneurPaths::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Entrepreneur::where('status', true)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
