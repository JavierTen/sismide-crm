<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Characterization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'economic_activity_id',
        'population_id',
        'characterization_date',
        // c. Estado del Emprendimiento
        'business_current_state',
        'maturity_level',
        // d. Características del Negocio
        'business_type',
        'business_age',
        'clients',
        'clients_other',
        'promotion_strategies',
        'promotion_strategies_other',
        'market_coverage',
        'average_monthly_sales',
        'direct_jobs',
        'indirect_jobs',
        // e. Formalización
        'has_commercial_registration',
        'mercantile_registration_number',
        'mercantile_registration_expiry',
        'has_accounting_records',
        'accounting_method',
        'accounting_method_other',
        'has_business_bank_account',
        'bank_name',
        'has_operation_licenses',
        'licenses_description',
        'family_in_drummond',
        'drummond_family_relationship',
        // f. Infraestructura
        'activity_location',
        'latitude',
        'longitude',
        // g. Impacto Social
        'economic_dependents',
        'benefited_families',
        // h. Información Financiera
        'monthly_costs',
        'monthly_expenses',
        'monthly_profit',
        'has_active_credits',
        'credit_entity',
        'credit_amount',
        'has_family_employees',
        'family_employees_count',
        'hires_women',
        'women_employees_count',
        // j. Producción y Operación
        'monthly_production_capacity',
        'equipment_and_tools',
        'main_suppliers',
        // k. Innovación y Tecnología
        'tech_capacity_level',
        'has_innovation',
        'innovation_description',
        'digital_tools',
        // l. Diagnóstico
        'main_difficulties',
        'strengthening_needs',
        // m. Habeas Data
        'habeas_data_accepted',
        'habeas_data_accepted_at',
        'habeas_data_manager_id',
        // Evidencias
        'commerce_evidence_path',
        'population_evidence_path',
        'photo_evidence_path',
        // legacy
        'employees_generated',
    ];

    protected $casts = [
        'clients'                      => 'array',
        'promotion_strategies'         => 'array',
        'digital_tools'                => 'array',
        'strengthening_needs'          => 'array',
        'has_accounting_records'       => 'boolean',
        'has_commercial_registration'  => 'boolean',
        'has_business_bank_account'    => 'boolean',
        'has_active_credits'           => 'boolean',
        'has_family_employees'         => 'boolean',
        'hires_women'                  => 'boolean',
        'has_innovation'               => 'boolean',
        'habeas_data_accepted'         => 'boolean',
        'habeas_data_accepted_at'      => 'datetime',

        'family_in_drummond'           => 'boolean',
        'mercantile_registration_expiry' => 'date',
        'commerce_evidence_path'       => 'array',
        'population_evidence_path'     => 'array',
        'photo_evidence_path'          => 'array',
    ];

    /**
     * Boot the model and register events
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new YearColumnScope('created_at'));

        // Evento antes de actualizar
        static::updating(function ($characterization) {
            $original = $characterization->getOriginal();
            $fileFields = ['commerce_evidence_path', 'population_evidence_path', 'photo_evidence_path'];

            foreach ($fileFields as $field) {
                if ($characterization->isDirty($field) && !empty($original[$field])) {
                    static::deleteFiles($original[$field]);
                }
            }
        });

        // Evento al eliminar (soft delete)
        static::deleted(function ($characterization) {
            static::deleteAllFiles($characterization);
        });

        // Evento al eliminar permanentemente
        static::forceDeleted(function ($characterization) {
            static::deleteAllFiles($characterization);
        });
    }

    /**
     * Eliminar todos los archivos del modelo
     */
    private static function deleteAllFiles($characterization)
    {
        $fileFields = ['commerce_evidence_path', 'population_evidence_path', 'photo_evidence_path'];

        foreach ($fileFields as $field) {
            if (!empty($characterization->$field)) {
                static::deleteFiles($characterization->$field);
            }
        }
    }

    /**
     * Eliminar archivos del storage
     */
    private static function deleteFiles($files)
    {
        if (is_string($files)) {
            $files = json_decode($files, true) ?? [$files];
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!empty($file) && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }
        }
    }

    // Relations
    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class, 'entrepreneur_id')->withTrashed();
    }

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class, 'economic_activity_id');
    }

    public function population()
    {
        return $this->belongsTo(Population::class, 'population_id');
    }

    // Helpful static option lists for forms / tables
    public static function businessTypes(): array
    {
        return [
            'individual'  => 'Individual',
            'associative' => 'Associative',
        ];
    }

    public static function businessAges(): array
    {
        return [
            'over_6_months'  => 'More than 6 months',
            'over_12_months' => 'More than 12 months',
            'over_24_months' => 'More than 24 months',
        ];
    }

    public static function clientsOptions(): array
    {
        return [
            'community'        => 'Community in general',
            'public_entities'  => 'Public entities',
            'private_entities' => 'Private entities',
            'schools'          => 'Schools',
            'hospitals'        => 'Hospitals',
        ];
    }

    public static function averageMonthlySalesOptions(): array
    {
        return [
            'lt_500000' => 'Less than 500,000',
            '500k_1m'   => '501,000 — 1,000,000',
            '1m_2m'     => '1,001,000 — 2,000,000',
            '2m_5m'     => '2,001,000 — 5,000,000',
            'gt_5m'     => 'More than 5,001,000',
        ];
    }

    public static function promotionStrategiesOptions(): array
    {
        return [
            'word_of_mouth' => 'Word of Mouth',
            'whatsapp'      => 'WhatsApp',
            'facebook'      => 'Facebook',
            'instagram'     => 'Instagram',
        ];
    }

    public static function employeesGeneratedOptions(): array
    {
        return [
            'up_to_2'       => 'Up to 2 employees',
            '3_to_4'        => '3 to 4 employees',
            'more_than_5'   => 'More than 5 employees',
        ];
    }
}
