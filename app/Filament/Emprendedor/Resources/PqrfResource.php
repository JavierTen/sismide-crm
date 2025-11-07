<?php

namespace App\Filament\Emprendedor\Resources;

use App\Filament\Emprendedor\Resources\PqrfResource\Pages;
use App\Models\Pqrf;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class PqrfResource extends Resource
{
    protected static ?string $model = Pqrf::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Mis PQRFS';

    protected static ?string $modelLabel = 'PQRF';

    protected static ?string $pluralModelLabel = 'PQRFS';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la PQRF')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Pqrf::typeOptions())
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('incident_date')
                            ->label('Fecha del Hecho')
                            ->required()
                            ->maxDate(now())
                            ->native(false),

                        Forms\Components\Select::make('city_id')
                            ->label('Municipio')
                            ->options(City::where('status', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('evidence_files')
                            ->label('Archivos de Evidencia')
                            ->multiple()
                            ->directory('pqrfs/evidence')
                            ->disk('public')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->helperText('Puedes subir archivos PDF, imágenes o documentos de Word (máx. 5MB cada uno)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Solo mostrar respuesta si existe (para edición)
                Forms\Components\Section::make('Respuesta del Gestor')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('Estado')
                            ->content(fn($record) => $record ? $record->getStatusLabel() : 'Pendiente'),

                        Forms\Components\Placeholder::make('response_date')
                            ->label('Fecha de Respuesta')
                            ->content(fn($record) => $record?->response_date?->format('d/m/Y') ?? 'Sin respuesta'),

                        Forms\Components\Placeholder::make('response')
                            ->label('Respuesta')
                            ->content(fn($record) => $record?->response ?? 'Aún no hay respuesta')
                            ->columnSpanFull(),


                    ])
                    ->columns(2)
                    ->hidden(fn($record) => !$record || !$record->hasResponse()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => Pqrf::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'petition' => 'info',
                        'complaint' => 'warning',
                        'claim' => 'danger',
                        'congratulation' => 'success',
                        'suggestion' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('incident_date')
                    ->label('Fecha del Hecho')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => Pqrf::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn($record) => $record->getStatusColor())
                    ->sortable(),

                Tables\Columns\TextColumn::make('response_date')
                    ->label('Fecha de Respuesta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Sin responder'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Pqrf::typeOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Pqrf::statusOptions())
                    ->native(false),

                Tables\Filters\Filter::make('with_response')
                    ->label('Con respuesta')
                    ->query(fn(Builder $query) => $query->whereNotNull('response')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
            ])

            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay PQRFS registradas')
            ->emptyStateDescription('Crea tu primera PQRF usando el botón de arriba')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->formatStateUsing(fn($state) => Pqrf::typeOptions()[$state] ?? $state)
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'petition' => 'info',
                                'complaint' => 'warning',
                                'claim' => 'danger',
                                'congratulation' => 'success',
                                'suggestion' => 'primary',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->formatStateUsing(fn($state) => Pqrf::statusOptions()[$state] ?? $state)
                            ->badge()
                            ->color(fn($record) => $record->getStatusColor()),

                        Infolists\Components\TextEntry::make('incident_date')
                            ->label('Fecha del Hecho')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('city.name')
                            ->label('Municipio')
                            ->placeholder('No especificado'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Descripción')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('')
                            ->prose()
                            ->columnSpanFull(),
                    ]),



                Infolists\Components\Section::make('Respuesta del Gestor')
                    ->schema([
                        Infolists\Components\TextEntry::make('respondedBy.name')
                            ->label('Respondido por')
                            ->placeholder('Sin responder'),

                        Infolists\Components\TextEntry::make('response_date')
                            ->label('Fecha de Respuesta')
                            ->date('d/m/Y')
                            ->placeholder('Sin responder'),

                        Infolists\Components\TextEntry::make('response')
                            ->label('Respuesta')
                            ->prose()
                            ->placeholder('Aún no hay respuesta')
                            ->columnSpanFull(),


                    ])
                    ->columns(2)
                    ->hidden(fn($record) => !$record->hasResponse()),
            ]);
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
            'index' => Pages\ListPqrves::route('/'),
            'create' => Pages\CreatePqrf::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('entrepreneur_id', auth()->id());
    }
}
