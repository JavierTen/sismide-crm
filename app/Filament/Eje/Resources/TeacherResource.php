<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\TeacherResource\Pages;
use App\Models\DocumentType;
use App\Models\EducationalInstitution;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Docente';

    protected static ?string $pluralModelLabel = 'Docentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Identificación y relación')
                    ->schema([
                        Forms\Components\Select::make('educational_institution_id')
                            ->label('Institución Educativa')
                            ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                                fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                            ))
                            ->placeholder('Seleccione la institución')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set(
                                    'municipio_derivado',
                                    $state ? EducationalInstitution::find($state)?->city?->name : null
                                );
                            })
                            ->required(),

                        Forms\Components\TextInput::make('municipio_derivado')
                            ->label('Municipio')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se completa según la institución')
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $record) {
                                if ($record) {
                                    $component->state($record->educationalInstitution?->city?->name);
                                }
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos generales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej: ANA MARTINEZ')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\Select::make('document_type_id')
                            ->label('Tipo de documento')
                            ->options(fn () => DocumentType::pluck('name', 'id'))
                            ->placeholder('Seleccione el tipo de documento')
                            ->required(),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Documento de identidad')
                            ->placeholder('Ej: 1065432109')
                            ->inputMode('numeric')
                            ->maxLength(20)
                            ->regex('/^[0-9]+$/')
                            ->extraInputAttributes([
                                'maxlength' => '20',
                                'oninput'   => "this.value=this.value.replace(/[^0-9]/g,'').slice(0,20)",
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('area')
                            ->label('Área')
                            ->placeholder('Ej: CIENCIAS SOCIALES')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->placeholder('Ej: docente@correo.edu.co')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Ej: 3001234567')
                            ->tel()
                            ->inputMode('numeric')
                            ->maxLength(10)
                            ->regex('/^[0-9]{10}$/')
                            ->extraInputAttributes([
                                'maxlength' => '10',
                                'oninput'   => "this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)",
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vinculación al programa')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(Teacher::statusOptions())
                            ->placeholder('Seleccione el estado')
                            ->default('active')
                            ->required(),

                        Forms\Components\DatePicker::make('program_start_date')
                            ->label('Fecha de vinculación al programa')
                            ->placeholder('Seleccione la fecha'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->placeholder('Notas del gestor')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('Área'),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (Teacher $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state) => Teacher::statusOptions()[$state] ?? $state)
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('educational_institution_id')
                    ->label('Institución')
                    ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                        fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                    )),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole(['Admin', 'Viewer'])) {
            return $query;
        }

        return $query->where('manager_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
}
