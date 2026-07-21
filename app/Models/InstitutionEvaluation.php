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
        'technical_verdict',
        'technical_conditions',
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

            // Marcar internamente si no cumple capacidad operativa mínima
            if (is_array($evaluation->operational_capacity_section)) {
                $capacity = $evaluation->operational_capacity_section;
                $capacity['meets_minimum_capacity'] =
                    ($capacity['can_link_min_students'] ?? null) !== 'no' &&
                    ($capacity['can_link_min_teachers'] ?? null) !== 'no';
                $evaluation->operational_capacity_section = $capacity;
            }

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

    // ── Lógica de desempate ──────────────────────────────────────────────────

    public function getTiebreakerScores(): array
    {
        $ped = $this->pedagogical_section              ?? [];
        $sus = $this->sustainability_section           ?? [];
        $cul = $this->entrepreneurial_culture_section  ?? [];
        $ter = $this->territorial_impact_section       ?? [];

        return [
            static::pedagogicalScoring()['pei_articulation'][$ped['pei_articulation'] ?? '']                         ?? 0,
            static::sustainabilityScoring()['continuity_strategy'][$sus['continuity_strategy'] ?? '']                 ?? 0,
            static::entrepreneurialCultureScoring()['business_fairs'][$cul['business_fairs'] ?? '']                   ?? 0,
            static::entrepreneurialCultureScoring()['external_fairs_participation'][$cul['external_fairs_participation'] ?? ''] ?? 0,
            static::territorialImpactScoring()['railway_corridor_influence'][$ter['railway_corridor_influence'] ?? ''] ?? 0,
        ];
    }

    /**
     * Devuelve todas las evaluaciones no eliminadas ordenadas por puntaje total
     * y luego por los 5 criterios de desempate (mayor a menor).
     */
    public static function sortedByRanking(): \Illuminate\Support\Collection
    {
        return static::whereNull('deleted_at')
            ->get()
            ->sort(function (self $a, self $b) {
                if ($a->total_score !== $b->total_score) {
                    return ($b->total_score ?? 0) <=> ($a->total_score ?? 0);
                }
                foreach (array_map(null, $a->getTiebreakerScores(), $b->getTiebreakerScores()) as [$aScore, $bScore]) {
                    if ($aScore !== $bScore) {
                        return $bScore <=> $aScore;
                    }
                }
                return 0;
            })
            ->values();
    }

    /**
     * Devuelve la posición de esta evaluación y si requiere decisión del comité.
     * ['rank' => 1, 'is_committee' => false, 'display' => '1']
     * ['rank' => 2, 'is_committee' => true,  'display' => 'Comité']
     */
    public function getRankingInfo(): array
    {
        $sorted  = static::sortedByRanking();
        $myKey   = array_merge([$this->total_score ?? 0], $this->getTiebreakerScores());

        // Cuántas evaluaciones tienen estrictamente mejor perfil que ésta
        $betterCount = $sorted->filter(function (self $e) use ($myKey) {
            if ($e->id === $this->id) {
                return false;
            }
            $eKey = array_merge([$e->total_score ?? 0], $e->getTiebreakerScores());
            foreach (array_map(null, $eKey, $myKey) as [$eScore, $myScore]) {
                if ($eScore > $myScore) return true;
                if ($eScore < $myScore) return false;
            }
            return false; // empate completo, no "mejor"
        })->count();

        $rank = $betterCount + 1;

        // ¿Hay otra evaluación con exactamente el mismo perfil?
        $isCommittee = $sorted->filter(function (self $e) use ($myKey) {
            if ($e->id === $this->id) return false;
            $eKey = array_merge([$e->total_score ?? 0], $e->getTiebreakerScores());
            return $eKey === $myKey;
        })->isNotEmpty();

        return [
            'rank'         => $rank,
            'is_committee' => $isCommittee,
            'display'      => $isCommittee ? 'Comité' : (string) $rank,
        ];
    }

    // ── Scoring ──────────────────────────────────────────────────────────────

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
