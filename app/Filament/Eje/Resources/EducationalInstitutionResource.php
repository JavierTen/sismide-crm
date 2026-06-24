<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\EducationalInstitutionResource\Pages;
use App\Models\City;
use App\Models\EducationalInstitution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EducationalInstitutionResource extends Resource
{
    protected static ?string $model = EducationalInstitution::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Comunidad Educativa';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Institución Educativa';

    protected static ?string $pluralModelLabel = 'Instituciones Educativas';

    /**
     * Municipios habilitados para este subproyecto.
     */
    private const ALLOWED_CITY_IDS = [21, 49, 219, 352, 1096];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('manager_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Select::make('city_id')
                            ->label('Municipio')
                            ->options(fn () => City::whereIn('id', self::ALLOWED_CITY_IDS)->pluck('name', 'id'))
                            ->placeholder('Seleccione el municipio')
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Institución Educativa')
                            ->placeholder('Ej: I.E. NORMAL SUPERIOR')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('campus')
                            ->label('Sede')
                            ->placeholder('Ej: SEDE PRINCIPAL')
                            ->helperText('Si la institución no tiene sedes, escriba "Principal".')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('principal_name')
                            ->label('Nombre Rector(a)')
                            ->placeholder('Ej: MARIA RODRIGUEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('proposed_teacher')
                            ->label('Docente Propuesto')
                            ->placeholder('Ej: CARLOS GOMEZ')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn (?string $state) => $state ? mb_strtoupper($state) : null),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->placeholder('Ej: 3001234567')
                            ->tel()
                            ->numeric()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->placeholder('Ej: institucion@correo.edu.co')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Institución')
                    ->formatStateUsing(fn (EducationalInstitution $record) => $record->display_name)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('principal_name')
                    ->label('Rector(a)'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo'),
            ])
            ->filters([
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
            'index' => Pages\ListEducationalInstitutions::route('/'),
            'create' => Pages\CreateEducationalInstitution::route('/create'),
            'edit' => Pages\EditEducationalInstitution::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
}
