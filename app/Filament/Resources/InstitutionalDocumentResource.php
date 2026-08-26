<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionalDocumentResource\Pages;
use App\Models\Actor;
use App\Models\InstitutionalDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionalDocumentResource extends Resource
{
    protected static ?string $model = InstitutionalDocument::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Parametros';
    protected static ?string $navigationLabel = 'Gestión Documental';
    protected static ?string $modelLabel      = 'Documento';
    protected static ?string $pluralModelLabel = 'Documentos';
    protected static ?int    $navigationSort  = 3;

    public static function canAccess(): bool
    {
        return auth()->user()->can('listInstitutionalDocuments');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('createInstitutionalDocument');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('editInstitutionalDocument');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('deleteInstitutionalDocument');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('viewInstitutionalDocument');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with([
                'entity' => fn($q) => $q->withoutGlobalScopes()->with('city'),
                'manager',
            ]);

        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query;
    }

    // ── FORMULARIO ─────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── 5.1 Entidad y documento ──────────────────────────────────────
            Forms\Components\Section::make('Entidad y documento')
                ->icon('heroicon-o-building-office-2')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('entity_id')
                        ->label('Entidad')
                        ->searchable()
                        ->required()
                        ->columnSpan(3)
                        ->options(fn() => Actor::orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->disabled(fn(string $operation) => $operation === 'edit')
                        ->dehydrated(true)
                        ->live(),

                    Forms\Components\Placeholder::make('entity_type_display')
                        ->label('Tipo de entidad')
                        ->content(fn(Get $get) => static::getEntityField($get('entity_id'), 'type_label'))
                        ->visible(fn(Get $get) => (bool) $get('entity_id')),

                    Forms\Components\Placeholder::make('entity_city_display')
                        ->label('Municipio')
                        ->content(fn(Get $get) => static::getEntityField($get('entity_id'), 'city'))
                        ->visible(fn(Get $get) => (bool) $get('entity_id')),

                    Forms\Components\Placeholder::make('entity_project_display')
                        ->label('Proyecto')
                        ->content(fn(Get $get) => static::getEntityField($get('entity_id'), 'project'))
                        ->visible(fn(Get $get) => (bool) $get('entity_id')),

                    Forms\Components\Grid::make(2)
                        ->columnSpan(3)
                        ->schema([
                            Forms\Components\Select::make('document_type')
                                ->label('Tipo de documento')
                                ->options(InstitutionalDocument::DOCUMENT_TYPE_OPTIONS)
                                ->required()
                                ->searchable(),

                            Forms\Components\DatePicker::make('document_date')
                                ->label('Fecha del documento')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                        ]),

                    Forms\Components\TextInput::make('subject')
                        ->label('Nombre o asunto')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),
                ]),

            // ── 5.2 Estado y seguimiento ─────────────────────────────────────
            Forms\Components\Section::make('Estado y seguimiento')
                ->icon('heroicon-o-clock')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(InstitutionalDocument::STATUS_OPTIONS)
                        ->required()
                        ->default('pending'),

                    Forms\Components\DatePicker::make('delivery_date')
                        ->label('Fecha de entrega')
                        ->native(false)
                        ->displayFormat('d/m/Y'),

                    Forms\Components\Toggle::make('requires_follow_up')
                        ->label('¿Requiere seguimiento?')
                        ->live()
                        ->default(false)
                        ->inline(false),

                    Forms\Components\DatePicker::make('follow_up_date')
                        ->label('Fecha de seguimiento')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->visible(fn(Get $get) => (bool) $get('requires_follow_up'))
                        ->required(fn(Get $get) => (bool) $get('requires_follow_up')),

                    Forms\Components\Textarea::make('observation')
                        ->label('Observación')
                        ->visible(fn(Get $get) => (bool) $get('requires_follow_up'))
                        ->rows(2)
                        ->columnSpan(2),
                ]),

            // ── 5.3 Soporte físico ───────────────────────────────────────────
            Forms\Components\Section::make('Soporte físico')
                ->icon('heroicon-o-archive-box')
                ->columns(2)
                ->schema([
                    Forms\Components\Radio::make('has_physical_support')
                        ->label('¿Existe soporte físico?')
                        ->options(InstitutionalDocument::PHYSICAL_SUPPORT_OPTIONS)
                        ->required()
                        ->default('no')
                        ->live()
                        ->inline(),

                    Forms\Components\TextInput::make('physical_location')
                        ->label('Ubicación física')
                        ->placeholder('Ej: Carpeta Alcaldías 2026 – Fundación')
                        ->visible(fn(Get $get) => $get('has_physical_support') === 'yes')
                        ->required(fn(Get $get) => $get('has_physical_support') === 'yes'),
                ]),

            // ── 5.4 Archivos digitales ───────────────────────────────────────
            Forms\Components\Section::make('Archivos digitales')
                ->icon('heroicon-o-paper-clip')
                ->columns(1)
                ->schema([
                    Forms\Components\FileUpload::make('main_file_path')
                        ->label('Documento principal')
                        ->disk('public')
                        ->directory('institutional-documents/main')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->maxSize(20480)
                        ->downloadable()
                        ->openable(),

                    Forms\Components\FileUpload::make('attachments')
                        ->label('Evidencias / Anexos')
                        ->disk('public')
                        ->directory('institutional-documents/attachments')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(20480)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                        ])
                        ->downloadable()
                        ->openable(),
                ]),
        ]);
    }

    // ── TABLA (BANDEJA) ─────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('document_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('entity.name')
                    ->label('Entidad')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->entity?->city?->name ?? '—'),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => InstitutionalDocument::DOCUMENT_TYPE_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Nombre / Asunto')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('document_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => InstitutionalDocument::STATUS_OPTIONS[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending'           => 'warning',
                        'in_management'     => 'info',
                        'delivered'         => 'primary',
                        'pending_signature' => 'danger',
                        'signed', 'finalized' => 'success',
                        default             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('main_file_path')
                    ->label('Archivo')
                    ->getStateUsing(fn($record) => !empty($record->main_file_path) ? 'Sí' : 'No')
                    ->badge()
                    ->color(fn($state) => $state === 'Sí' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('requires_follow_up')
                    ->label('Seguimiento')
                    ->getStateUsing(fn($record) => match (true) {
                        $record->isFollowUpOverdue()                                          => 'Vencido',
                        $record->requires_follow_up && in_array($record->status, ['signed', 'finalized']) => 'Completado',
                        (bool) $record->requires_follow_up                                   => 'Activo',
                        default                                                               => 'No',
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Vencido'    => 'danger',
                        'Activo'     => 'warning',
                        'Completado' => 'success',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_city')
                    ->label('Municipio')
                    ->query(fn(Builder $query, array $data) =>
                        $data['value']
                            ? $query->whereHas('entity', fn($q) => $q->where('city_id', $data['value']))
                            : $query
                    )
                    ->options(fn() => \App\Models\City::orderBy('name')->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('entity_id')
                    ->label('Entidad')
                    ->relationship('entity', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de documento')
                    ->options(InstitutionalDocument::DOCUMENT_TYPE_OPTIONS),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(InstitutionalDocument::STATUS_OPTIONS),

                Tables\Filters\Filter::make('document_date')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['from'],  fn($q) => $q->whereDate('document_date', '>=', $data['from']))
                        ->when($data['until'], fn($q) => $q->whereDate('document_date', '<=', $data['until']))
                    ),

                Tables\Filters\Filter::make('follow_up_overdue')
                    ->label('Seguimiento vencido')
                    ->query(fn(Builder $query) => $query
                        ->where('requires_follow_up', true)
                        ->where('follow_up_date', '<', now())
                        ->whereNotIn('status', ['signed', 'finalized'])
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver documento'),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar')
                    ->visible(fn($record) => !$record->trashed()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Deshabilitar')
                    ->visible(fn($record) => !$record->trashed()),

                Tables\Actions\RestoreAction::make()
                    ->label('')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->tooltip('Restaurar')
                    ->visible(fn($record) => $record->trashed()),
            ])
            ->headerActions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    // ── PÁGINAS ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInstitutionalDocuments::route('/'),
            'create' => Pages\CreateInstitutionalDocument::route('/create'),
            'edit'   => Pages\EditInstitutionalDocument::route('/{record}/edit'),
        ];
    }

    // ── HELPERS ─────────────────────────────────────────────────────────────────

    private static function getEntityField(?int $entityId, string $field): string
    {
        if (!$entityId) return '—';
        $entity = Actor::withoutGlobalScopes()->with('city')->find($entityId);
        if (!$entity) return '—';

        return match ($field) {
            'type_label' => Actor::TYPE_OPTIONS[$entity->type] ?? $entity->type ?? '—',
            'city'       => $entity->city?->name ?? '—',
            'project'    => Actor::PROJECT_OPTIONS[$entity->project] ?? '—',
            default      => '—',
        };
    }
}