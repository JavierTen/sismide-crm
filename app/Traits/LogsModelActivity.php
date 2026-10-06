<?php

namespace App\Traits;

use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Registra en el historial de actividad las altas, ediciones, deshabilitaciones,
 * restauraciones y eliminaciones del modelo.
 *
 * Solo se guardan los campos que cambiaron. Se excluyen las marcas de tiempo y
 * `updated_by_id`, que cambian en cada guardado sin aportar nada, y los datos
 * de autenticación, que nunca deben quedar en un registro legible.
 */
trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('model')
            ->logAll()
            ->logExcept([
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'deleted_at',
                'updated_by_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * El paquete registra igual el borrado suave y el definitivo. Aquí se
     * distinguen, porque para el sistema son acciones de gravedad muy distinta.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($eventName === 'deleted' && method_exists($this, 'isForceDeleting') && $this->isForceDeleting()) {
            $activity->event = 'force_deleted';
        }

        // El paquete también llama a este método en las acciones registradas a
        // mano sobre el modelo (exportaciones, asistencia), que traen su propia
        // descripción: solo se reemplaza en los eventos de ciclo de vida.
        if (in_array($activity->event, ['created', 'updated', 'deleted', 'restored', 'force_deleted'], true)) {
            $activity->description = \App\Models\Activity::EVENT_LABELS[$activity->event];
        }
    }
}
