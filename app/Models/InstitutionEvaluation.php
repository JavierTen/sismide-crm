<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'educational_institution_id',
        'pedagogical_section',
        'sustainability_section',
        'entrepreneurial_culture_section',
        'territorial_impact_section',
        'operational_capacity_section',
        'technical_concept',
        'total_score',
        'result_category',
        'manager_id',
        'updated_by',
    ];

    protected $casts = [
        'pedagogical_section' => 'array',
        'sustainability_section' => 'array',
        'entrepreneurial_culture_section' => 'array',
        'territorial_impact_section' => 'array',
        'operational_capacity_section' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (InstitutionEvaluation $evaluation) {
            $evaluation->total_score = $evaluation->calculateTotalScore();
            $evaluation->result_category = $evaluation->calculateResultCategory();

            if (auth()->check()) {
                $evaluation->updated_by = auth()->id();
            }
        });
    }

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function pedagogicalScoring(): array
    {
        return [
            'pei_articulation' => ['full' => 25, 'partial' => 20, 'isolated' => 10, 'none' => 0],
            'active_area' => ['full' => 15, 'one_grade' => 10, 'occasional' => 5, 'none' => 0],
        ];
    }

    public static function sustainabilityScoring(): array
    {
        return [
            'continuity_strategy' => ['defined' => 15, 'concrete_actions' => 10, 'intention' => 5, 'none' => 0],
            'institutional_commitment' => ['letter_and_plan' => 10, 'letter' => 7, 'verbal' => 3, 'none' => 0],
            'institutional_availability' => ['full' => 5, 'partial' => 3, 'none' => 0],
        ];
    }

    public static function entrepreneurialCultureScoring(): array
    {
        return [
            'business_fairs' => ['annual' => 5, 'periodic' => 3, 'isolated' => 1, 'none' => 0],
            'external_fairs_participation' => ['frequent' => 5, 'occasional' => 3, 'none' => 0],
        ];
    }

    public static function territorialImpactScoring(): array
    {
        return [
            'railway_corridor_influence' => ['direct' => 10, 'indirect' => 7, 'low' => 2],
            'vulnerable_population' => ['high' => 10, 'medium' => 7, 'low' => 4],
        ];
    }

    public function calculateTotalScore(): int
    {
        return $this->calculateSectionScore($this->pedagogical_section, static::pedagogicalScoring())
            + $this->calculateSectionScore($this->sustainability_section, static::sustainabilityScoring())
            + $this->calculateSectionScore($this->entrepreneurial_culture_section, static::entrepreneurialCultureScoring())
            + $this->calculateSectionScore($this->territorial_impact_section, static::territorialImpactScoring());
    }

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

    public function calculateResultCategory(): string
    {
        $score = $this->total_score ?? 0;

        if ($score >= 70) {
            return 'Apta';
        }

        if ($score >= 40) {
            return 'Apta con condiciones';
        }

        return 'No apta';
    }
}
