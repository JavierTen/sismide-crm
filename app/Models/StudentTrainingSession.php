<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StudentTrainingSession extends Model
{
    use SoftDeletes, TracksUpdatedBy;

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));

        // Los adjuntos sólo se borran del disco en el eliminado definitivo, para
        // que deshabilitar siga siendo reversible.
        static::deleting(function (self $session) {
            if (! $session->isForceDeleting()) {
                return;
            }

            foreach (array_filter([$session->attendance_list_path]) as $path) {
                Storage::disk('public')->delete($path);
            }

            foreach ($session->additional_material ?? [] as $path) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    protected $fillable = [
        'student_training_id',
        'educational_institution_id',
        'session_date',
        'start_time',
        'end_time',
        'intensity_hours',
        'methodology',
        'methodology_other',
        'activity',
        'result_rating',
        'result_detail',
        'observations',
        'commitments',
        'attendance_list_path',
        'additional_material',
        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'session_date'        => 'date',
        'intensity_hours'     => 'decimal:2',
        'commitments'         => 'array',
        'additional_material' => 'array',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(StudentTraining::class, 'student_training_id');
    }

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** Todos los estudiantes convocados, hayan asistido o no. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_training_session_student')
            ->withPivot('attended');
    }

    public function attendees(): BelongsToMany
    {
        return $this->students()->wherePivot('attended', true);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'student_training_session_teacher');
    }

    /**
     * Guarda la asistencia conservando el listado completo: escribe una fila
     * por cada estudiante registrado de la institución, con `attended` según
     * haya sido marcado o no.
     *
     * @param  array<int|string>  $attendedIds
     */
    public function syncAttendance(array $attendedIds): void
    {
        $attendedIds = array_map('intval', $attendedIds);

        $payload = Student::withoutTrashed()
            ->where('educational_institution_id', $this->educational_institution_id)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => ['attended' => in_array((int) $id, $attendedIds, true)]])
            ->all();

        $this->students()->sync($payload);
    }
}
