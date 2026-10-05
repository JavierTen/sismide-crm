<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EjePanelAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->can('accessEjePanel')) {
            // Se marca antes del logout para que el registro de sesiones lo
            // distinga de un cierre voluntario.
            $request->attributes->set(\App\Listeners\RecordLoginActivity::FORCED_LOGOUT_ATTRIBUTE, true);

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.eje.auth.login')
                ->with('status', 'No tienes permiso para acceder a este panel.');
        }

        return $next($request);
    }
}
