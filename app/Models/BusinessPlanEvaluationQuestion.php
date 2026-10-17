<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessPlanEvaluationQuestion extends Model
{
    use HasFactory;

    /** Desde este año la nota final es solo el promedio de los evaluadores. */
    public const EVALUATORS_ONLY_FROM = 2026;

    /** Factores que considera el criterio global, por formato. */
    private const GLOBAL_FACTORS = [
        2025 => [
            'Escalabilidad'                          => 'El negocio tiene la capacidad de crecer exponencialmente. Enfoque hacia la formalización y fortalecimiento de la economía formal.',
            'Tendencias y disrupciones del mercado'  => 'Los cambios disruptivos en la industria, pueden afectar a la empresa.',
            'Adaptabilidad del equipo emprendedor'   => 'El equipo de liderazgo de la empresa puede adaptarse y manejar cambios en el entorno.',
            'Barreras de entrada y competencia'      => 'Dificultad para entrar al mercado y enfrentar a la competencia existente en el largo plazo.',
            'Expansión de mercados y productos'      => 'Capacidad para introducir nuevos productos o servicios y expandirse a nuevos mercados.',
            'Retorno de la inversión'                => 'Expectativas de beneficios económicos que la empresa podría generar tanto a corto como a largo plazo.',
        ],
        2026 => [
            'Ubicación física del emprendimiento'    => 'El emprendimiento dispone de un espacio físico destinado a su operación, que garantiza la continuidad de sus actividades y ofrece condiciones para ampliar su capacidad productiva conforme a su crecimiento.',
            'Escalabilidad'                          => 'El negocio tiene la capacidad de crecer exponencialmente.',
            'Tendencias y disrupciones del mercado'  => 'Los cambios disruptivos en la industria, pueden afectar a la empresa.',
            'Adaptabilidad del equipo emprendedor'   => 'El equipo de liderazgo de la empresa puede adaptarse y manejar cambios en el entorno.',
            'Barreras de entrada y competencia'      => 'Dificultad para entrar al mercado y enfrentar a la competencia existente en el largo plazo.',
            'Expansión de mercados y productos'      => 'Capacidad para introducir nuevos productos o servicios y expandirse a nuevos mercados.',
            'Retorno de la inversión'                => 'Expectativas de beneficios económicos que la empresa podría generar tanto a corto como a largo plazo.',
        ],
    ];

    /** @var array<int, int> */
    private static array $formatCache = [];

    protected $fillable = [
        'format_year',
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
        'format_year' => 'integer',
    ];

    /**
     * Formato que aplica a un año: el más reciente que haya empezado.
     */
    public static function formatYearFor(?int $year): int
    {
        $year ??= now()->year;

        return static::$formatCache[$year] ??= (int) (static::where('format_year', '<=', $year)->max('format_year')
            ?? static::min('format_year')
            ?? $year);
    }

    /** ¿El gestor participa en la nota final de los planes de ese año? */
    public static function managerApplies(?int $year): bool
    {
        return $year === null || $year < self::EVALUATORS_ONLY_FROM;
    }

    /** @return array<string, string> título => descripción */
    public static function globalFactors(int $formatYear): array
    {
        $formats = array_filter(array_keys(self::GLOBAL_FACTORS), fn (int $y) => $y <= $formatYear);

        return self::GLOBAL_FACTORS[$formats === [] ? 2025 : max($formats)];
    }

    /** ¿Es el criterio global (el que agrupa varios factores)? */
    public function isGlobalCriterion(): bool
    {
        return $this->target_role === 'evaluator' && $this->question_number === 11;
    }

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
    public function scopeForEvaluators($query, ?int $year = null)
    {
        return $query->where('target_role', 'evaluator')
            ->where('format_year', static::formatYearFor($year))
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope para preguntas de gestores
     */
    public function scopeForManagers($query, ?int $year = null)
    {
        return $query->where('target_role', 'manager')
            ->where('format_year', static::formatYearFor($year))
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
    public static function getEvaluatorsTotalWeight(?int $year = null): float
    {
        return (float) self::forEvaluators($year)->sum('weight');
    }

    /**
     * Obtener peso total de preguntas de gestores
     */
    public static function getManagersTotalWeight(?int $year = null): float
    {
        return (float) self::forManagers($year)->sum('weight');
    }
}
