<?php

namespace App\Exports;

use App\Models\BusinessPlan;
use App\Models\BusinessPlanEvaluation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Consolidado de evaluaciones del plan de negocio: una hoja con el listado
 * ordenado por nota final y otra con la calificación de cada evaluador en
 * cada criterio, para poder justificar cualquier nota.
 */
class EvaluationResultsExport implements WithMultipleSheets
{
    /** @param  Collection<int, BusinessPlan>  $plans  en el orden del listado */
    public function __construct(private readonly Collection $plans)
    {
        $this->plans->loadMissing([
            'entrepreneur.business',
            'entrepreneur.city',
            'evaluatorEvaluations.question',
            'evaluatorEvaluations.evaluator',
            'managerEvaluations',
        ]);
    }

    public function sheets(): array
    {
        return [
            $this->summarySheet(),
            $this->detailSheet(),
        ];
    }

    private function summarySheet(): EvaluationResultsSheet
    {
        // La columna del gestor solo si hay planes del formato anterior.
        $withManager = $this->plans->contains(fn (BusinessPlan $plan) => BusinessPlanEvaluation::managerAppliesToPlan($plan->id));

        $headings = ['Posición', 'Emprendedor', 'Documento', 'Emprendimiento', 'Municipio', 'Año', 'N.º de evaluadores', 'Promedio evaluadores'];

        if ($withManager) {
            $headings[] = 'Calificación gestor';
        }

        $headings[] = 'Nota final';

        $rows = $this->plans->values()->map(function (BusinessPlan $plan, int $i) use ($withManager): array {
            $evaluators = $plan->evaluatorEvaluations->groupBy('evaluator_id');
            $average    = $evaluators->isEmpty() ? null : $evaluators->map(fn ($ratings) => $this->weightedAverage($ratings))->avg();

            $row = [
                $i + 1,
                $plan->entrepreneur?->full_name,
                $plan->entrepreneur?->document_number,
                $plan->entrepreneur?->business?->business_name ?? 'Sin emprendimiento',
                $plan->entrepreneur?->city?->name,
                $plan->created_at?->year,
                $evaluators->count(),
                $average === null ? 'Sin evaluar' : round($average, 2),
            ];

            if ($withManager) {
                $row[] = BusinessPlanEvaluation::managerAppliesToPlan($plan->id)
                    ? ($plan->managerEvaluations->isEmpty() ? 'Sin evaluar' : round((float) $plan->managerEvaluations->avg('score'), 2))
                    : 'No aplica';
            }

            $row[] = $plan->final_score_value === null ? 'Pendiente' : (float) $plan->final_score_value;

            return $row;
        })->all();

        return new EvaluationResultsSheet('Resultados', $headings, $rows);
    }

    private function detailSheet(): EvaluationResultsSheet
    {
        // Un criterio por columna, con el número y el nombre del formato.
        $criteria = $this->plans
            ->flatMap(fn (BusinessPlan $plan) => $plan->evaluatorEvaluations)
            ->mapWithKeys(fn (BusinessPlanEvaluation $e) => [$e->question_number => $e->question_number . '. ' . $e->question?->question_text])
            ->sortKeys();

        $headings = ['Emprendedor', 'Emprendimiento', 'Municipio', 'Evaluador', 'Fecha', ...$criteria->values()->all(), 'Promedio ponderado', 'Recomendaciones'];

        $rows = [];

        foreach ($this->plans as $plan) {
            foreach ($plan->evaluatorEvaluations->groupBy('evaluator_id') as $ratings) {
                $byQuestion = $ratings->keyBy('question_number');

                $rows[] = [
                    $plan->entrepreneur?->full_name,
                    $plan->entrepreneur?->business?->business_name ?? 'Sin emprendimiento',
                    $plan->entrepreneur?->city?->name,
                    $ratings->first()->evaluator?->name,
                    $ratings->max('created_at')?->format('d/m/Y'),
                    ...$criteria->keys()->map(fn ($number) => $byQuestion->has($number) ? (float) $byQuestion[$number]->score : '')->all(),
                    round($this->weightedAverage($ratings), 2),
                    $ratings->first()->comments,
                ];
            }
        }

        return new EvaluationResultsSheet('Detalle por evaluador', $headings, $rows);
    }

    /** Promedio ponderado de un evaluador, como en la pantalla. */
    private function weightedAverage(Collection $ratings): float
    {
        $weight = $ratings->sum(fn ($r) => (float) $r->question?->weight);

        return $weight > 0
            ? $ratings->sum(fn ($r) => (float) $r->score * (float) $r->question?->weight) / $weight
            : 0.0;
    }
}
