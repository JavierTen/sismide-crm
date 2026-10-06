<?php

namespace App\Models;

use App\Support\ActivityContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Acción registrada. Extiende el modelo del paquete para ligar cada acción a la
 * sesión, el panel y la IP desde los que se hizo.
 */
class Activity extends SpatieActivity
{
    public const EVENT_LABELS = [
        'created'            => 'Creó',
        'updated'            => 'Editó',
        'deleted'            => 'Deshabilitó',
        'restored'           => 'Restauró',
        'force_deleted'      => 'Eliminó definitivamente',
        'exported'           => 'Exportó',
        'attendance_updated' => 'Actualizó asistencia',
        'evaluation_deleted' => 'Eliminó evaluación',
        'cascade_deleted'    => 'Deshabilitó en cascada',
        'cascade_restored'   => 'Restauró en cascada',
        'relation_updated'   => 'Actualizó asignación',
        'password_changed'   => 'Cambió la contraseña',
        'access_granted'     => 'Creó acceso',
        'access_revoked'     => 'Eliminó acceso',
    ];

    public const EVENT_COLORS = [
        'created'            => 'success',
        'updated'            => 'info',
        'deleted'            => 'warning',
        'restored'           => 'gray',
        'force_deleted'      => 'danger',
        'exported'           => 'primary',
        'attendance_updated' => 'info',
        'evaluation_deleted' => 'danger',
        'cascade_deleted'    => 'warning',
        'cascade_restored'   => 'gray',
        'relation_updated'   => 'info',
        'password_changed'   => 'warning',
        'access_granted'     => 'success',
        'access_revoked'     => 'danger',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $activity->login_log_id ??= ActivityContext::loginLogId();
            $activity->panel        ??= ActivityContext::panel();
            $activity->ip_address   ??= request()->ip();
            $activity->entrepreneur_id ??= static::resolveEntrepreneurId($activity);
        });
    }

    public function loginLog(): BelongsTo
    {
        return $this->belongsTo(LoginLog::class);
    }

    /** Sin global scopes: el emprendedor puede ser de otro año o estar deshabilitado. */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class)->withoutGlobalScopes();
    }

    /**
     * Emprendedor al que se refiere la acción, si se refiere a alguno.
     *
     * Se lee del registro afectado y, si ya no existe (eliminado definitivo),
     * de la copia de sus campos que quedó en la propia actividad.
     */
    public static function resolveEntrepreneurId(self $activity): ?int
    {
        if (! $activity->subject_type) {
            return null;
        }

        if ($activity->subject_type === Entrepreneur::class) {
            return $activity->subject_id ? (int) $activity->subject_id : null;
        }

        $fields = array_merge(
            (array) ($activity->properties['old'] ?? []),
            (array) ($activity->properties['attributes'] ?? []),
            $activity->subject?->getAttributes() ?? [],
        );

        if (filled($fields['entrepreneur_id'] ?? null)) {
            return (int) $fields['entrepreneur_id'];
        }

        // Relaciones indirectas: el emprendedor está un nivel más arriba.
        if ($activity->subject_type === BusinessPlanEvaluation::class && filled($fields['business_plan_id'] ?? null)) {
            $id = BusinessPlan::withoutGlobalScopes()->whereKey($fields['business_plan_id'])->value('entrepreneur_id');

            return $id ? (int) $id : null;
        }

        return null;
    }

    /**
     * Nombre del emprendedor relacionado, solo cuando no es el propio registro
     * afectado (en ese caso ya aparece como "Registro").
     */
    public function getRelatedEntrepreneurNameAttribute(): ?string
    {
        if (! $this->entrepreneur_id || $this->subject_type === Entrepreneur::class) {
            return null;
        }

        return $this->entrepreneur?->getFilamentName() ?? 'Emprendedor #'.$this->entrepreneur_id;
    }

    /**
     * Sin global scopes: varios modelos filtran por año (YearColumnScope) y el
     * historial debe poder mostrar autores y registros de cualquier año, además
     * de los deshabilitados.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * Nombre del autor. Si el usuario ya no existe se recurre a la copia que
     * guardó el registro de sesiones al iniciar sesión.
     */
    public function getCauserNameAttribute(): string
    {
        $causer = $this->causer;

        if ($causer) {
            return method_exists($causer, 'getFilamentName')
                ? $causer->getFilamentName()
                : ($causer->name ?? '#'.$causer->getKey());
        }

        return $this->loginLog?->user_name ?? ($this->causer_id ? 'Usuario eliminado #'.$this->causer_id : 'Sistema');
    }

    /**
     * Nombre legible del registro afectado. Si se eliminó definitivamente, se
     * toma de la copia que quedó en el propio registro de actividad.
     */
    public function getSubjectNameAttribute(): string
    {
        $source = $this->subject?->getAttributes()
            ?? (array) ($this->properties['old'] ?? $this->properties['attributes'] ?? []);

        foreach (['display_name', 'full_name', 'name', 'business_name', 'document_number'] as $field) {
            if (filled($source[$field] ?? null)) {
                return (string) $source[$field];
            }
        }

        if ($this->subject && filled($this->subject->display_name ?? null)) {
            return (string) $this->subject->display_name;
        }

        return $this->subject_id ? '#'.$this->subject_id : '—';
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event] ?? ($this->event ?? '—');
    }

    /**
     * Cambios campo por campo. En una edición trae el valor anterior y el
     * nuevo; al crear solo el nuevo y al borrar solo el que había.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChangesListAttribute(): array
    {
        $new = (array) ($this->properties['attributes'] ?? []);
        $old = (array) ($this->properties['old'] ?? []);

        $fields = array_unique([...array_keys($new), ...array_keys($old)]);

        $changes = [];

        foreach ($fields as $field) {
            $changes[$field] = [
                'old' => $old[$field] ?? null,
                'new' => $new[$field] ?? null,
            ];
        }

        return $changes;
    }
}
