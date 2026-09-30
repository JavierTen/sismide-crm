<?php

namespace App\Support;

use App\Models\EducationalInstitution;
use App\Models\Student;
use App\Models\StudentTraining;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alertas de control de calidad del panel EJE.
 *
 * Cada alerta define una única consulta: de ahí salen tanto el número de casos
 * como el listado del detalle, así que nunca pueden discrepar.
 *
 * Se omiten a propósito cuatro alertas del documento original (autorizaciones
 * faltantes, capacitación sin evidencias, feria sin cierre documental y
 * capacitación con asistencia sin diligenciar): los formularios ya exigen esos
 * soportes como obligatorios, de modo que el caso no puede producirse y la
 * alerta sería siempre cero.
 */
class EjeAlerts
{
    /** Porcentaje por debajo del cual se considera baja asistencia. */
    public const LOW_ATTENDANCE_THRESHOLD = 20;

    /**
     * @return array<string, array{label: string, description: string, icon: string, filterable: bool}>
     */
    public static function definitions(): array
    {
        return [
            'students_without_characterization' => [
                'label'       => 'Estudiantes sin caracterización',
                'description' => 'Estudiantes registrados que aún no tienen una caracterización diligenciada.',
                'icon'        => 'heroicon-o-clipboard-document-list',
                'filterable'  => true,
            ],
            'students_without_canvas' => [
                'label'       => 'Estudiantes sin Modelo Canvas',
                'description' => 'Estudiantes registrados sin el Modelo Canvas de su iniciativa.',
                'icon'        => 'heroicon-o-rectangle-group',
                'filterable'  => true,
            ],
            'students_low_attendance' => [
                'label'       => 'Estudiantes con baja asistencia',
                'description' => 'Asistieron a menos del '.self::LOW_ATTENDANCE_THRESHOLD.'% de las sesiones dictadas en su institución.',
                'icon'        => 'heroicon-o-user-minus',
                'filterable'  => true,
            ],
            'institutions_without_evaluation' => [
                'label'       => 'Instituciones sin evaluación',
                'description' => 'Instituciones educativas sin ninguna evaluación registrada.',
                'icon'        => 'heroicon-o-building-library',
                'filterable'  => true,
            ],
            'trainings_without_sessions' => [
                'label'       => 'Capacitaciones sin sesiones',
                'description' => 'Capacitaciones creadas en las que aún no se ha registrado ninguna sesión ni su asistencia.',
                'icon'        => 'heroicon-o-academic-cap',
                // No cuelgan de una institución hasta tener sesiones, así que
                // los filtros de municipio e institución no les aplican.
                'filterable'  => false,
            ],
        ];
    }

    /**
     * Resumen para las tarjetas. Las alertas en cero se omiten.
     *
     * @param  array{city_id?: int|string|null, educational_institution_id?: int|string|null}  $filters
     * @return array<int, array{key: string, label: string, description: string, icon: string, count: int, filtered: bool}>
     */
    public static function summary(array $filters = []): array
    {
        $summary = [];

        foreach (self::definitions() as $key => $definition) {
            $count = self::query($key, $filters)->count();

            if ($count === 0) {
                continue;
            }

            $summary[] = [
                'key'         => $key,
                'label'       => $definition['label'],
                'description' => $definition['description'],
                'icon'        => $definition['icon'],
                'count'       => $count,
                'filtered'    => $definition['filterable'] && self::hasFilters($filters),
            ];
        }

        return $summary;
    }

    /**
     * @param  array{city_id?: int|string|null, educational_institution_id?: int|string|null}  $filters
     */
    public static function query(string $key, array $filters = []): Builder
    {
        return match ($key) {
            'students_without_characterization' => self::scopeStudents(
                Student::query()->whereDoesntHave('characterizations'),
                $filters,
            ),
            'students_without_canvas' => self::scopeStudents(
                Student::query()->whereDoesntHave('studentCanvas'),
                $filters,
            ),
            'students_low_attendance' => self::scopeStudents(
                self::lowAttendanceQuery(),
                $filters,
            ),
            'institutions_without_evaluation' => self::scopeInstitutions(
                EducationalInstitution::query()->whereDoesntHave('evaluations'),
                $filters,
            ),
            'trainings_without_sessions' => self::scopeOwnership(
                StudentTraining::query()->whereDoesntHave('sessions'),
            ),
            default => throw new \InvalidArgumentException("Alerta desconocida: {$key}"),
        };
    }

    public static function label(string $key): string
    {
        return self::definitions()[$key]['label'] ?? $key;
    }

    /**
     * Estudiantes que asistieron a menos del umbral de las sesiones dictadas en
     * su institución. El denominador son las sesiones del colegio, no las filas
     * de la pivote: la alerta busca justo a quien no fue aunque su institución sí.
     *
     * Se excluye a quien no ha tenido ninguna sesión en su institución, porque
     * sin denominador no hay baja asistencia sino ausencia de datos.
     */
    private static function lowAttendanceQuery(): Builder
    {
        $sessionsAtInstitution = '(select count(*) from student_training_sessions s
            where s.educational_institution_id = students.educational_institution_id
              and s.deleted_at is null)';

        $attendedSessions = '(select count(*) from student_training_session_student sts
            inner join student_training_sessions s on s.id = sts.student_training_session_id
            where sts.student_id = students.id
              and sts.attended = 1
              and s.deleted_at is null)';

        return Student::query()
            ->selectRaw("students.*, {$sessionsAtInstitution} as sessions_total, {$attendedSessions} as sessions_attended")
            ->whereRaw("{$sessionsAtInstitution} > 0")
            // Se compara con multiplicación en vez de dividir, para no depender
            // del redondeo de la división entera de MySQL.
            ->whereRaw("{$attendedSessions} * 100 < ".self::LOW_ATTENDANCE_THRESHOLD." * {$sessionsAtInstitution}");
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function scopeStudents(Builder $query, array $filters): Builder
    {
        if (filled($filters['educational_institution_id'] ?? null)) {
            $query->where('students.educational_institution_id', $filters['educational_institution_id']);
        } elseif (filled($filters['city_id'] ?? null)) {
            $query->whereHas(
                'educationalInstitution',
                fn (Builder $institution) => $institution->where('city_id', $filters['city_id']),
            );
        }

        return self::scopeOwnership($query);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function scopeInstitutions(Builder $query, array $filters): Builder
    {
        if (filled($filters['educational_institution_id'] ?? null)) {
            $query->whereKey($filters['educational_institution_id']);
        } elseif (filled($filters['city_id'] ?? null)) {
            $query->where('city_id', $filters['city_id']);
        }

        return self::scopeOwnership($query);
    }

    /**
     * Mismo criterio que los widgets de indicadores: quien no es Admin ni
     * Viewer solo ve lo que registró.
     */
    private static function scopeOwnership(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && ! $user->hasRole(['Admin', 'Viewer'])) {
            $query->where($query->getModel()->getTable().'.manager_id', $user->getKey());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function hasFilters(array $filters): bool
    {
        return filled($filters['city_id'] ?? null)
            || filled($filters['educational_institution_id'] ?? null);
    }
}
