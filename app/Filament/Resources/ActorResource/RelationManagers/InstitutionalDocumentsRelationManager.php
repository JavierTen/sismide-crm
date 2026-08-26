<?php

namespace App\Filament\Resources\ActorResource\RelationManagers;

use App\Models\InstitutionalDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class InstitutionalDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title       = 'Expediente Documental';
    protected static ?string $icon        = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('document_type')
                ->label('Tipo de documento')
                ->options(InstitutionalDocument::DOCUMENT_TYPE_OPTIONS)
                ->required()
                ->searchable(),

            Forms\Components\TextInput::make('subject')
                ->label('Nombre o asunto')
                ->required()
                ->maxLength(255),

            Forms\Components\DatePicker::make('document_date')
                ->label('Fecha del documento')
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y'),

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
                ->visible(fn(Get $get) => (bool) $get('requires_follow_up')),

            Forms\Components\Textarea::make('observation')
                ->label('Observación')
                ->visible(fn(Get $get) => (bool) $get('requires_follow_up'))
                ->rows(2),

            Forms\Components\Radio::make('has_physical_support')
                ->label('¿Existe soporte físico?')
                ->options(InstitutionalDocument::PHYSICAL_SUPPORT_OPTIONS)
                ->required()
                ->default('no')
                ->live()
                ->inline(),

            Forms\Components\TextInput::make('physical_location')
                ->label('Ubicación física')
                ->visible(fn(Get $get) => $get('has_physical_support') === 'yes'),

            Forms\Components\FileUpload::make('main_file_path')
                ->label('Documento principal')
                ->disk('public')
                ->directory('institutional-documents/main')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->maxSize(20480)
                ->downloadable()
                ->openable(),

            Forms\Components\FileUpload::make('attachments')
                ->label('Anexos')
                ->disk('public')
                ->directory('institutional-documents/attachments')
                ->multiple()
                ->maxFiles(10)
                ->maxSize(20480)
                ->downloadable()
                ->openable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('document_date', 'desc')
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo de documento')
                    ->formatStateUsing(fn($state) => InstitutionalDocument::DOCUMENT_TYPE_OPTIONS[$state] ?? $state),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Nombre / Asunto')
                    ->limit(35),

                Tables\Columns\TextColumn::make('document_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => InstitutionalDocument::STATUS_OPTIONS[$state] ?? $state)
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'in_management',
                        'primary' => 'delivered',
                        'danger'  => 'pending_signature',
                        'success' => fn($state) => in_array($state, ['signed', 'finalized']),
                    ]),

                Tables\Columns\TextColumn::make('main_file_path')
                    ->label('Archivo')
                    ->formatStateUsing(fn($state) => $state ? 'Ver' : '—')
                    ->url(fn($record) => $record->main_file_path
                        ? Storage::disk('public')->url($record->main_file_path)
                        : null
                    )
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar documento')
                    ->mutateFormDataUsing(fn(array $data) => array_merge($data, [
                        'manager_id' => auth()->id(),
                    ])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
