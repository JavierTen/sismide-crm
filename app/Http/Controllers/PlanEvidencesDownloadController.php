<?php

namespace App\Http\Controllers;

use App\Support\ExportLogger;
use App\Support\PlanEvidences;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

/**
 * Entrega el ZIP de "Descargar Evidencias" de Planes de negocio y Canvas.
 * Los registros los escoge la pantalla (lo que muestra la tabla) y llegan por
 * la sesión con un token de un solo uso.
 */
class PlanEvidencesDownloadController extends Controller
{
    public function download(Request $request)
    {
        abort_unless(PlanEvidences::canDownload(), 403);

        $payload = session()->pull('plan_evidences_' . $request->query('token'));
        abort_if(empty($payload['ids']) || ! in_array($payload['type'] ?? null, [PlanEvidences::PLANS, PlanEvidences::CANVASES], true), 404);

        set_time_limit(600);

        $zip = PlanEvidences::zip($payload['type'], $payload['ids']);

        if (! $zip) {
            Notification::make()
                ->warning()
                ->title('Sin evidencias')
                ->body('Los registros de la tabla no tienen archivos ni videos para descargar.')
                ->send();

            return redirect()->back();
        }

        // Ley 1581 de 2012: queda constancia de quién descargó qué.
        ExportLogger::log($payload['type'] === PlanEvidences::PLANS ? 'Evidencias de planes de negocio (ZIP)' : 'Evidencias de Canvas (ZIP)', [
            'file'    => $zip['name'],
            'records' => $zip['records'],
            'files'   => $zip['files'],
        ]);

        return response()->download($zip['path'], $zip['name'])->deleteFileAfterSend(true);
    }
}
