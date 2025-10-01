<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CharacterizationResource\Pages;
use App\Filament\Resources\CharacterizationResource\RelationManagers;
use App\Models\Characterization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CharacterizationResource extends Resource
{
    protected static ?string $model = Characterization::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Información general';

    protected static ?string $modelLabel = 'Caracterización';
    protected static ?string $pluralModelLabel = 'Caracterizaciones';

    protected static ?int $navigationSort = 3;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listCharacterizations');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createCharacterization');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editCharacterization');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteCharacterization');
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
                Forms\Components\Section::make('Información del Emprendedor')
                    ->description('Selecciona el emprendedor y visualiza sus datos principales')
                    ->icon('heroicon-o-user')
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
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->placeholder('Buscar emprendedor por nombre')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->helperText(
                                fn(string $operation): string =>
                                $operation === 'edit'
                                    ? 'El emprendedor asignado no puede ser modificado.'
                                    : 'Selecciona el emprendedor para autocompletar información relacionada'
                            )
                            ->unique(table: 'characterizations', column: 'entrepreneur_id', ignoreRecord: true),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('business_name')
                                    ->label('Emprendimiento')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('business')->find($entrepreneurId);
                                        return $entrepreneur?->business?->business_name ?? 'Sin emprendimiento';
                                    }),

                                Forms\Components\Placeholder::make('city_name')
                                    ->label('Municipio')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);
                                        return $entrepreneur?->city?->name ?? 'Sin ubicación';
                                    }),

                                Forms\Components\Placeholder::make('manager_name')
                                    ->label('Gestor Asignado')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('manager')->find($entrepreneurId);
                                        return $entrepreneur?->manager?->name ?? 'Sin gestor asignado';
                                    }),
                            ]),

                        Forms\Components\DatePicker::make('characterization_date')
                            ->label('Fecha de Caracterización')
                            ->required()
                            ->maxDate(now())
                            ->displayFormat('d/m/Y')
                            ->native(true),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Información Económica')
                    ->description('Datos sobre la actividad económica y población del emprendedor')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('economic_activity')
                                    ->label('Actividad Económica')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('business.economicActivity')
                                            ->find($entrepreneurId);

                                        return $entrepreneur?->business?->economicActivity?->name ?? 'Sin actividad económica';
                                    }),

                                Forms\Components\Placeholder::make('vulnerable_population')
                                    ->label('Población Vulnerable')
                                    ->content(function ($get) {
                                        $entrepreneurId = $get('entrepreneur_id');
                                        if (!$entrepreneurId) return '----';

                                        $entrepreneur = \App\Models\Entrepreneur::with('population')
                                            ->find($entrepreneurId);

                                        return $entrepreneur?->population?->name ?? 'Sin población asignada';
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Características del Negocio')
                    ->description('Información específica sobre el emprendimiento')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('business_type')
                                    ->label('Tipo de Negocio')
                                    ->options([
                                        'individual'  => 'Individual',
                                        'associative' => 'Asociativo',
                                    ])
                                    ->placeholder('Seleccione el tipo de negocio')
                                    ->helperText('Modalidad de organización del emprendimiento'),

                                Forms\Components\Select::make('business_age')
                                    ->label('Antigüedad del Negocio')
                                    ->options([
                                        'over_6_months'  => 'Más de 6 meses',
                                        'over_12_months' => 'Más de 12 meses',
                                        'over_24_months' => 'Más de 24 meses',
                                    ])
                                    ->placeholder('Seleccione la antigüedad')
                                    ->helperText('Tiempo que lleva funcionando el negocio'),

                                Forms\Components\CheckboxList::make('clients')
                                    ->label('Clientela Actual y Potencial')
                                    ->options([
                                        'community'        => 'Comunidad en general',
                                        'public_entities'  => 'Entidades públicas',
                                        'private_entities' => 'Entidades privadas',
                                        'schools'          => 'Colegios',
                                        'hospitals'        => 'Hospitales',
                                    ])
                                    ->columns(2)
                                    ->helperText('Seleccione todos los tipos de clientes que apliquen')
                                    ->columnSpanFull(),

                                Forms\Components\CheckboxList::make('promotion_strategies')
                                    ->label('Estrategias de Promoción')
                                    ->options([
                                        'word_of_mouth' => 'Voz a voz',
                                        'whatsapp'      => 'WhatsApp',
                                        'facebook'      => 'Facebook',
                                        'instagram'     => 'Instagram',
                                    ])
                                    ->columns(2)
                                    ->helperText('Marque todas las estrategias que utiliza')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('average_monthly_sales')
                                    ->label('Ventas Mensuales Promedio')
                                    ->options([
                                        'lt_500000' => 'Menos de $500.000',
                                        '500k_1m'   => '$500.001 — $1.000.000',
                                        '1m_2m'     => '$1.001.000 — $2.000.000',
                                        '2m_5m'     => '$2.001.000 — $5.000.000',
                                        'gt_5m'     => 'Más de $5.001.000',
                                    ])
                                    ->placeholder('Seleccione rango de ventas')
                                    ->helperText('Ingreso promedio mensual del negocio'),

                                Forms\Components\Select::make('employees_generated')
                                    ->label('Empleos Generados')
                                    ->options([
                                        'up_to_2'     => 'Hasta 2 empleados',
                                        '3_to_4'      => '3 a 4 empleados',
                                        'more_than_5' => 'Más de 5 empleados',
                                    ])
                                    ->placeholder('Seleccione cantidad de empleos')
                                    ->helperText('Número de personas empleadas por el emprendimiento'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Formalización y Registros')
                    ->description('Estado de formalización del emprendimiento')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('has_accounting_records')
                                    ->label('Registros Contables')
                                    ->helperText('¿Lleva registros de ingresos y gastos?')
                                    ->required()
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Toggle::make('has_commercial_registration')
                                    ->label('Registro Mercantil')
                                    ->helperText('¿Tiene registro mercantil vigente?')
                                    ->required()
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Toggle::make('family_in_drummond')
                                    ->label('Familiar en Drummond')
                                    ->helperText('¿Tiene familiares trabajando en Drummond?')
                                    ->required()
                                    ->inline(false)
                                    ->onIcon('heroicon-m-check-circle')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->onColor('success')
                                    ->offColor('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Georreferenciación')
                    ->description('Ubicación exacta del emprendimiento')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitud')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Ej: 7.1193')
                                    ->helperText('Coordenada de latitud GPS')
                                    ->rules(['numeric', 'between:-90,90']),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitud')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Ej: -73.1227')
                                    ->helperText('Coordenada de longitud GPS')
                                    ->rules(['numeric', 'between:-180,180']),
                            ]),

                        Forms\Components\Placeholder::make('coordinates_info')
                            ->label('Información')
                            ->content('Las coordenadas GPS deben ser obtenidas en el lugar exacto del emprendimiento. Use una aplicación GPS o Google Maps para obtener las coordenadas precisas.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Forms\Components\Section::make('Evidencias Fotográficas')
                    ->description('Documentos y fotografías de soporte')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\FileUpload::make('commerce_evidence_path')
                                    ->label('Evidencia del Comercio')
                                    ->directory('characterizations/commerce')
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->helperText('Fotografías del establecimiento o documento (máximo 5MB)')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->validationMessages([
                                        'max' => 'El archivo no puede superar los 5MB.',
                                    ]),

                                    Forms\Components\FileUpload::make('population_evidence_path')
                                    ->label('Evidencia de Población Vulnerable')
                                    ->directory('characterizations/population')
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                    ->helperText('Documentos que certifican la condición de población vulnerable (máximo 5MB)')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->validationMessages([
                                        'max' => 'El archivo no puede superar los 5MB.',
                                    ]),

                                Forms\Components\FileUpload::make('photo_evidence_path')
                                    ->label('Fotografía Georeferenciación')
                                    ->directory('characterizations/georeference')
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                    ->helperText('Foto de la ubicación exacta del emprendimiento (máximo 5MB)')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->validationMessages([
                                        'max' => 'El archivo no puede superar los 5MB.',
                                    ]),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
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
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entrepreneur.city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin ubicación'),

                Tables\Columns\TextColumn::make('characterization_date')
                    ->label('Fecha Caracterización')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin fecha'),

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
                    ->tooltip('Editar caracterización')
                    ->visible(fn($record) => !$record->trashed() && static::userCanEdit()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('primary')
                    ->tooltip('Deshabilitar')
                    ->visible(fn($record) => !$record->trashed() && static::userCanDelete()),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar caracterización')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCharacterizations::route('/'),
            'create' => Pages\CreateCharacterization::route('/create'),
            'edit' => Pages\EditCharacterization::route('/{record}/edit'),
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
