<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Throwable;

/**
 * Registra inicios y cierres de sesión de todos los paneles.
 *
 * El inicio y el cierre se enlazan guardando el id del registro en la propia
 * sesión, no por `session_id`: Filament regenera el id de sesión justo después
 * de autenticar, así que el que existe durante el evento Login ya no sirve
 * para reconocer la sesión después. Los datos de sesión, en cambio, sí
 * sobreviven a esa regeneración.
 *
 * Un fallo al auditar nunca debe impedir que alguien entre o salga, por eso
 * cada registro va protegido y solo se informa al log.
 */
class RecordLoginActivity
{
    /** Atributo de petición con el que se marca un cierre forzado. */
    public const FORCED_LOGOUT_ATTRIBUTE = 'login_log_forced_logout';

    public function handleLogin(Login $event): void
    {
        try {
            $log = LoginLog::create([
                'authenticatable_type' => $event->user instanceof Model ? $event->user->getMorphClass() : null,
                'authenticatable_id'   => $event->user->getAuthIdentifier(),
                'user_name'            => $this->displayName($event->user),
                'user_email'           => $event->user->email ?? null,
                'user_roles'           => $this->roleNames($event->user),
                'panel'                => $this->resolvePanel($event->guard),
                'guard'                => $event->guard,
                'via_remember'         => $event->remember,
                'ip_address'           => request()->ip(),
                'user_agent'           => mb_substr((string) request()->userAgent(), 0, 1000),
                'login_at'             => now(),
                'last_activity_at'     => now(),
            ]);

            if (request()->hasSession()) {
                request()->session()->put(LoginLog::SESSION_KEY.'.'.$event->guard, $log->getKey());
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function handleLogout(Logout $event): void
    {
        try {
            if (! request()->hasSession()) {
                return;
            }

            $key   = LoginLog::SESSION_KEY.'.'.$event->guard;
            $logId = request()->session()->get($key);

            if (! $logId) {
                return;
            }

            LoginLog::whereKey($logId)
                ->whereNull('logout_at')
                ->update([
                    'logout_at'        => now(),
                    'last_activity_at' => now(),
                    'logout_reason'    => request()->attributes->get(self::FORCED_LOGOUT_ATTRIBUTE)
                        ? LoginLog::STATUS_FORCED
                        : LoginLog::STATUS_MANUAL,
                ]);

            request()->session()->forget($key);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class  => 'handleLogin',
            Logout::class => 'handleLogout',
        ];
    }

    /**
     * El login de Filament corre en una petición Livewire, no en la URL del
     * panel, pero Filament restaura el panel activo en esas peticiones. Si aun
     * así no lo hay, el guard del Emprendedor es inequívoco.
     */
    private function resolvePanel(string $guard): ?string
    {
        $panel = Filament::getCurrentPanel()?->getId();

        if ($panel) {
            return $panel;
        }

        return $guard === 'entrepreneur' ? 'emprendedor' : null;
    }

    /**
     * Roles con los que entra. El Emprendedor no usa roles de Spatie: se le
     * asigna uno fijo para que el historial siga siendo homogéneo.
     *
     * @return array<int, string>
     */
    private function roleNames(Authenticatable $user): array
    {
        if ($user instanceof \App\Models\Entrepreneur) {
            return [LoginLog::ENTREPRENEUR_ROLE];
        }

        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->values()->all();
        }

        return [];
    }

    private function displayName(Authenticatable $user): ?string
    {
        if (method_exists($user, 'getFilamentName')) {
            return $user->getFilamentName();
        }

        return $user->name ?? null;
    }
}
