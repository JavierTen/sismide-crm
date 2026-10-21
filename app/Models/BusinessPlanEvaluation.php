<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TracksUpdatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BusinessPlanEvaluation extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy, LogsModelActivity;

    protected $fillable = [
        'business_plan_id',
        'evaluator_id',
        'question_id',
        'evaluator_type',
        'question_number',
        'score',
        'comments',
        'updated_by_id',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'question_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

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
        // Incluye usuarios eliminados: su calificación sigue contando y el
        // historial debe mostrar quién la hizo.
        return $this->belongsTo(User::class, 'evaluator_id')->withTrashed();
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
    public static function getFinalScore(int $businessPlanId): float
    {
        $evaluatorsAvg = self::getAllEvaluatorsAverage($businessPlanId);

        // Desde 2026 la nota final es solo el promedio de los evaluadores.
        if (! self::managerAppliesToPlan($businessPlanId)) {
            return round($evaluatorsAvg, 2);
        }

        $managersAvg = self::getAllManagersAverage($businessPlanId);

        // Formato 2025: 90% evaluadores + 10% gestores
        $finalScore = ($evaluatorsAvg * 0.90) + ($managersAvg * 0.10);

        return round($finalScore, 2);
    }

    /**
     * La misma nota final como expresión SQL, para ordenar y filtrar el
     * consolidado en la consulta. Cada evaluador califica todas las preguntas
     * de su formato, así que el promedio ponderado de todas sus calificaciones
     * equivale al promedio de los promedios de cada evaluador.
     */
    public static function finalScoreSql(string $plans = 'business_plans'): string
    {
        $evaluators = "(SELECT SUM(e.score * q.weight) / NULLIF(SUM(q.weight), 0)
            FROM business_plan_evaluations e
            JOIN business_plan_evaluation_questions q ON q.id = e.question_id
            WHERE e.business_plan_id = {$plans}.id AND e.evaluator_type = 'evaluator' AND e.deleted_at IS NULL)";

        $managers = "(SELECT AVG(m.score)
            FROM business_plan_evaluations m
            WHERE m.business_plan_id = {$plans}.id AND m.evaluator_type = 'manager' AND m.deleted_at IS NULL)";

        $from = BusinessPlanEvaluationQuestion::EVALUATORS_ONLY_FROM;

        return "(CASE
            WHEN {$evaluators} IS NULL AND (YEAR({$plans}.created_at) >= {$from} OR {$managers} IS NULL) THEN NULL
            WHEN YEAR({$plans}.created_at) >= {$from} THEN ROUND({$evaluators}, 2)
            ELSE ROUND(COALESCE({$evaluators}, 0) * 0.90 + COALESCE({$managers}, 0) * 0.10, 2)
        END)";
    }

    /** @var array<int, ?int> */
    private static array $planYears = [];

    /** Año del plan de negocio: define su formato de evaluación. */
    public static function planYear(int $businessPlanId): ?int
    {
        if (! array_key_exists($businessPlanId, self::$planYears)) {
            $createdAt = BusinessPlan::withoutGlobalScope(YearColumnScope::class)
                ->withTrashed()
                ->whereKey($businessPlanId)
                ->value('created_at');

            self::$planYears[$businessPlanId] = $createdAt ? (int) substr((string) $createdAt, 0, 4) : null;
        }

        return self::$planYears[$businessPlanId];
    }

    /** ¿La calificación del gestor cuenta para la nota final de este plan? */
    public static function managerAppliesToPlan(int $businessPlanId): bool
    {
        return BusinessPlanEvaluationQuestion::managerApplies(self::planYear($businessPlanId) ?? now()->year);
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
        $year = self::planYear($businessPlanId);

        $expectedQuestions = $evaluatorType === 'manager'
            ? BusinessPlanEvaluationQuestion::forManagers($year)->count()
            : BusinessPlanEvaluationQuestion::forEvaluators($year)->count();

        if ($expectedQuestions === 0) {
            return false;
        }

        $completedQuestions = self::where('business_plan_id', $businessPlanId)
            ->where('evaluator_id', $evaluatorId)
            ->where('evaluator_type', $evaluatorType)
            ->count();

        return $completedQuestions >= $expectedQuestions;
    }
}
