<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFairParticipation extends Model
{
    use SoftDeletes, TracksUpdatedBy;

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

    protected $fillable = [
        'student_fair_id',
        'educational_institution_id',
        'participation_date',

        'generated_articulations',
        'articulation_actor_types',
        'articulations_count',

        'identified_chain_opportunity',
        'chain_link',
        'chain_actor_types',

        'had_sales',
        'sales_range',
        'exact_sales_amount',
        'sales_balance',

        'organization_rating',
        'visitor_flow',
        'generated_contacts',

        'description',
        'attendance_list_path',
        'photos',

        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'participation_date'           => 'date',
        'generated_articulations'      => 'boolean',
        'articulation_actor_types'     => 'array',
        'identified_chain_opportunity' => 'boolean',
        'chain_actor_types'            => 'array',
        'had_sales'                    => 'boolean',
        'exact_sales_amount'           => 'decimal:2',
        'generated_contacts'           => 'boolean',
        'photos'                       => 'array',
    ];

    public function fair(): BelongsTo
    {
        return $this->belongsTo(StudentFair::class, 'student_fair_id');
    }

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_fair_participation_student');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'student_fair_participation_teacher');
    }

    public function actors(): BelongsToMany
    {
        return $this->belongsToMany(Actor::class, 'student_fair_participation_actor');
    }
}
