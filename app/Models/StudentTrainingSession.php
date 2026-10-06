<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use App\Traits\TracksUpdatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class StudentTrainingSession extends Model
{
    use SoftDeletes, TracksUpdatedBy, LogsModelActivity;

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

        $before = $this->attendees()->pluck('students.id')->map(fn ($id) => (int) $id)->all();

        $this->students()->sync($payload);

        $this->logAttendanceChange($before, $payload);
    }

    /**
     * La asistencia vive en la tabla pivote y `sync()` no dispara eventos de
     * modelo, así que el historial de actividad no la vería. Se registra aquí,
     * solo cuando algo cambió, con quién quedó marcado y desmarcado.
     *
     * @param  array<int>  $before
     * @param  array<int|string, array{attended: bool}>  $payload
     */
    private function logAttendanceChange(array $before, array $payload): void
    {
        $after = array_map('intval', array_keys(array_filter($payload, fn ($row) => $row['attended'])));

        $marked   = array_values(array_diff($after, $before));
        $unmarked = array_values(array_diff($before, $after));

        if ($marked === [] && $unmarked === []) {
            return;
        }

        $names = Student::withoutGlobalScopes()
            ->whereIn('id', [...$marked, ...$unmarked])
            ->pluck('name', 'id');

        $summoned  = count($payload);
        $attendees = count($after);

        activity('attendance')
            ->performedOn($this)
            ->event('attendance_updated')
            ->withProperties([
                'summoned'  => $summoned,
                'attendees' => $attendees,
                'marked'    => collect($marked)->map(fn ($id) => $names[$id] ?? "#{$id}")->values()->all(),
                'unmarked'  => collect($unmarked)->map(fn ($id) => $names[$id] ?? "#{$id}")->values()->all(),
            ])
            ->log(sprintf(
                'Actualizó la asistencia: %d de %d asistentes (+%d / -%d)',
                $attendees,
                $summoned,
                count($marked),
                count($unmarked),
            ));
    }
}
