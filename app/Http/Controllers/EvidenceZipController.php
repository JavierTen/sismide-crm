<?php

namespace App\Http\Controllers;

use App\Support\EvidenceZips;
use App\Support\ExportLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * "Descargar Evidencias" (Caracterización, Planes de negocio y Canvas) en tres
 * pasos, para que la pantalla muestre el avance:
 *  1. prepare: arma el ZIP con los registros que escogió la pantalla.
 *  2. progress: la pantalla pregunta el porcentaje mientras se arma.
 *  3. file: entrega el ZIP armado (una sola vez) y deja constancia.
 */
class EvidenceZipController extends Controller
{
    public function prepare(Request $request): JsonResponse
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $token   = (string) $request->input('token');
        $payload = Cache::pull(EvidenceZips::cacheKey('request', $token));
        abort_if(empty($payload['ids']) || ($payload['user'] ?? null) !== auth()->id() || ! EvidenceZips::exists($payload['type'] ?? null), 404);

        set_time_limit(600);
        static::removeOldZips();

        // El avance se guarda solo cuando cambia, para no escribir en caché a cada archivo.
        $last     = -1;
        $progress = function (int $percent) use ($token, &$last) {
            if ($percent !== $last) {
                Cache::put(EvidenceZips::cacheKey('progress', $token), $last = $percent, now()->addMinutes(30));
            }
        };

        $zip = EvidenceZips::zip($payload['type'], $payload['ids'], $progress);

        if (! $zip) {
            return response()->json(['status' => 'empty']);
        }

        Cache::put(EvidenceZips::cacheKey('ready', $token), $zip + ['type' => $payload['type'], 'user' => auth()->id()], now()->addMinutes(30));

        return response()->json([
            'status'  => 'ready',
            'url'     => route('evidences.file', ['token' => $token]),
            'files'   => $zip['files'],
            'records' => $zip['records'],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        return response()->json(['percent' => Cache::get(EvidenceZips::cacheKey('progress', (string) $request->query('token')))]);
    }

    public function file(Request $request)
    {
        abort_unless(EvidenceZips::canDownload(), 403);

        $token = (string) $request->query('token');
        $zip   = Cache::pull(EvidenceZips::cacheKey('ready', $token));
        abort_if(! $zip || $zip['user'] !== auth()->id() || ! is_file($zip['path']), 404);

        Cache::forget(EvidenceZips::cacheKey('progress', $token));

        // Ley 1581 de 2012: queda constancia de quién descargó qué.
        ExportLogger::log(EvidenceZips::logLabel($zip['type']), [
            'file'    => $zip['name'],
            'records' => $zip['records'],
            'files'   => $zip['files'],
        ]);

        return response()->download($zip['path'], $zip['name'])->deleteFileAfterSend(true);
    }

    /** ZIP que se armaron y nunca se descargaron (se cerró la pestaña, por ejemplo). */
    private static function removeOldZips(): void
    {
        foreach (glob(storage_path('app/temp/evidencias_*.zip')) ?: [] as $file) {
            if (filemtime($file) < now()->subDay()->getTimestamp()) {
                @unlink($file);
            }
        }
    }
}
