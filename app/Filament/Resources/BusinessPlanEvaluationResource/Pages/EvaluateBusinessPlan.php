<?php

namespace App\Filament\Resources\BusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\BusinessPlanEvaluationResource;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use App\Models\BusinessPlanEvaluationQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class EvaluateBusinessPlan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BusinessPlanEvaluationResource::class;

    protected static string $view = 'filament.resources.business-plan-evaluation-resource.pages.evaluate-business-plan';

    public ?array $data = [];
    public BusinessPlan $record;

    public function mount(int | string $record): void
    {
        $this->record = BusinessPlan::with(['entrepreneur.business', 'entrepreneur.city'])->findOrFail($record);

        // Verificar si ya evaluó
        if (BusinessPlanEvaluation::hasCompletedEvaluation($this->record->id, auth()->id(), 'evaluator')) {
            Notification::make()
                ->warning()
                ->title('Ya evaluó este plan')
                ->body('Usted ya completó la evaluación de este plan de negocio.')
                ->persistent()
                ->send();

            $this->redirect(BusinessPlanEvaluationResource::getUrl('view-evaluation', ['record' => $this->record->id]));
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $questions = BusinessPlanEvaluationQuestion::forEvaluators()->get();

        $schema = [
            Forms\Components\Section::make('Información del Emprendedor')
                ->description('Datos del emprendedor y su plan de negocio')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('entrepreneur_name')
                                ->label('Emprendedor')
                                ->content($this->record->entrepreneur->full_name),

                            Forms\Components\Placeholder::make('business_name')
                                ->label('Emprendimiento')
                                ->content($this->record->entrepreneur->business?->business_name ?? 'Sin emprendimiento'),

                            Forms\Components\Placeholder::make('city_name')
                                ->label('Municipio')
                                ->content($this->record->entrepreneur->city?->name ?? 'Sin ubicación'),
                        ]),
                ])
                ->collapsible()
                ->collapsed(false),

            Forms\Components\Section::make('Evaluación del Plan de Negocio')
                ->description('Califique cada criterio en una escala de 1 a 10')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema(
                    $questions->map(function ($question) {
                        return Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make("question_{$question->id}_info")
                                    ->label($question->question_number . '. ' . $question->question_text)
                                    ->content(function () use ($question) {
                                        return view('filament.components.question-description', [
                                            'description' => $question->description,
                                            'weight' => $question->weight * 100,
                                        ]);
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make("score_{$question->id}")
                                    ->label('Calificación (1-10)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(10)
                                    ->step(0.1)
                                    ->suffix('/ 10')
                                    ->helperText('Ponderación: ' . ($question->weight * 100) . '%')
                                    ->rules(['required', 'numeric', 'min:1', 'max:10'])
                                    ->columnSpan(1),
                            ]);
                    })->toArray()
                )
                ->collapsible()
                ->collapsed(false),

            Forms\Components\Section::make('Recomendaciones')
                ->description('Agregue sus recomendaciones para mejorar el plan de negocio')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    Forms\Components\Textarea::make('comments')
                        ->label('¿Qué recomendación le daría al emprendedor(a) para mejorar su plan de negocio?')
                        ->rows(5)
                        ->placeholder('Escriba sus recomendaciones aquí...')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false),
        ];

        return $form->schema($schema)->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $questions = BusinessPlanEvaluationQuestion::forEvaluators()->get();

        try {
            DB::beginTransaction();

            foreach ($questions as $question) {
                $scoreKey = "score_{$question->id}";

                if (!isset($data[$scoreKey])) {
                    throw new \Exception("Falta la calificación para la pregunta: {$question->question_text}");
                }

                BusinessPlanEvaluation::create([
                    'business_plan_id' => $this->record->id,
                    'evaluator_id' => auth()->id(),
                    'question_id' => $question->id,
                    'evaluator_type' => 'evaluator',
                    'question_number' => $question->question_number,
                    'score' => $data[$scoreKey],
                    'comments' => $data['comments'] ?? null,
                ]);
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Evaluación guardada exitosamente')
                ->body('Su evaluación ha sido registrada correctamente.')
                ->send();

            $this->redirect(BusinessPlanEvaluationResource::getUrl('index'));

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error al guardar la evaluación')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('submit')
                ->label('Guardar Evaluación')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('submit'),

            Forms\Components\Actions\Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->url(BusinessPlanEvaluationResource::getUrl('index')),
        ];
    }
}
