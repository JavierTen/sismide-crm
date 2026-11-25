<?php

namespace App\Http\Controllers;

use App\Support\EvidenceZips;
use App\Support\ExportLogger;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

/**
 * Entrega el ZIP de "Descargar Evidencias" (Caracterización, Planes de negocio
 * y Canvas). Los registros los escoge la pantalla y llegan por la sesión con un
 * token de un solo uso, como en Gestión Documental.
 */
class EvidenceZipController extends Controller
{
    public function download(Request $request)
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $payload = session()->pull('evidence_zip_' . $request->query('token'));
        abort_if(empty($payload['ids']) || ! EvidenceZips::exists($payload['type'] ?? null), 404);

        set_time_limit(600);

        $zip = EvidenceZips::zip($payload['type'], $payload['ids']);

        if (! $zip) {
            Notification::make()
                ->warning()
                ->title('Sin evidencias')
                ->body('Los registros no tienen archivos ni videos para descargar.')
                ->send();

            return redirect()->back();
        }

        // Ley 1581 de 2012: queda constancia de quién descargó qué.
        ExportLogger::log(EvidenceZips::logLabel($payload['type']), [
            'file'    => $zip['name'],
            'records' => $zip['records'],
            'files'   => $zip['files'],
        ]);

        return response()->download($zip['path'], $zip['name'])->deleteFileAfterSend(true);
    }
}
