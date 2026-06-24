<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\StudentResource\Pages;
use App\Models\DocumentType;
use App\Models\EducationalInstitution;
use App\Models\Gender;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Estudiante';

    protected static ?string $pluralModelLabel = 'Estudiantes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Identificación y relaciones')
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
                                $set('teachers', []);
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

                        Forms\Components\Select::make('teachers')
                            ->label('Docente(s) a cargo')
                            ->relationship(
                                'teachers',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) => $query
                                    ->where('educational_institution_id', $get('educational_institution_id')),
                            )
                            ->placeholder('Primero seleccione la institución')
                            ->helperText('Solo se muestran los docentes registrados en la institución seleccionada.')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Forms\Get $get) => blank($get('educational_institution_id')))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos personales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombres')
                            ->placeholder('Ej: LAURA GOMEZ')
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
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('age')
                            ->label('Edad')
                            ->placeholder('Ej: 16')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(25),

                        Forms\Components\Select::make('gender_id')
                            ->label('Género')
                            ->options(fn () => Gender::pluck('name', 'id'))
                            ->placeholder('Seleccione el género'),

                        Forms\Components\Select::make('grade')
                            ->label('Grado')
                            ->options(Student::gradeOptions())
                            ->placeholder('Seleccione el grado')
                            ->required(),

                        Forms\Components\TextInput::make('course')
                            ->label('Curso')
                            ->placeholder('Ej: 10-1')
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos de contacto')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Ej: 3001234567')
                            ->tel()
                            ->numeric()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->placeholder('Ej: estudiante@correo.com')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Acudiente')
                    ->schema([
                        Forms\Components\TextInput::make('guardian_name')
                            ->label('Nombre acudiente')
                            ->placeholder('Ej: MARIA GOMEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('guardian_phone')
                            ->label('Teléfono acudiente')
                            ->placeholder('Ej: 3007654321')
                            ->tel()
                            ->numeric()
                            ->maxLength(20),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('Grado')
                    ->formatStateUsing(fn (?string $state) => Student::gradeOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('course')
                    ->label('Curso'),

                Tables\Columns\TextColumn::make('educationalInstitution.name')
                    ->label('Institución')
                    ->formatStateUsing(fn (Student $record) => $record->educationalInstitution?->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('educationalInstitution.city.name')
                    ->label('Municipio'),

                Tables\Columns\TextColumn::make('gender.name')
                    ->label('Género'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('educational_institution_id')
                    ->label('Institución')
                    ->options(fn () => EducationalInstitution::get()->mapWithKeys(
                        fn (EducationalInstitution $institution) => [$institution->id => $institution->display_name]
                    )),

                Tables\Filters\SelectFilter::make('grade')
                    ->label('Grado')
                    ->options(Student::gradeOptions()),

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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
}
