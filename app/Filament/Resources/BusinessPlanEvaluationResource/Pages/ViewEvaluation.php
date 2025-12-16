<?php

namespace App\Filament\Resources\BusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\BusinessPlanEvaluationResource;
use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use Filament\Resources\Pages\Page;

class ViewEvaluation extends Page
{
    protected static string $resource = BusinessPlanEvaluationResource::class;

    protected static string $view = 'filament.resources.business-plan-evaluation-resource.pages.view-evaluation';

    public BusinessPlan $record;
    public $evaluations;
    public $average;

    public function mount(int | string $record): void
    {
        $this->record = BusinessPlan::with(['entrepreneur.business', 'entrepreneur.city'])->findOrFail($record);

        $this->evaluations = BusinessPlanEvaluation::where('business_plan_id', $this->record->id)
            ->where('evaluator_id', auth()->id())
            ->where('evaluator_type', 'evaluator')
            ->with('question')
            ->get();

        $this->average = BusinessPlanEvaluation::getEvaluatorAverage($this->record->id, auth()->id());
    }
}
