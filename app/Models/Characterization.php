<?php

namespace App\Models;

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
        'business_type',
        'business_age',
        'clients',
        'promotion_strategies',
        'average_monthly_sales',
        'employees_generated',
        'has_accounting_records',
        'has_commercial_registration',
        'family_in_drummond',
        'latitude',
        'longitude',
        'commerce_evidence_path',
        'population_evidence_path',
        'photo_evidence_path',
        'characterization_date'
    ];

    protected $casts = [
        'clients' => 'array',
        'promotion_strategies' => 'array',
        'has_accounting_records' => 'boolean',
        'has_commercial_registration' => 'boolean',
        'family_in_drummond' => 'boolean',
        'commerce_evidence_path' => 'array',
        'population_evidence_path' => 'array',
        'photo_evidence_path' => 'array',
    ];

    /**
     * Boot the model and register events
     */
    protected static function boot()
    {
        parent::boot();

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
