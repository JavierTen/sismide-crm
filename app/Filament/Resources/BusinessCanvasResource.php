<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessCanvasResource\Pages;
use App\Models\BusinessCanvas;
use App\Models\Entrepreneur;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
    public static function canRestore($r): bool { return auth()->user()?->can('deleteBusinessCanvas') ?? false; }
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
                Tables\Actions\ViewAction::make()->label('')->tooltip('Ver'),
                Tables\Actions\EditAction::make()->label('')->tooltip('Editar')
                    ->visible(fn ($record) => ! $record->trashed()),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Deshabilitar')
                    ->visible(fn ($record) => ! $record->trashed()),
                Tables\Actions\RestoreAction::make()->label('')->tooltip('Restaurar')
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
