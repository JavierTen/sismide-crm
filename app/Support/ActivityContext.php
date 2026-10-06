<?php

namespace App\Support;

use App\Models\LoginLog;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Quién está actuando y en qué sesión, para el registro de actividad.
 *
 * spatie/laravel-activitylog toma el autor del guard por defecto (`web`), lo
 * que dejaría sin autor todo lo que hagan los emprendedores, que entran por el
 * guard `entrepreneur`. Aquí se usa el guard del panel en curso.
 */
class ActivityContext
{
    public static function guard(): string
    {
        return Filament::getCurrentPanel()?->getAuthGuard() ?? config('auth.defaults.guard', 'web');
    }

    public static function causer(): ?Model
    {
        $user = auth()->guard(static::guard())->user();

        if ($user instanceof Model) {
            return $user;
        }

        // Fuera de un panel (consola, colas) se intenta con cada guard.
        foreach (array_keys(config('auth.guards', [])) as $guard) {
            $user = auth()->guard($guard)->user();

            if ($user instanceof Model) {
                return $user;
            }
        }

        return null;
    }

    public static function loginLogId(): ?int
    {
        if (! request()->hasSession()) {
            return null;
        }

        $id = request()->session()->get(LoginLog::SESSION_KEY.'.'.static::guard());

        return $id ? (int) $id : null;
    }

    public static function panel(): ?string
    {
        return Filament::getCurrentPanel()?->getId();
    }
}
