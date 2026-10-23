<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Deja en el historial de acciones cada página a la que entra el usuario.
 *
 * Solo cuenta las cargas de página (no las interacciones Livewire dentro de
 * ella) y no repite la misma página si se recarga en menos de un minuto.
 * Un fallo al registrar nunca debe impedir ver la página.
 */
class TrackPageVisits
{
    private const REPEAT_SECONDS = 60;

    private const LAST_VISIT_KEY = 'activity_last_page_visit';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->expectsJson() && $response->isSuccessful()) {
            try {
                $this->log($request);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    private function log(Request $request): void
    {
        $page = $request->route()?->getControllerClass();

        if (! $page || (! is_subclass_of($page, Page::class) && ! is_subclass_of($page, ResourcePage::class))) {
            return;
        }

        if ($request->hasSession()) {
            [$lastPath, $lastTime] = $request->session()->get(self::LAST_VISIT_KEY, [null, 0]);

            if ($lastPath === $request->path() && time() - $lastTime < self::REPEAT_SECONDS) {
                return;
            }

            $request->session()->put(self::LAST_VISIT_KEY, [$request->path(), time()]);
        }

        $label  = $this->label($page);
        $record = $this->record($request, $page);

        $activity = activity('navigation')
            ->event('visited')
            ->withProperties(array_filter([
                'page' => $label,
                'url'  => $request->fullUrl(),
            ]));

        if ($record) {
            $activity->performedOn($record);
        }

        $activity->log('Ingresó a '.$label);
    }

    /**
     * Nombre de la página. Las páginas propias usan su título, que es más
     * completo que el del menú ("Indicadores de Capacitaciones" y no
     * "Indicadores"); las de un recurso, "Planes de negocio › Editar".
     */
    private function label(string $page): string
    {
        if (! is_subclass_of($page, ResourcePage::class)) {
            try {
                return (string) app($page)->getTitle();
            } catch (Throwable) {
                return $page::getNavigationLabel();
            }
        }

        $resource = $page::getResource();

        $action = match (true) {
            is_subclass_of($page, ListRecords::class)  => null,
            is_subclass_of($page, CreateRecord::class) => 'Crear',
            is_subclass_of($page, EditRecord::class)   => 'Editar',
            is_subclass_of($page, ViewRecord::class)   => 'Ver',
            default                                    => Str::headline(class_basename($page)),
        };

        return $resource::getNavigationLabel().($action ? ' › '.$action : '');
    }

    /** Registro que se abrió, en las páginas de editar o ver. */
    private function record(Request $request, string $page): ?Model
    {
        $key = $request->route('record');

        if (! is_subclass_of($page, ResourcePage::class) || blank($key)) {
            return null;
        }

        $record = $page::getResource()::resolveRecordRouteBinding($key);

        return $record instanceof Model ? $record : null;
    }
}
