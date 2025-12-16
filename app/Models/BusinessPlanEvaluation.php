<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BusinessPlanEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_plan_id',
        'evaluator_id',
        'question_id',
        'evaluator_type',
        'question_number',
        'score',
        'comments',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'question_number' => 'integer',
    ];

    /**
     * Relación con el plan de negocio
     */
    public function businessPlan(): BelongsTo
    {
        return $this->belongsTo(BusinessPlan::class);
    }

    /**
     * Relación con el evaluador (usuario)
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Relación con la pregunta
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(BusinessPlanEvaluationQuestion::class, 'question_id');
    }

    /**
     * Scope para evaluaciones de evaluadores
     */
    public function scopeByEvaluators($query)
    {
        return $query->where('evaluator_type', 'evaluator');
    }

    /**
     * Scope para evaluaciones de gestores
     */
    public function scopeByManagers($query)
    {
        return $query->where('evaluator_type', 'manager');
    }

    /**
     * Scope para un plan específico
     */
    public function scopeForBusinessPlan($query, int $businessPlanId)
    {
        return $query->where('business_plan_id', $businessPlanId);
    }

    /**
     * Calcular el promedio de un evaluador para un plan específico
     */
    public static function getEvaluatorAverage(int $businessPlanId, int $evaluatorId): float
    {
        $evaluations = self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_id', $evaluatorId)
            ->where('evaluator_type', 'evaluator')
            ->with('question')
            ->get();

        if ($evaluations->isEmpty()) {
            return 0;
        }

        // Calcular promedio ponderado
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($evaluations as $evaluation) {
            $weight = $evaluation->question->weight;
            $totalWeightedScore += $evaluation->score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
    }

    /**
     * Calcular el promedio de todos los evaluadores para un plan
     */
    public static function getAllEvaluatorsAverage(int $businessPlanId): float
    {
        $evaluatorIds = self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_type', 'evaluator')
            ->distinct()
            ->pluck('evaluator_id');

        if ($evaluatorIds->isEmpty()) {
            return 0;
        }

        $averages = [];
        foreach ($evaluatorIds as $evaluatorId) {
            $averages[] = self::getEvaluatorAverage($businessPlanId, $evaluatorId);
        }

        return count($averages) > 0 ? array_sum($averages) / count($averages) : 0;
    }

    /**
     * Calcular el promedio de todos los gestores para un plan
     */
    public static function getAllManagersAverage(int $businessPlanId): float
    {
        $managerScores = self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_type', 'manager')
            ->pluck('score');

        if ($managerScores->isEmpty()) {
            return 0;
        }

        return $managerScores->avg();
    }

    /**
     * Calcular el puntaje final de un plan de negocio
     */
    public static function getFinalScore(int $businessPlanId): array
    {
        $evaluatorsAvg = self::getAllEvaluatorsAverage($businessPlanId);
        $managersAvg = self::getAllManagersAverage($businessPlanId);
        $finalScore = ($evaluatorsAvg * 0.90) + ($managersAvg * 0.10);

        return [
            'evaluators_average' => round($evaluatorsAvg, 2),
            'managers_average' => round($managersAvg, 2),
            'final_score' => round($finalScore, 2),
        ];
    }

    /**
     * Verificar si un gestor puede evaluar un plan (no puede ser su propio emprendedor)
     */
    public static function canManagerEvaluate(int $businessPlanId, int $managerId): bool
    {
        $businessPlan = BusinessPlan::with('entrepreneur')->find($businessPlanId);

        if (!$businessPlan) {
            return false;
        }

        // El gestor NO puede evaluar a su propio emprendedor
        return $businessPlan->entrepreneur->manager_id !== $managerId;
    }

    /**
     * Obtener el conteo de evaluadores que han evaluado un plan
     */
    public static function getEvaluatorsCount(int $businessPlanId): int
    {
        return self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_type', 'evaluator')
            ->distinct('evaluator_id')
            ->count('evaluator_id');
    }

    /**
     * Obtener el conteo de gestores que han evaluado un plan
     */
    public static function getManagersCount(int $businessPlanId): int
    {
        return self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_type', 'manager')
            ->distinct('evaluator_id')
            ->count('evaluator_id');
    }

    /**
     * Verificar si un evaluador ya completó su evaluación
     */
    public static function hasCompletedEvaluation(int $businessPlanId, int $evaluatorId, string $evaluatorType): bool
    {
        $expectedQuestions = BusinessPlanEvaluationQuestion::where('target_role', $evaluatorType)
            ->where('is_active', true)
            ->count();

        $completedQuestions = self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_id', $evaluatorId)
            ->where('evaluator_type', $evaluatorType)
            ->count();

        return $completedQuestions >= $expectedQuestions;
    }
}
