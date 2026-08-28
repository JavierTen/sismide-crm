<?php

namespace App\Filament\Eje\Resources;

use App\Filament\Eje\Resources\StudentCanvasResource\Pages;
use App\Models\Student;
use App\Models\StudentCanvas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentCanvasResource extends Resource
{
    protected static ?string $model = StudentCanvas::class;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Comunidad Educativa';
    protected static ?string $navigationLabel = 'Canvas Estudiantes';
    protected static ?string $modelLabel      = 'Canvas';
    protected static ?string $pluralModelLabel = 'Canvas';
    protected static ?int    $navigationSort  = 10;

    private static function userCanList(): bool   { return auth()->user()?->can('listStudentCanvases') ?? false; }
    private static function userCanCreate(): bool { return auth()->user()?->can('createStudentCanvas') ?? false; }
    private static function userCanEdit(): bool   { return auth()->user()?->can('editStudentCanvas') ?? false; }
    private static function userCanDelete(): bool  { return auth()->user()?->can('deleteStudentCanvas') ?? false; }

    public static function canViewAny(): bool               { return static::userCanList(); }
    public static function canCreate(): bool                { return static::userCanCreate(); }
    public static function canEdit($record): bool           { return static::userCanEdit(); }
    public static function canDelete($record): bool         { return static::userCanDelete(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['student.educationalInstitution']);
    }

    // ── FORMULARIO ─────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Estudiante')
                ->icon('heroicon-o-academic-cap')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Estudiante')
                        ->columnSpan(2)
                        ->options(fn () => Student::withoutTrashed()
                            ->with('educationalInstitution')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => $s->name . ' — ' . ($s->educationalInstitution?->name ?? '—'),
                            ])
                            ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->dehydrated(true)
                        ->live()
                        ->unique(table: 'student_canvases', column: 'student_id', ignoreRecord: true),

                    Forms\Components\Placeholder::make('institution')
                        ->label('Institución')
                        ->content(fn (Get $get) => static::getStudentField($get('student_id'), 'institution')),

                    Forms\Components\Placeholder::make('grade')
                        ->label('Grado')
                        ->content(fn (Get $get) => static::getStudentField($get('student_id'), 'grade')),
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
                        ->directory('student-canvas')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->required()
                        ->downloadable()
                        ->openable()
                        ->helperText('Obligatorio — PDF, JPG o PNG (máx. 10 MB)'),

                    Forms\Components\TextInput::make('fire_pitch_video_url')
                        ->label('Video Fire Pitch (URL) *')
                        ->url()
                        ->required()
                        ->placeholder('https://youtube.com/...')
                        ->helperText('Obligatorio para estudiantes EJE'),
                ]),

        ]);
    }

    // ── TABLA ───────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Estudiante')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.educationalInstitution.name')
                    ->label('Institución')
                    ->default('—'),

                Tables\Columns\TextColumn::make('student.grade')
                    ->label('Grado')
                    ->formatStateUsing(fn ($state) => $state ? $state . '°' : '—'),

                Tables\Columns\IconColumn::make('canvas_file_path')
                    ->label('Canvas')
                    ->boolean()
                    ->getStateUsing(fn ($record) => ! empty($record->canvas_file_path))
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('fire_pitch_video_url')
                    ->label('Fire Pitch')
                    ->boolean()
                    ->getStateUsing(fn ($record) => ! empty($record->fire_pitch_video_url))
                    ->trueIcon('heroicon-o-play-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
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
            'index'  => Pages\ListStudentCanvases::route('/'),
            'create' => Pages\CreateStudentCanvas::route('/create'),
            'edit'   => Pages\EditStudentCanvas::route('/{record}/edit'),
        ];
    }

    // ── HELPERS ─────────────────────────────────────────────────────────────────

    private static function getStudentField(?int $id, string $field): string
    {
        if (! $id) return '—';
        $s = Student::with('educationalInstitution')->find($id);
        if (! $s) return '—';

        return match ($field) {
            'institution' => $s->educationalInstitution?->name ?? '—',
            'grade'       => $s->grade ? $s->grade . '°' : '—',
            default       => '—',
        };
    }
}
