<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FairEvaluationResource\Pages;
use App\Filament\Resources\FairEvaluationResource\RelationManagers;
use App\Models\FairEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;

//Exportar en excel
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class FairEvaluationResource extends Resource
{
    protected static ?string $model = FairEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Actores';

    protected static ?string $modelLabel = 'Participacion en Feria';
    protected static ?string $pluralModelLabel = 'Participacion en Ferias';

    protected static ?int $navigationSort = 3;

    // Método helper para verificar permisos

    private static function userCanList(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('listFairParticipations');
    }

    private static function userCanCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('createFairParticipation');
    }

    private static function userCanEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('editFairParticipation');
    }

    private static function userCanDelete(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        return $user->can('deleteFairParticipation');
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
                Forms\Components\Tabs::make('Evaluación de Feria')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Datos del Emprendimiento')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\Section::make('Información del Emprendimiento')
                                    ->description('Seleccione el emprendimiento que participó en la feria')
                                    ->icon('heroicon-o-building-storefront')
                                    ->schema([
                                        Forms\Components\Select::make('entrepreneur_id')
                                            ->label('Emprendimiento')
                                            ->relationship(
                                                'entrepreneur',
                                                'full_name',
                                                fn($query) => $query
                                                    ->with('business')
                                                    ->whereHas('business')
                                                    ->when(
                                                        !auth()->user()->hasRole(['Admin', 'Viewer']),
                                                        fn($q) => $q->where('manager_id', auth()->id())
                                                    )
                                            )
                                            ->getOptionLabelFromRecordUsing(fn($record) => $record->business?->business_name ?? $record->full_name)
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull()
                                            ->required()
                                            ->live()
                                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                                            ->placeholder('Buscar emprendimiento por nombre')
                                            ->helperText('Selecciona el emprendimiento para autocompletar información relacionada'),

                                        Forms\Components\Placeholder::make('city_name')
                                            ->label('Municipio')
                                            ->content(function ($get) {
                                                $entrepreneurId = $get('entrepreneur_id');
                                                if (!$entrepreneurId) return '----';

                                                $entrepreneur = \App\Models\Entrepreneur::with('city')->find($entrepreneurId);
                                                return $entrepreneur?->city?->name ?? 'Sin ubicación';
                                            }),

                                        Forms\Components\Placeholder::make('manager_name')
                                            ->label('Gestor')
                                            ->content(function ($get) {
                                                $entrepreneurId = $get('entrepreneur_id');
                                                if (!$entrepreneurId) return '----';

                                                $entrepreneur = \App\Models\Entrepreneur::with('manager')->find($entrepreneurId);
                                                return $entrepreneur?->manager?->name ?? 'Sin gestor';
                                            }),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Feria y Participación')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\Section::make('Datos de Participación')
                                    ->description('Seleccione la feria y fecha de participación')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Forms\Components\Select::make('fair_id')
                                            ->label('Seleccionar Feria')
                                            ->relationship('fair', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->placeholder('Seleccione la feria en la que participó')
                                            ->helperText('Seleccione la feria previamente registrada'),

                                        Forms\Components\DatePicker::make('participation_date')
                                            ->label('Fecha de Participación')
                                            ->required()
                                            ->native(true)
                                            ->displayFormat('d/m/Y')
                                            ->maxDate(now())
                                            ->helperText('Fecha del día en que asistió a la feria')
                                            ->placeholder('Seleccione la fecha'),

                                        Forms\Components\FileUpload::make('participation_photo_path')
                                            ->label('Foto de Participación')
                                            ->image()
                                            ->required()
                                            ->downloadable()
                                            ->directory('fair-evaluations/participation-photos')
                                            ->maxSize(5120) // 5MB
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                                            ->helperText('Imagen de la participación en la feria (máx. 5MB)')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Experiencia en la Feria')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Forms\Components\Section::make('Evaluación de la Experiencia')
                                    ->description('Califique su experiencia en la feria')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Forms\Components\Radio::make('organization_rating')
                                            ->label('¿Cómo califica la organización general de la feria?')
                                            ->options(FairEvaluation::ORGANIZATION_RATING_OPTIONS)
                                            ->required()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('visitor_flow')
                                            ->label('¿Cómo fue el flujo de visitantes en su stand?')
                                            ->options(FairEvaluation::VISITOR_FLOW_OPTIONS)
                                            ->required()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('generated_contacts')
                                            ->label('¿Pudo generar contactos o alianzas durante la feria?')
                                            ->boolean()
                                            ->required()
                                            ->live()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('strategic_contacts_details')
                                            ->label('¿Cuáles fueron los contactos estratégicos que logró establecer?')
                                            ->placeholder('Escriba el nombre de las personas, empresas y los beneficios que puede obtener')
                                            ->rows(4)
                                            ->visible(fn(Get $get) => $get('generated_contacts') == 1)
                                            ->required(fn(Get $get) => $get('generated_contacts') == 1)
                                            ->columnSpanFull()
                                            ->helperText('Detalle los contactos establecidos y sus beneficios'),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Impacto en el Emprendimiento')
                            ->icon('heroicon-o-arrow-trending-up')
                            ->schema([
                                Forms\Components\Section::make('Resultados e Impacto')
                                    ->description('Evalúe el impacto de la feria en su emprendimiento')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Radio::make('product_visibility')
                                            ->label('¿La feria le permitió dar a conocer mejor su producto/servicio?')
                                            ->options(FairEvaluation::PRODUCT_VISIBILITY_OPTIONS)
                                            ->required()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('total_sales')
                                            ->label('¿Cuánto fue el valor total en pesos de sus ventas durante la feria?')
                                            ->options(FairEvaluation::TOTAL_SALES_OPTIONS)
                                            ->required()
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('order_value')
                                            ->label('¿Qué valor en pesos suman los pedidos que le hicieron?')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->prefix('$')
                                            ->placeholder('0')
                                            ->helperText('Solo números, sin puntos ni comas')
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('sufficient_products')
                                            ->label('¿Fueron suficientes los productos que llevó a la feria?')
                                            ->boolean()
                                            ->required()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Radio::make('established_productive_chain')
                                            ->label('¿Durante la feria logró establecer algún encadenamiento productivo?')
                                            ->helperText('Acuerdos de cooperación, compra/venta entre emprendedores, alianzas estratégicas')
                                            ->boolean()
                                            ->required()
                                            ->live()
                                            ->inline()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('productive_chain_details')
                                            ->label('Indique brevemente con quién y de qué tipo fue el encadenamiento')
                                            ->placeholder('Describa el encadenamiento productivo establecido')
                                            ->rows(3)
                                            ->visible(fn(Get $get) => $get('established_productive_chain') == 1)
                                            ->required(fn(Get $get) => $get('established_productive_chain') == 1)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('observations')
                                            ->label('Observaciones')
                                            ->placeholder('Agregue cualquier comentario o detalle adicional')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('entrepreneur.full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('entrepreneur.business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('fair.name')
                    ->label('Feria')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('participation_date')
                    ->label('Fecha Participación')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar Excel')
                    ->visible(fn() => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->exports([
                        ExcelExport::make()
                            ->withFilename(fn() => 'participación-ferias-' . now()->format('Y-m-d-His'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn($query) => $query->with([
                                'entrepreneur.business',
                                'entrepreneur.city',
                                'entrepreneur.manager',
                                'fair',
                                'manager',
                            ]))
                            ->withColumns([
                                // === DATOS DEL EMPRENDIMIENTO ===
                                Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                Column::make('entrepreneur.city.name')->heading('Municipio'),
                                Column::make('entrepreneur.manager.name')->heading('Gestor'),

                                // === FERIA Y PARTICIPACIÓN ===
                                Column::make('fair.name')->heading('Feria'),
                                Column::make('participation_date')->heading('Fecha de Participación')
                                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                // === EXPERIENCIA EN LA FERIA ===
                                Column::make('organization_rating')->heading('Calificación de Organización')
                                    ->formatStateUsing(fn($state) => FairEvaluation::ORGANIZATION_RATING_OPTIONS[$state] ?? $state),

                                Column::make('visitor_flow')->heading('Flujo de Visitantes')
                                    ->formatStateUsing(fn($state) => FairEvaluation::VISITOR_FLOW_OPTIONS[$state] ?? $state),

                                Column::make('generated_contacts')->heading('Generó Contactos')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                Column::make('strategic_contacts_details')->heading('Detalles de Contactos Estratégicos'),

                                // === IMPACTO EN EL EMPRENDIMIENTO ===
                                Column::make('product_visibility')->heading('Visibilidad del Producto')
                                    ->formatStateUsing(fn($state) => FairEvaluation::PRODUCT_VISIBILITY_OPTIONS[$state] ?? $state),

                                Column::make('total_sales')->heading('Ventas Totales')
                                    ->formatStateUsing(fn($state) => FairEvaluation::TOTAL_SALES_OPTIONS[$state] ?? $state),

                                Column::make('order_value')->heading('Valor de Pedidos')
                                    ->formatStateUsing(fn($state) => '$' . number_format($state, 0, ',', '.')),

                                Column::make('sufficient_products')->heading('Productos Suficientes')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                Column::make('established_productive_chain')->heading('Estableció Encadenamiento')
                                    ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                Column::make('productive_chain_details')->heading('Detalles del Encadenamiento'),

                                Column::make('observations')->heading('Observaciones'),

                                // === INFORMACIÓN ADICIONAL ===
                                Column::make('manager.name')->heading('Registrado por'),

                                Column::make('created_at')->heading('Fecha de Registro')
                                    ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),

                                Column::make('updated_at')->heading('Última Actualización')
                                    ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),
                            ]),
                    ])
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
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
                    ->tooltip('Editar participación')
                    ->visible(
                        fn($record) =>
                        !$record->trashed() &&
                            static::userCanEdit() &&
                            (auth()->user()->hasRole(['Admin']) || $record->manager_id === auth()->id())
                    ),

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
                    ->tooltip('Restaurar participación')
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
                    ExportBulkAction::make()
                        ->label('Exportar Excel')
                        ->exports([
                            ExcelExport::make()
                                ->withFilename(fn() => 'participación-ferias-' . now()->format('Y-m-d-His'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->modifyQueryUsing(fn($query) => $query->with([
                                    'entrepreneur.business',
                                    'entrepreneur.city',
                                    'entrepreneur.manager',
                                    'fair',
                                    'manager',
                                ]))
                                ->withColumns([
                                    // === DATOS DEL EMPRENDIMIENTO ===
                                    Column::make('entrepreneur.full_name')->heading('Emprendedor'),
                                    Column::make('entrepreneur.business.business_name')->heading('Emprendimiento'),
                                    Column::make('entrepreneur.city.name')->heading('Municipio'),
                                    Column::make('entrepreneur.manager.name')->heading('Gestor'),

                                    // === FERIA Y PARTICIPACIÓN ===
                                    Column::make('fair.name')->heading('Feria'),
                                    Column::make('participation_date')->heading('Fecha de Participación')
                                        ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : ''),

                                    // === EXPERIENCIA EN LA FERIA ===
                                    Column::make('organization_rating')->heading('Calificación de Organización')
                                        ->formatStateUsing(fn($state) => FairEvaluation::ORGANIZATION_RATING_OPTIONS[$state] ?? $state),

                                    Column::make('visitor_flow')->heading('Flujo de Visitantes')
                                        ->formatStateUsing(fn($state) => FairEvaluation::VISITOR_FLOW_OPTIONS[$state] ?? $state),

                                    Column::make('generated_contacts')->heading('Generó Contactos')
                                        ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                    Column::make('strategic_contacts_details')->heading('Detalles de Contactos Estratégicos'),

                                    // === IMPACTO EN EL EMPRENDIMIENTO ===
                                    Column::make('product_visibility')->heading('Visibilidad del Producto')
                                        ->formatStateUsing(fn($state) => FairEvaluation::PRODUCT_VISIBILITY_OPTIONS[$state] ?? $state),

                                    Column::make('total_sales')->heading('Ventas Totales')
                                        ->formatStateUsing(fn($state) => FairEvaluation::TOTAL_SALES_OPTIONS[$state] ?? $state),

                                    Column::make('order_value')->heading('Valor de Pedidos')
                                        ->formatStateUsing(fn($state) => '$' . number_format($state, 0, ',', '.')),

                                    Column::make('sufficient_products')->heading('Productos Suficientes')
                                        ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                    Column::make('established_productive_chain')->heading('Estableció Encadenamiento')
                                        ->formatStateUsing(fn($state) => $state ? 'Sí' : 'No'),

                                    Column::make('productive_chain_details')->heading('Detalles del Encadenamiento'),

                                    Column::make('observations')->heading('Observaciones'),

                                    // === INFORMACIÓN ADICIONAL ===
                                    Column::make('manager.name')->heading('Registrado por'),

                                    Column::make('created_at')->heading('Fecha de Registro')
                                        ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),

                                    Column::make('updated_at')->heading('Última Actualización')
                                        ->formatStateUsing(fn($state) => $state ? $state->format('d/m/Y H:i') : ''),
                                ]),
                        ]),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFairEvaluations::route('/'),
            'create' => Pages\CreateFairEvaluation::route('/create'),
            'edit' => Pages\EditFairEvaluation::route('/{record}/edit'),
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
