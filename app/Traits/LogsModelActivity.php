<?php

namespace App\Traits;

use App\Models\Entrepreneur;
use App\Support\AuditTrail;
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
    use LogsActivity {
        attributeValuesToBeLogged as protected spatieAttributeValuesToBeLogged;
    }

    private const PASSWORD_SET   = 'definida';
    private const PASSWORD_UNSET = 'sin contraseña';

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
     * La contraseña está excluida para que nunca se guarde, ni cifrada. Pero si
     * solo se excluyera, cambiarla no dejaría rastro: se registra en su lugar
     * si quedó definida o no, sin su valor.
     */
    public function attributeValuesToBeLogged(string $processingEvent): array
    {
        $properties = $this->spatieAttributeValuesToBeLogged($processingEvent);

        if (! array_key_exists('password', $this->getAttributes())) {
            return $properties;
        }

        $describe = fn ($value): string => filled($value) ? self::PASSWORD_SET : self::PASSWORD_UNSET;

        if ($processingEvent === 'created' && filled($this->getAttribute('password'))) {
            $properties['attributes']['password'] = self::PASSWORD_SET;
        }

        if ($processingEvent === 'updated' && ($this->isDirty('password') || $this->wasChanged('password'))) {
            $properties['attributes']['password'] = $describe($this->getAttribute('password'));
            $properties['old']['password']        = $describe($this->getOriginal('password'));
        }

        return $properties;
    }

    /**
     * El paquete registra igual el borrado suave y el definitivo. Aquí se
     * distinguen, porque para el sistema son acciones de gravedad muy distinta.
     * También se da nombre propio a los cambios que solo tocan la contraseña.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($eventName === 'deleted' && method_exists($this, 'isForceDeleting') && $this->isForceDeleting()) {
            $activity->event = 'force_deleted';
        }

        if ($eventName === 'updated') {
            $changed = array_keys((array) ($activity->properties['attributes'] ?? []));

            if ($changed === ['password']) {
                $before = $activity->properties['old']['password'] ?? null;
                $after  = $activity->properties['attributes']['password'] ?? null;

                // En emprendedores, tener o no contraseña es tener o no acceso.
                $activity->event = match (true) {
                    $this instanceof Entrepreneur && $before === self::PASSWORD_UNSET && $after === self::PASSWORD_SET => 'access_granted',
                    $this instanceof Entrepreneur && $after === self::PASSWORD_UNSET                                   => 'access_revoked',
                    default                                                                                             => 'password_changed',
                };
            }
        }

        // El paquete también llama a este método en las acciones registradas a
        // mano sobre el modelo (exportaciones, asistencia), que traen su propia
        // descripción: solo se reemplaza en los eventos de ciclo de vida.
        if (in_array($activity->event, ['created', 'updated', 'deleted', 'restored', 'force_deleted'], true)) {
            $activity->description = \App\Models\Activity::EVENT_LABELS[$activity->event];
        }

        // Las pantallas pueden precisar el motivo del cambio de contraseña
        // ("Reenvió credenciales"); si no lo hacen, se usa el nombre genérico.
        if (in_array($activity->event, ['password_changed', 'access_granted', 'access_revoked'], true)) {
            $activity->description = request()->attributes->get(AuditTrail::CONTEXT_ATTRIBUTE)
                ?? \App\Models\Activity::EVENT_LABELS[$activity->event];
        }
    }
}
