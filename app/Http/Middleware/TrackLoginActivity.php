<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mantiene al día la última actividad de la sesión.
 *
 * Es lo que permite deducir cuándo terminó una sesión que expiró o cuyo
 * navegador se cerró, casos en los que Laravel no emite ningún evento. Para no
 * escribir en cada clic, actualiza como mucho una vez por minuto.
 */
class TrackLoginActivity
{
    private const THROTTLE_SECONDS = 60;

    private const TOUCHED_KEY = 'login_log_touched_at';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->touch($request);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function touch(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $guard = Filament::getCurrentPanel()?->getAuthGuard();

        if (! $guard || ! auth()->guard($guard)->check()) {
            return;
        }

        $session = $request->session();
        $logId   = $session->get(LoginLog::SESSION_KEY.'.'.$guard);

        if (! $logId) {
            return;
        }

        $touchedKey  = self::TOUCHED_KEY.'.'.$guard;
        $lastTouched = (int) $session->get($touchedKey, 0);

        if (time() - $lastTouched < self::THROTTLE_SECONDS) {
            return;
        }

        LoginLog::whereKey($logId)
            ->whereNull('logout_at')
            ->update(['last_activity_at' => now()]);

        $session->put($touchedKey, time());
    }
}
