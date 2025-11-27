<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessDiagnosis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'diagnosis_date',
        'has_news',
        'news_type',
        'news_date',
        'administrative_section',
        'financial_section',
        'production_section',
        'market_section',
        'technology_section',
        'general_observations',
        'work_sections',
        'total_score',
        'maturity_level',
    ];

    protected $casts = [
        'diagnosis_date' => 'date',
        'news_date' => 'date',
        'has_news' => 'boolean',
        'administrative_section' => 'array',
        'financial_section' => 'array',
        'production_section' => 'array',
        'market_section' => 'array',
        'technology_section' => 'array',
        'work_sections' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($diagnosis) {
            $diagnosis->total_score = $diagnosis->calculateTotalScore();

            if ($diagnosis->total_score !== null) {
                $maturity = $diagnosis->getMaturityLevel();
                $diagnosis->maturity_level = $maturity['label'];
            }
        });
    }

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class)->withTrashed();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    /**
     * SCORING MAPS - Mapeo de respuestas a puntuaciones
     */

    public static function getAdministrativeScoring(): array
    {
        return [
            'task_organization' => [
                'no_organization' => 0,
                'informal' => 1,
                'basic_list' => 2,
                'not_applicable' => 0,
            ],
            'resource_planning' => [
                'no_planning' => 0,
                'basic_plan' => 1,
                'planning_tools' => 3,
                'not_applicable' => 0,
            ],
            'communication_channels' => [
                'irregular' => 0,
                'periodic_traditional' => 1,
                'digital_regular' => 3,
                'not_applicable' => 0,
            ],
            'purchase_management' => [
                'basic_tracking' => 1,
                'planned_system' => 2,
                'advanced_software' => 3,
                'not_applicable' => 0,
            ],
            'distribution' => [
                'self' => 1,
                'outsourced' => 2,
                'not_applicable' => 0,
            ],
        ];
    }

    public static function getFinancialScoring(): array
    {
        return [
            'income_expenses_record' => [
                'no_record' => 0,
                'basic_manual' => 1,
                'digital_tools' => 3,
                'not_applicable' => 0,
            ],
            'last_month_income' => [
                'lt_500k' => 0,
                '500k_1m' => 1,
                '1m_2m' => 2,
                '2m_5m' => 3,
                'gt_5m' => 4,
            ],
            'quarterly_income' => [
                'negative' => 0,
                'positive' => 2,
                'not_applicable' => 0,
            ],
            'knows_margin' => [
                'no' => 0,
                'yes' => 2,
                'not_applicable' => 0,
            ],
            'profit_margin' => [
                '10_20' => 1,
                '20_30' => 2,
                '30_40' => 3,
                '40_50' => 4,
                'gt_50' => 5,
                'not_applicable' => 0,
            ],
            'external_financing' => [
                'no_external' => 0,
                'formal_financing' => 3,
                'informal_financing' => 2,
                'reinvestment' => 2,
                'not_applicable' => 0,
            ],
            'budget_planning' => [
                'no_planning' => 0,
                'irregular_intuitive' => 1,
                'basic_planning' => 2,
                'established_process' => 3,
                'not_applicable' => 0,
            ],
            'business_investments' => [
                'no_planned' => 0,
                'basic_immediate' => 1,
                'planned_growth' => 3,
                'not_applicable' => 0,
            ],
            'payment_methods' => [
                'cash' => 0,
                'digital_transfer' => 2,
                'pos_terminal' => 2,
                'not_applicable' => 0,
            ],
            'accounts_management' => [
                'no_specific_record' => 0,
                'basic_informal' => 1,
                'organized_record' => 2,
                'not_applicable' => 0,
            ],
            'tax_obligations' => [
                'no_knowledge' => 0,
                'basic_compliance' => 2,
                'planned_review' => 3,
                'not_applicable' => 0,
            ],
        ];
    }

    public static function getProductionScoring(): array
    {
        return [
            'production_planning' => [
                'no_planning' => 0,
                'basic_demand' => 1,
                'structured_planning' => 3,
                'not_applicable' => 0,
            ],
            'quality_control' => [
                'no_quality_control' => 0,
                'sporadic_control' => 1,
                'documented_standards' => 3,
                'not_applicable' => 0,
            ],
            'raw_materials_optimization' => [
                'no_attention' => 0,
                'basic_system' => 1,
                'efficient_management' => 3,
                'not_applicable' => 0,
            ],
            'innovation' => [
                'no_innovation' => 0,
                'has_innovation' => 2,
                'not_applicable' => 0,
            ],
            'safety_hygiene' => [
                'no_measures' => 0,
                'basic_measures' => 1,
                'established_protocols' => 3,
                'not_applicable' => 0,
            ],
            'equipment_maintenance' => [
                'no_plan' => 0,
                'reactive_maintenance' => 1,
                'preventive_calendar' => 3,
                'not_applicable' => 0,
            ],
            'waste_management' => [
                'no_management' => 0,
                'basic_compliance' => 1,
                'reduce_recycle' => 3,
                'not_applicable' => 0,
            ],
        ];
    }

    public static function getMarketScoring(): array
    {
        return [
            'customer_identification' => [
                'casual_intuitive' => 0,
                'basic_idea' => 1,
                'periodic_analysis' => 3,
                'not_applicable' => 0,
            ],
            'market_opportunities' => [
                'occasional_casual' => 0,
                'basic_research' => 1,
                'structured_method' => 3,
                'not_applicable' => 0,
            ],
            'market_adaptation' => [
                'reactive_big_changes' => 0,
                'basic_tracking' => 1,
                'proactive_update' => 3,
                'not_applicable' => 0,
            ],
            'promotion_advertising' => [
                'no_promotion' => 0,
                'sporadic_traditional' => 1,
                'structured_plan' => 3,
                'not_applicable' => 0,
            ],
            'competition_analysis' => [
                'no_analysis' => 0,
                'basic_awareness' => 1,
                'regular_tracking' => 3,
                'not_applicable' => 0,
            ],
            'sales_channels' => [
                'no_exploration' => 0,
                'occasional_experiment' => 1,
                'strategic_analysis' => 3,
                'not_applicable' => 0,
            ],
        ];
    }

    public static function getTechnologyScoring(): array
    {
        return [
            'technology_usage' => [
                'no_technology' => 0,
                'basic_traditional' => 2,
                'modern_technology' => 4,
                'not_applicable' => 0,
            ],
            'data_management' => [
                'no_management' => 0,
                'basic_manual' => 2,
                'tech_tools' => 4,
                'not_applicable' => 0,
            ],
            'staff_training' => [
                'no_training' => 0,
                'occasional_unstructured' => 2,
                'basic_relevant' => 4,
                'not_applicable' => 0,
            ],
            'office_tools' => [
                'no_office_tools' => 0,
                'use_office_tools' => 4,
                'not_applicable' => 0,
            ],
            'tech_adaptation' => [
                'no_adaptation' => 0,
                'occasional_no_strategy' => 2,
                'basic_adjustments' => 4,
                'not_applicable' => 0,
            ],
        ];
    }

    /**
     * Calcula el puntaje total del diagnóstico
     */
    public function calculateTotalScore(): int
    {
        $score = 0;

        // Sección Administrativa (15 pts)
        $score += $this->calculateSectionScore($this->administrative_section, self::getAdministrativeScoring());

        // Sección Financiera (25 pts)
        $score += $this->calculateSectionScore($this->financial_section, self::getFinancialScoring());

        // Sección Producción (20 pts)
        $score += $this->calculateSectionScore($this->production_section, self::getProductionScoring());

        // Sección Mercado (20 pts)
        $score += $this->calculateSectionScore($this->market_section, self::getMarketScoring());

        // Sección Tecnología (20 pts)
        $score += $this->calculateSectionScore($this->technology_section, self::getTechnologyScoring());

        return $score;
    }

    /**
     * Calcula el puntaje de una sección específica
     */
    private function calculateSectionScore(?array $section, array $scoring): int
    {
        if (empty($section)) {
            return 0;
        }

        $score = 0;
        foreach ($section as $question => $answer) {
            if (isset($scoring[$question][$answer])) {
                $score += $scoring[$question][$answer];
            }
        }

        return $score;
    }

    /**
     * Obtiene el nivel de madurez empresarial basado en el puntaje
     */
    public function getMaturityLevel(): array
    {
        $score = $this->total_score ?? 0;

        if ($score >= 0 && $score <= 15) {
            return [
                'level' => 0,
                'name' => 'Pre-emprendimiento y validación temprana',
                'label' => 'Nivel 0: Pre-emprendimiento y validación temprana',
                'color' => 'danger',
            ];
        } elseif ($score >= 16 && $score <= 30) {
            return [
                'level' => 1,
                'name' => 'Pre-emprendimiento y validación temprana',
                'label' => 'Nivel 1: Pre-emprendimiento y validación temprana',
                'color' => 'warning',
            ];
        } elseif ($score >= 31 && $score <= 50) {
            return [
                'level' => 2,
                'name' => 'Pre-emprendimiento y validación temprana',
                'label' => 'Nivel 2: Pre-emprendimiento y validación temprana',
                'color' => 'primary',
            ];
        } elseif ($score >= 51 && $score <= 70) {
            return [
                'level' => 3,
                'name' => 'Consolidación',
                'label' => 'Nivel 3: Consolidación',
                'color' => 'info',
            ];
        } elseif ($score >= 71 && $score <= 85) {
            return [
                'level' => 4,
                'name' => 'Consolidación',
                'label' => 'Nivel 4: Consolidación',
                'color' => 'success',
            ];
        } else { // 86-100
            return [
                'level' => 5,
                'name' => 'Escalamiento',
                'label' => 'Nivel 5: Escalamiento e Innovación',
                'color' => 'success',
            ];
        }
    }

    /**
     * Opciones para tipos de novedad
     */
    public static function newsTypeOptions(): array
    {
        return [
            'reactivation' => 'Reactivación',
            'definitive_closure' => 'Cierre de Emprendimiento definitivo',
            'temporary_closure' => 'Cierre de Emprendimiento temporal',
            'permanent_disability' => 'Incapacidad Permanente',
            'temporary_disability' => 'Incapacidad Temporal',
            'definitive_retirement' => 'Retiro definitivo',
            'temporary_retirement' => 'Retiro temporal',
            'address_change' => 'Cambio de domicilio',
            'owner_death' => 'Muerte del titular',
            'no_news' => 'Sin novedad',
        ];
    }

    /**
     * Opciones para secciones de trabajo
     */
    public static function workSectionOptions(): array
    {
        return [
            'administrative' => 'Sección Administrativa',
            'financial' => 'Sección Financiera y Contable',
            'production' => 'Sección De Producción',
            'market' => 'Sección De Mercado y comercial',
            'technology' => 'Sección Digital Tecnología',
        ];
    }

    /**
     * Check if diagnosis is complete
     */
    public function isComplete(): bool
    {
        return !empty($this->administrative_section) &&
               !empty($this->financial_section) &&
               !empty($this->production_section) &&
               !empty($this->market_section) &&
               !empty($this->technology_section) &&
               !empty($this->work_sections) &&
               count($this->work_sections) >= 2;
    }
}
