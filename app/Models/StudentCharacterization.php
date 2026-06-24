<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentCharacterization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'zone',
        'main_interest',
        'main_interest_other',
        'life_project',
        'has_prior_experience',
        'prior_experience_type',
        'prior_experience_other',
        'participation_status',
        'program_join_date',
        'program_exit_date',
        'exit_reason',
        'exit_reason_other',
        'data_authorization',
        'data_authorization_file',
        'manager_id',
    ];

    protected $casts = [
        'has_prior_experience' => 'boolean',
        'data_authorization' => 'boolean',
        'program_join_date' => 'date',
        'program_exit_date' => 'date',
    ];

    public static function zoneOptions(): array
    {
        return [
            'urban' => 'Urbana',
            'rural' => 'Rural',
        ];
    }

    public static function mainInterestOptions(): array
    {
        return [
            'technology' => 'Tecnología',
            'agroindustry' => 'Agroindustria',
            'commerce' => 'Comercio',
            'services' => 'Servicios',
            'arts_culture' => 'Arte y Cultura',
            'other' => 'Otro',
        ];
    }

    public static function priorExperienceTypeOptions(): array
    {
        return [
            'family_business' => 'Negocio familiar',
            'own_venture' => 'Emprendimiento propio',
            'school_project' => 'Proyecto escolar de emprendimiento',
            'training_course' => 'Curso o capacitación en emprendimiento',
            'other' => 'Otro',
        ];
    }

    public static function participationStatusOptions(): array
    {
        return [
            'active' => 'Activo',
            'withdrawn' => 'Retirado',
            'completed' => 'Culminación del programa',
            'transferred' => 'Trasladado',
        ];
    }

    public static function exitReasonOptions(): array
    {
        return [
            'institution_change' => 'Cambio de institución',
            'family_reasons' => 'Razones familiares',
            'academic_performance' => 'Bajo rendimiento académico',
            'other' => 'Otro',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
