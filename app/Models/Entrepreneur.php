<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Entrepreneur extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, SoftDeletes, TracksUpdatedBy;

    protected $table = 'entrepreneurs';

    protected $guard = 'entrepreneur';

    protected $fillable = [
        'status',
        'document_type_id',
        'document_number',
        'full_name',
        'gender_id',
        'marital_status_id',
        'birth_date',
        'phone',
        'phone_2',
        'address',
        'email',
        'city_id',
        'education_level_id',
        'population_id',
        'state_id',
        'manager_id',
        'project_id',
        'service',
        'admission_date',
        'cohort_id',
        'user_id',
        'traffic_light',
        'password',
        'updated_by_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        'birth_date' => 'date',
        'admission_date' => 'date',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

    /**
     * Método REQUERIDO por HasName interface
     * Este es el que Filament usa internamente
     */
    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->email ?? 'Emprendedor';
    }

    /**
     * Verificar acceso al panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->status;
    }

    /**
     * OPCIONAL: Avatar personalizado
     */
    public function getFilamentAvatarUrl(): ?string
    {
        $name = $this->getFilamentName();

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    // ========== RELACIONES ==========

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function population()
    {
        return $this->belongsTo(Population::class);
    }

    public function entrepreneurshipStage()
    {
        return $this->belongsTo(EntrepreneurshipStage::class);
    }

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    public function productiveLine()
    {
        return $this->belongsTo(ProductiveLine::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function visits()
    {
        return $this->hasMany(\App\Models\Visit::class);
    }

    public function pqrfs()
    {
        return $this->hasMany(Pqrf::class, 'entrepreneur_id');
    }

    public function characterizations()
    {
        return $this->hasMany(\App\Models\Characterization::class);
    }

    public function businessDiagnoses()
    {
        return $this->hasMany(\App\Models\BusinessDiagnosis::class);
    }

    public function businessDiagnosis()
    {
        return $this->hasOne(BusinessDiagnosis::class);
    }

    /**
     * Relación con las participaciones en capacitaciones
     */
    public function trainingParticipations(): HasMany
    {
        return $this->hasMany(TrainingParticipation::class);
    }

    /**
     * Relación con las capacitaciones en las que ha participado (many-to-many a través de participations)
     */
    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(
            Training::class,
            'training_participations',
            'entrepreneur_id',
            'training_id'
        )->withTimestamps()->withTrashed();
    }

    /**
     * Contar capacitaciones en las que ha participado
     */
    public function getTrainingsCountAttribute(): int
    {
        return $this->trainingParticipations()->count();
    }

    public function businessPlans()
    {
        return $this->hasMany(BusinessPlan::class);
    }

    public function businessPlan()
    {
        return $this->hasOne(BusinessPlan::class);
    }

    public function businessCanvas(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\BusinessCanvas::class);
    }

    /**
     * Relación con las evaluaciones de ferias
     */
    public function fairEvaluations(): HasMany
    {
        return $this->hasMany(\App\Models\FairEvaluation::class);
    }

    /**
     * Determina la ruta del emprendedor según su último diagnóstico.
     * Retorna 'route_1', 'route_2', 'route_3' o null si no tiene diagnóstico.
     */
    public function getRoute(): ?string
    {
        $diagnosis = $this->businessDiagnoses()->latest()->first();

        if (! $diagnosis || $diagnosis->total_score === null) {
            return null;
        }

        $year   = $diagnosis->created_at?->year ?? now()->year;
        $phases = \App\Support\MaturityScale::getPhaseRanges($year);
        $score  = (int) $diagnosis->total_score;

        foreach ($phases as $phase => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return 'route_' . $phase;
            }
        }

        return null;
    }

    /**
     * Retorna los IDs de emprendedores cuyo último diagnóstico los ubica en las rutas indicadas.
     * Ejemplo: Entrepreneur::getIdsByRoute(['route_1']) para Ruta 1.
     */
    public static function getIdsByRoute(array|string $routes): array
    {
        $routes = (array) $routes;

        $latestDiagnoses = \Illuminate\Support\Facades\DB::table('business_diagnoses')
            ->select('entrepreneur_id', 'total_score', \Illuminate\Support\Facades\DB::raw('YEAR(created_at) as diag_year'))
            ->whereIn('id', function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw('MAX(id)'))
                    ->from('business_diagnoses')
                    ->whereNull('deleted_at')
                    ->groupBy('entrepreneur_id');
            })
            ->whereNotNull('total_score')
            ->get();

        return $latestDiagnoses
            ->filter(function ($d) use ($routes) {
                $year   = (int) $d->diag_year;
                $score  = (int) $d->total_score;
                $phases = \App\Support\MaturityScale::getPhaseRanges($year);

                foreach ($phases as $phase => $range) {
                    if ($score >= $range['min'] && $score <= $range['max']) {
                        return in_array('route_' . $phase, $routes);
                    }
                }
                return false;
            })
            ->pluck('entrepreneur_id')
            ->toArray();
    }
}
