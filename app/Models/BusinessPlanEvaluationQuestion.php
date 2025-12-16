<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessPlanEvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_number',
        'question_text',
        'description',
        'target_role',
        'weight',
        'order',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'question_number' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Relación con las evaluaciones
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(BusinessPlanEvaluation::class, 'question_id');
    }

    /**
     * Scope para preguntas de evaluadores
     */
    public function scopeForEvaluators($query)
    {
        return $query->where('target_role', 'evaluator')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope para preguntas de gestores
     */
    public function scopeForManagers($query)
    {
        return $query->where('target_role', 'manager')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope para preguntas activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Obtener todas las preguntas agrupadas por rol
     */
    public static function getQuestionsByRole(): array
    {
        return [
            'evaluator' => self::forEvaluators()->get(),
            'manager' => self::forManagers()->get(),
        ];
    }

    /**
     * Obtener peso total de preguntas de evaluadores
     */
    public static function getEvaluatorsTotalWeight(): float
    {
        return self::where('target_role', 'evaluator')
            ->where('is_active', true)
            ->sum('weight');
    }

    /**
     * Obtener peso total de preguntas de gestores
     */
    public static function getManagersTotalWeight(): float
    {
        return self::where('target_role', 'manager')
            ->where('is_active', true)
            ->sum('weight');
    }
}
