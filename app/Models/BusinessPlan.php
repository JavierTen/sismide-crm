<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BusinessPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'creation_date',
        'business_definition',
        'problems_to_solve',
        'mission',
        'vision',
        'is_capitalized',
        'capitalization_year',
        'value_proposition',
        'requirements_needs',
        'monthly_sales_cop',
        'monthly_sales_units',
        'production_frequency',
        'gross_profitability_rate',
        'cash_flow_growth_rate',
        'internal_return_rate',
        'break_even_units',
        'break_even_cop',
        'current_investment_value',
        'jobs_generated',
        'direct_competitors',
        'target_market',
        'observations',
        'business_plan_path',
        'acquisition_matrix_path',
        'fire_pitch_video_url',
        'production_cycle_video_url',
        'business_model_path',
        'logo_path',
        'is_prioritized',
    ];

    protected $casts = [
        'creation_date' => 'date',
        'is_capitalized' => 'boolean',
        'is_prioritized' => 'boolean',
        'capitalization_year' => 'integer',
        'monthly_sales_cop' => 'decimal:2',
        'monthly_sales_units' => 'integer',
        'gross_profitability_rate' => 'decimal:2',
        'cash_flow_growth_rate' => 'decimal:2',
        'internal_return_rate' => 'decimal:2',
        'break_even_units' => 'integer',
        'break_even_cop' => 'decimal:2',
        'current_investment_value' => 'decimal:2',
        'jobs_generated' => 'integer',
    ];

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function booted(): void
    {
        // Eliminar archivos cuando se actualiza
        static::updating(function (BusinessPlan $plan) {
            $original = $plan->getOriginal();

            $fileFields = [
                'business_plan_path',
                'acquisition_matrix_path',
                'business_model_path',
                'logo_path',
            ];

            foreach ($fileFields as $field) {
                if ($original[$field] && $original[$field] !== $plan->$field) {
                    Storage::disk('public')->delete($original[$field]);
                }
            }
        });

        // Eliminar archivos cuando se elimina (soft delete)
        static::deleting(function (BusinessPlan $plan) {
            $plan->deleteAllFiles();
        });

        // Eliminar archivos cuando se elimina permanentemente
        static::forceDeleting(function (BusinessPlan $plan) {
            $plan->deleteAllFiles();
        });
    }

    /**
     * Eliminar todos los archivos asociados
     */
    protected function deleteAllFiles(): void
    {
        $fileFields = [
            'business_plan_path',
            'acquisition_matrix_path',
            'business_model_path',
            'logo_path',
        ];

        foreach ($fileFields as $field) {
            if ($this->$field) {
                Storage::disk('public')->delete($this->$field);
            }
        }
    }

    /**
     * Relación con el emprendedor
     */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class)->withTrashed();
    }

    /**
     * Relación con el gestor
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Verificar si tiene plan de negocio adjunto
     */
    public function hasBusinessPlanFile(): bool
    {
        return !empty($this->business_plan_path);
    }

    /**
     * Verificar si tiene matriz de adquisición
     */
    public function hasAcquisitionMatrix(): bool
    {
        return !empty($this->acquisition_matrix_path);
    }

    /**
     * Verificar si tiene modelo de negocio
     */
    public function hasBusinessModel(): bool
    {
        return !empty($this->business_model_path);
    }

    /**
     * Verificar si tiene logo
     */
    public function hasLogo(): bool
    {
        return !empty($this->logo_path);
    }

    /**
     * Obtener URL completa del plan de negocio
     */
    public function getBusinessPlanUrlAttribute(): ?string
    {
        if (!$this->business_plan_path) {
            return null;
        }

        return Storage::url($this->business_plan_path);
    }

    /**
     * Obtener URL de la matriz de adquisición
     */
    public function getAcquisitionMatrixUrlAttribute(): ?string
    {
        if (!$this->acquisition_matrix_path) {
            return null;
        }

        return Storage::url($this->acquisition_matrix_path);
    }

    /**
     * Obtener URL del modelo de negocio
     */
    public function getBusinessModelUrlAttribute(): ?string
    {
        if (!$this->business_model_path) {
            return null;
        }

        return Storage::url($this->business_model_path);
    }

    /**
     * Obtener URL del logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::url($this->logo_path);
    }

    /**
     * Scope para filtrar por emprendedor
     */
    public function scopeByEntrepreneur($query, int $entrepreneurId)
    {
        return $query->where('entrepreneur_id', $entrepreneurId);
    }

    /**
     * Scope para filtrar por gestor
     */
    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope para planes capitalizados
     */
    public function scopeCapitalized($query)
    {
        return $query->where('is_capitalized', true);
    }

    /**
     * Scope para planes no capitalizados
     */
    public function scopeNotCapitalized($query)
    {
        return $query->where('is_capitalized', false);
    }

    /**
     * Scope para planes priorizados
     */
    public function scopePrioritized($query)
    {
        return $query->where('is_prioritized', true);
    }

    /**
     * Scope para planes no priorizados
     */
    public function scopeNotPrioritized($query)
    {
        return $query->where('is_prioritized', false);
    }

    /**
     * Opciones para frecuencia de producción
     */
    public static function productionFrequencyOptions(): array
    {
        return [
            'daily' => 'Diaria',
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'biannual' => 'Semestral',
            'annual' => 'Anual',
        ];
    }

    /**
     * Opciones para años de capitalización
     */
    public static function capitalizationYearOptions(): array
    {
        return [
            '2020' => '2020',
            '2021' => '2021',
            '2022' => '2022',
            '2023' => '2023',
            '2024' => '2024',
            '2025' => '2025',
        ];
    }
}
